<?php
// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 设置响应头
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理OPTIONS请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 获取POST数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$requirements = $data['requirements'] ?? '';

// 验证输入
if (empty($requirements)) {
    echo json_encode(['success' => false, 'error' => '内容需求不能为空']);
    exit;
}

// 调用AI润色
$polished = callDeepSeekForPolish($requirements);

if ($polished) {
    echo json_encode(['success' => true, 'polished_requirements' => $polished]);
} else {
    echo json_encode(['success' => false, 'error' => '润色失败，请稍后重试']);
}

// 调用DeepSeek API进行润色
function callDeepSeekForPolish($text) {
    $api_url = 'https://api.deepseek.com/v1/chat/completions';
    $api_key = 'sk-1977cad2e2794c5bb65664fb7301a04c';  // DeepSeek API密钥
    
    $data = [
        'model' => 'deepseek-chat',
        'messages' => [
            [
                'role' => 'system',
                'content' => '你是一个专业的文案优化助手，擅长优化和改进文章需求描述，使其更加清晰、专业和易于理解。'
            ],
            [
                'role' => 'user',
                'content' => "请帮我优化以下文章需求描述，使其更加清晰、专业和结构化，但保持原有意图不变：\n\n{$text}"
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 1000
    ];
    
    // 使用file_get_contents代替curl
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key
            ],
            'content' => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    
    try {
        $response = file_get_contents($api_url, false, $context);
        if ($response === false) {
            error_log('API request failed');
            return null;
        }
        
        $result = json_decode($response, true);
        return $result['choices'][0]['message']['content'] ?? null;
    } catch (Exception $e) {
        error_log('API error: ' . $e->getMessage());
        return null;
    }
} 