<?php
/**
 * 话题生成接口
 * POST /api/topic/generate.php
 *
 * 请求参数（JSON）：
 *  - industry: string (必需) 行业名称
 *  - count: int (必需) 生成数量 5-10
 *  - _csrf: string CSRF Token
 *
 * 响应：
 *  - success: boolean
 *  - data: {topics: array}
 *  - message: string
 */

require_once '../../../src/bootstrap.php';

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('仅支持POST请求', 405);
}

// CSRF保护
CSRF::verifyOrFail();

// 速率限制
RateLimit::checkTopicGenerate();

// 获取JSON请求体
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    Response::error('无效的JSON数据', 400);
}

// 验证参数
$industry = trim($input['industry'] ?? '');
$count = (int)($input['count'] ?? 5);

if (empty($industry)) {
    Response::validationError(['industry' => '行业名称不能为空']);
}

if ($count < 5 || $count > 10) {
    $count = 5;
}

try {
    // 调用AIClient生成话题
    $topics = AIClient::generateTopics($industry, $count);

    // 记录请求（用于限流）
    RateLimit::recordTopicGenerate();

    Response::success(
        ['topics' => $topics],
        '话题生成成功'
    );

} catch (Exception $e) {
    error_log('Topic generation error: ' . $e->getMessage());
    Response::error(
        'AI话题生成失败: ' . $e->getMessage(),
        500
    );
}
