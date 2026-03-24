<?php
/**
 * 删除文章API（仅限本人或管理员）
 * POST /api/article/delete.php
 *
 * 请求参数：
 *  - article_id: int 文章ID
 *  - _csrf: string CSRF Token
 */

require_once '../../../src/bootstrap.php';

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('仅支持POST请求', 405);
}

// CSRF保护
CSRF::verifyOrFail();

// 需要登录
Auth::requireLoginApi();

// 获取JSON请求体
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    Response::error('无效的JSON数据', 400);
}

$articleId = (int)($input['article_id'] ?? 0);

if ($articleId <= 0) {
    Response::validationError(['article_id' => '无效的文章ID']);
}

try {
    // 获取文章
    $article = Database::fetchOne(
        'SELECT id, user_id FROM articles WHERE id = ?',
        [$articleId]
    );

    if (!$article) {
        Response::notFound('文章不存在');
    }

    // 检查权限：只能删除自己的文章或者是管理员
    if ($article['user_id'] !== Auth::getUserId() && !Auth::isAdmin()) {
        Response::forbidden('无权删除此文章');
    }

    // 删除文章（会级联删除相关的评分）
    Database::delete('articles', ['id' => $articleId]);

    Response::success(null, '文章已删除');

} catch (Exception $e) {
    error_log('Delete article error: ' . $e->getMessage());
    Response::serverError('删除文章失败: ' . $e->getMessage());
}
