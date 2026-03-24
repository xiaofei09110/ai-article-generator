<?php
/**
 * 行业话题生成处理
 * 四川粒子通识网络科技有限公司
 * 安全限制：仅使用DeepSeek API，不暴露API Key到前端
 */

// 设置内容类型为JSON和错误处理
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 错误日志记录设置
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/topic_generator_errors.log');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // 加载配置文件
    if (!file_exists(__DIR__ . '/config.php')) {
        throw new Exception('配置文件不存在');
    }
    
    $config = require_once __DIR__ . '/config.php';
    
    // 检查配置是否完整
    if (!isset($config['deepseek']) || !isset($config['topic_generator'])) {
        throw new Exception('配置文件不完整');
    }
    
    // 提取配置
    $deepseekConfig = $config['deepseek'];
    $topicConfig = $config['topic_generator'];
    
    // 检查请求方法
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => '请使用POST方法请求'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 获取POST数据
    $inputJSON = file_get_contents('php://input');
    
    if (empty($inputJSON)) {
        echo json_encode(['error' => '没有接收到有效的POST数据'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $input = json_decode($inputJSON, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['error' => '无效的JSON数据: ' . json_last_error_msg()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 检查必需参数
    if (!isset($input['industry']) || !isset($input['count'])) {
        echo json_encode(['error' => '缺少必需参数'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 提取参数
    $industry = trim($input['industry']);
    $count = (int)$input['count'];
    
    // 验证参数
    if (empty($industry)) {
        echo json_encode(['error' => '行业不能为空'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 限制话题数量在配置的范围内
    if ($count < $topicConfig['min_count']) {
        $count = $topicConfig['min_count'];
    } else if ($count > $topicConfig['max_count']) {
        $count = $topicConfig['max_count'];
    }
    
    // 简单的访问频率限制
    if (!checkRequestLimit($topicConfig['request_limit'], $topicConfig['request_window'])) {
        echo json_encode(['error' => '请求频率过高，请稍后再试'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // 调用DeepSeek API生成话题
    $topics = generateTopics($industry, $count, $deepseekConfig, $topicConfig);
    
    // 清理话题格式
    $cleanedTopics = cleanTopics($topics, $topicConfig);
    
    echo json_encode(['success' => true, 'topics' => $cleanedTopics], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('话题生成错误: ' . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 检查请求频率限制
 * @param int $limit 允许的请求次数
 * @param int $window 时间窗口(秒)
 * @return bool 是否允许请求
 */
function checkRequestLimit($limit, $window) {
    // 获取客户端IP
    $clientIP = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $cacheFile = sys_get_temp_dir() . '/topic_requests_' . md5($clientIP) . '.txt';
    
    // 检查过去指定时间内的请求次数
    if (file_exists($cacheFile)) {
        $requests = @unserialize(file_get_contents($cacheFile));
        
        if ($requests === false) {
            // 如果反序列化失败，创建新的请求记录
            $requests = [];
        } else {
            // 清理超过时间窗口的请求记录
            $now = time();
            $requests = array_filter($requests, function($timestamp) use ($now, $window) {
                return ($now - $timestamp) < $window;
            });
            
            // 如果请求超过限制，则拒绝
            if (count($requests) >= $limit) {
                return false;
            }
        }
    } else {
        $requests = [];
    }
    
    // 记录当前请求
    $requests[] = time();
    
    // 确保目录存在并可写
    $tempDir = sys_get_temp_dir();
    if (!is_dir($tempDir) || !is_writable($tempDir)) {
        error_log("临时目录不存在或不可写: {$tempDir}");
        return true; // 忽略限制，继续执行
    }
    
    @file_put_contents($cacheFile, serialize($requests));
    
    return true;
}

/**
 * 调用API生成行业话题
 * @param string $industry 行业名称
 * @param int $count 话题数量
 * @param array $apiConfig API配置
 * @param array $topicConfig 话题配置
 * @return array 话题列表
 */
function generateTopics($industry, $count, $apiConfig, $topicConfig) {
    // 使用配置中的API信息
    $apiKey = $apiConfig['api_key'];
    $apiUrl = $apiConfig['api_base_url'] . '/v1/chat/completions';
    
    // 调试信息
    error_log("准备请求DeepSeek API，URL: {$apiUrl}，行业: {$industry}，数量: {$count}");
    
    // 构建请求数据
    $requestData = [
        'model' => $apiConfig['model'],
        'messages' => [
            [
                'role' => 'system',
                'content' => "你是一位行业内容创作专家，擅长为各行业提供热门且有价值的内容话题建议。你的回复必须简洁干净，不带任何前缀和后缀说明。"
            ],
            [
                'role' => 'user',
                'content' => "请为我生成{$count}个关于「{$industry}」行业的文章话题，这些话题必须满足：
1. 每条话题控制在{$topicConfig['min_topic_length']}-{$topicConfig['max_topic_length']}个汉字
2. 符合小红书、公众号阅读习惯，标题要简洁有吸引力
3. 能够引起读者共鸣，有实用价值
4. 反映行业热点或趋势

返回要求：
- 直接返回话题列表，每行一个话题
- 不要有编号或其他格式标记
- 不要包含任何引言和结尾说明
- 不要使用Markdown格式（如#、##、**等符号）"
            ]
        ],
        'temperature' => $apiConfig['temperature'],
        'max_tokens' => $apiConfig['max_tokens']
    ];
    
    // 调用API并获取响应
    $response = callAPI($apiUrl, $apiKey, $requestData);
    
    // 解析返回的话题
    if (isset($response['choices'][0]['message']['content'])) {
        $content = $response['choices'][0]['message']['content'];
        $topicLines = explode("\n", trim($content));
        
        // 过滤空行和格式标记
        $topics = array_filter(array_map('trim', $topicLines), function($line) {
            return !empty($line) && !preg_match('/^\d+[\.\、]/', $line);
        });
        
        // 确保返回数组格式
        $topics = array_values($topics);
        
        if (count($topics) > $count) {
            return array_slice($topics, 0, $count);
        } elseif (count($topics) < $count && count($topics) > 0) {
            // 如果返回的话题数量不足，但已有部分话题，则返回已有的话题
            return $topics;
        } elseif (count($topics) === 0) {
            throw new Exception('生成话题失败，请稍后重试');
        }
        
        return $topics;
    }
    
    if (isset($response['error'])) {
        $errorMessage = isset($response['error']['message']) 
            ? $response['error']['message'] 
            : json_encode($response['error']);
        throw new Exception('API返回错误: ' . $errorMessage);
    }
    
    throw new Exception('生成话题失败，无法解析API响应');
}

/**
 * 清理话题格式，确保符合要求
 * @param array $topics 原始话题列表
 * @param array $config 话题配置
 * @return array 清理后的话题
 */
function cleanTopics($topics, $config) {
    if (empty($topics) || !is_array($topics)) {
        return [];
    }
    
    $cleanedTopics = [];
    
    foreach ($topics as $topic) {
        if (!is_string($topic)) {
            continue;
        }
        
        // 清理Markdown格式
        $cleaned = preg_replace('/^#+\s+/', '', $topic); // 移除标题符号
        $cleaned = preg_replace('/\*\*(.*?)\*\*/', '$1', $cleaned); // 移除加粗
        $cleaned = preg_replace('/\*(.*?)\*/', '$1', $cleaned); // 移除斜体
        $cleaned = preg_replace('/`([^`]+)`/', '$1', $cleaned); // 移除代码格式
        $cleaned = preg_replace('/^\d+[\.\、]\s*/', '', $cleaned); // 移除序号
        
        // 移除多余的空格和换行
        $cleaned = trim($cleaned);
        
        // 限制长度在配置的范围内
        if (mb_strlen($cleaned, 'UTF-8') > $config['max_topic_length']) {
            $cleaned = mb_substr($cleaned, 0, $config['max_topic_length'], 'UTF-8');
        }
        
        // 确保每个话题都至少有最小字符数
        if (mb_strlen($cleaned, 'UTF-8') >= $config['min_topic_length']) {
            $cleanedTopics[] = $cleaned;
        }
    }
    
    return $cleanedTopics;
}

/**
 * 通用API调用函数
 * @param string $url API URL
 * @param string $apiKey API密钥
 * @param array $data 请求数据
 * @return array 解析后的响应数据
 */
function callAPI($url, $apiKey, $data) {
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];
    
    $ch = curl_init($url);
    
    if ($ch === false) {
        throw new Exception('初始化CURL失败');
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 增加超时时间到60秒
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    // 添加更多调试信息
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $curlInfo = curl_getinfo($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        
        // 记录详细的CURL错误信息
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        error_log("CURL详细日志: " . $verboseLog);
        
        curl_close($ch);
        throw new Exception("API请求失败: {$error} (错误码: {$errno})");
    }
    
    $httpCode = $curlInfo['http_code'];
    curl_close($ch);
    
    // 记录响应状态码和部分响应内容用于调试
    error_log("DeepSeek API 响应状态码: {$httpCode}");
    error_log("DeepSeek API 响应内容(部分): " . substr($response, 0, 500));
    
    if ($httpCode < 200 || $httpCode >= 300) {
        // 尝试解析错误响应
        $responseData = json_decode($response, true);
        $errorMessage = isset($responseData['error']['message']) 
            ? $responseData['error']['message'] 
            : "API返回错误码: {$httpCode}";
        throw new Exception($errorMessage);
    }
    
    $decodedResponse = json_decode($response, true);
    
    if ($decodedResponse === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log("API响应JSON解析错误: " . json_last_error_msg());
        error_log("原始响应: " . substr($response, 0, 1000)); // 记录部分原始响应用于调试
        throw new Exception('API返回无效的JSON响应: ' . json_last_error_msg());
    }
    
    return $decodedResponse;
} 