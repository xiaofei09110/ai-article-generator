<?php
/**
 * AI润色需求接口
 * POST /api/polish/requirements.php
 *
 * 请求参数（JSON）：
 *  - requirements: string (必需) 原始需求描述
 *  - _csrf: string CSRF Token
 *
 * 响应：
 *  - success: boolean
 *  - data: {polished_requirements: string}
 *  - message: string
 */

require_once '../../../src/bootstrap.php';

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('仅支持POST请求', 405);
}

// CSRF保护
CSRF::verifyOrFail();

// 获取JSON请求体
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    Response::error('无效的JSON数据', 400);
}

// 验证参数
$requirements = trim($input['requirements'] ?? '');

if (empty($requirements)) {
    Response::validationError(['requirements' => '需求描述不能为空']);
}

if (strlen($requirements) > 5000) {
    Response::validationError(['requirements' => '需求描述太长，请控制在5000字以内']);
}

try {
    // 调用AIClient润色需求
    $polishedRequirements = AIClient::polishRequirements($requirements);

    Response::success(
        ['polished_requirements' => $polishedRequirements],
        '需求已润色'
    );

} catch (Exception $e) {
    error_log('Polish requirements error: ' . $e->getMessage());
    Response::error(
        'AI润色失败: ' . $e->getMessage(),
        500
    );
}
