<?php
/**
 * 查看文章详情API
 * GET /api/article/view.php?id=1
 */

require_once '../../../src/bootstrap.php';

// 只允许GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('仅支持GET请求', 405);
}

$articleId = (int)($_GET['id'] ?? 0);

if ($articleId <= 0) {
    Response::validationError(['id' => '无效的文章ID']);
}

try {
    // 获取文章
    $article = Database::fetchOne(
        'SELECT id, user_id, title, requirements, content, style, word_limit, output_format, api_source, generated_at
         FROM articles
         WHERE id = ?',
        [$articleId]
    );

    if (!$article) {
        Response::notFound('文章不存在');
    }

    // 如果是登录用户，只能查看自己的文章；如果未登录，可以查看任意文章
    if (Auth::isLoggedIn()) {
        if ($article['user_id'] !== Auth::getUserId() && !Auth::isAdmin()) {
            Response::forbidden('无权查看此文章');
        }
    }

    Response::success($article, '获取成功');

} catch (Exception $e) {
    Response::serverError('获取文章失败: ' . $e->getMessage());
}
