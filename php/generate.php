<?php
/**
 * AI文章生成处理
 * 四川粒子通识网络科技有限公司
 */

// 设置内容类型为JSON
header('Content-Type: application/json');

// 设置中国时区
date_default_timezone_set('Asia/Shanghai');

// 获取POST数据
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// 检查必需参数
if (!isset($input['title']) || !isset($input['requirements']) || !isset($input['wordLimit']) || !isset($input['style']) || !isset($input['apiSource'])) {
    echo json_encode(['error' => '缺少必需参数']);
    exit;
}

// 提取参数
$title = $input['title'];
$requirements = $input['requirements'];
$wordLimit = (int)$input['wordLimit'];
$style = $input['style'];
$apiSource = $input['apiSource'];

// 根据选择的API调用不同的接口
try {
    if ($apiSource === 'ailion') {
        $result = callAilionAPI($title, $requirements, $wordLimit, $style);
    } else if ($apiSource === 'deepseek') {
        $result = callDeepseekAPI($title, $requirements, $wordLimit, $style);
    } else {
        echo json_encode(['error' => '不支持的API来源']);
        exit;
    }
    
    // 返回结果
    echo json_encode(['content' => $result]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * 调用Ailion API
 */
function callAilionAPI($title, $requirements, $wordLimit, $style) {
    $apiKey = 'sk-s7MuNA41JTsF5XexGxgIf7cmSK55klRk8zuKqJGDv1UxATwx';
    $apiUrl = 'https://api.ailion.top/v1/chat/completions';
    
    // 根据风格调整提示词
    $stylePrompt = getStylePrompt($style);
    
    // 构建请求数据
    $requestData = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            [
                'role' => 'system',
                'content' => "你是一位专业的内容创作者，擅长根据需求撰写高质量文章。请用{$stylePrompt}风格写作。"
            ],
            [
                'role' => 'user',
                'content' => "请根据以下要求撰写一篇文章：\n标题：{$title}\n内容要求：{$requirements}\n字数限制：大约{$wordLimit}字。"
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => min(max($wordLimit * 2, 1000), 4000)
    ];
    
    return callAPI($apiUrl, $apiKey, $requestData);
}

/**
 * 调用DeepSeek API
 */
function callDeepseekAPI($title, $requirements, $wordLimit, $style) {
    $apiKey = 'sk-1977cad2e2794c5bb65664fb7301a04c';
    $apiUrl = 'https://api.deepseek.com/v1/chat/completions';
    
    // 根据风格调整提示词
    $stylePrompt = getStylePrompt($style);
    
    // 构建请求数据
    $requestData = [
        'model' => 'deepseek-chat',
        'messages' => [
            [
                'role' => 'system',
                'content' => "你是一位专业的内容创作者，擅长根据需求撰写高质量文章。请用{$stylePrompt}风格写作。"
            ],
            [
                'role' => 'user',
                'content' => "请根据以下要求撰写一篇文章：\n标题：{$title}\n内容要求：{$requirements}\n字数限制：大约{$wordLimit}字。"
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => min(max($wordLimit * 2, 1000), 4000)
    ];
    
    return callAPI($apiUrl, $apiKey, $requestData);
}

/**
 * 根据风格获取相应的提示词
 */
function getStylePrompt($style) {
    switch ($style) {
        case 'formal':
            return '正式、严谨、专业';
        case 'casual':
            return '轻松、活泼、亲切';
        case 'tech':
            return '科技感、未来感、专业';
        case 'professional':
            return '行业专业、精确、权威';
        case 'creative':
            return '创意、新颖、独特';
        default:
            return '清晰、简洁、易懂';
    }
}

/**
 * 通用API调用函数
 */
function callAPI($url, $apiKey, $data) {
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 设置60秒超时
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 关闭SSL验证，生产环境不建议
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('API请求失败: ' . $error);
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode < 200 || $httpCode >= 300) {
        throw new Exception('API返回错误码: ' . $httpCode);
    }
    
    $responseData = json_decode($response, true);
    
    // 不同API的响应格式可能不同，需要适配
    if (strpos($url, 'ailion.top') !== false) {
        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }
    } else if (strpos($url, 'deepseek.com') !== false) {
        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }
    }
    
    // 如果上面的条件都不满足，尝试通用方式解析
    if (isset($responseData['choices'][0]['message']['content'])) {
        return $responseData['choices'][0]['message']['content'];
    } else if (isset($responseData['output'])) {
        return $responseData['output'];
    } else if (isset($responseData['text'])) {
        return $responseData['text'];
    }
    
    throw new Exception('无法解析API响应');
}
?> 