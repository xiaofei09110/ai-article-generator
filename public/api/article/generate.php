<?php
/**
 * 文章生成接口
 * POST /api/article/generate.php
 *
 * 请求参数（JSON）：
 *  - title: string (必需) 文章标题
 *  - requirements: string (必需) 内容需求
 *  - wordLimit: int (必需) 字数限制
 *  - style: string (必需) 文章风格
 *  - outputFormat: string (必需) 输出格式
 *  - apiSource: string (必需) AI API来源 (deepseek/ailion)
 *  - _csrf: string CSRF Token
 *
 * 响应：
 *  - success: boolean
 *  - data: {content: string, article_id: int}
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

// 验证必需参数
$title = trim($input['title'] ?? '');
$requirements = trim($input['requirements'] ?? '');
$wordLimit = (int)($input['wordLimit'] ?? 800);
$style = $input['style'] ?? 'formal';
$outputFormat = $input['outputFormat'] ?? 'default';
$apiSource = strtolower($input['apiSource'] ?? '');

// 参数验证
if (empty($title)) {
    Response::validationError(['title' => '标题不能为空']);
}

if (empty($requirements)) {
    Response::validationError(['requirements' => '内容需求不能为空']);
}

if ($wordLimit < 100 || $wordLimit > 10000) {
    Response::validationError(['wordLimit' => '字数限制应在100-10000之间']);
}

$validStyles = ['formal', 'casual', 'tech', 'professional', 'creative'];
if (!in_array($style, $validStyles)) {
    Response::validationError(['style' => '无效的文章风格']);
}

$validFormats = ['default', 'work-report', 'xiaohongshu', 'wechat'];
if (!in_array($outputFormat, $validFormats)) {
    Response::validationError(['outputFormat' => '无效的输出格式']);
}

$validSources = ['deepseek', 'ailion'];
if (!in_array($apiSource, $validSources)) {
    Response::validationError(['apiSource' => '无效的API来源']);
}

try {
    // 调用AIClient生成文章
    $content = AIClient::generateArticle(
        $apiSource,
        $title,
        $requirements,
        $wordLimit,
        $style
    );

    // 获取当前用户ID（如果已登录）
    $userId = Auth::getUserId();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    // 保存文章记录到数据库
    $articleId = Database::insert('articles', [
        'user_id' => $userId,
        'title' => $title,
        'requirements' => $requirements,
        'content' => $content,
        'word_limit' => $wordLimit,
        'style' => $style,
        'output_format' => $outputFormat,
        'api_source' => $apiSource,
        'ip_address' => $ipAddress,
        'generated_at' => date('Y-m-d H:i:s'),
    ]);

    // 返回成功响应
    Response::success(
        [
            'article_id' => $articleId,
            'content' => $content,
        ],
        '文章生成成功'
    );

} catch (Exception $e) {
    // 记录错误
    error_log('Article generation error: ' . $e->getMessage());

    // 返回错误响应
    Response::error(
        'AI文章生成失败，请检查API配置或稍后重试: ' . $e->getMessage(),
        500
    );
}
