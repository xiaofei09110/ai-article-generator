<?php
/**
 * 获取用户文章历史API
 * GET /api/article/history.php?page=1&limit=10
 *
 * 需要登录
 */

require_once '../../../src/bootstrap.php';

// 只允许GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('仅支持GET请求', 405);
}

// 需要登录
Auth::requireLoginApi();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
$offset = ($page - 1) * $limit;

try {
    $userId = Auth::getUserId();

    // 获取总数
    $totalCount = Database::fetchColumn(
        'SELECT COUNT(*) FROM articles WHERE user_id = ?',
        [$userId]
    );

    // 获取文章列表
    $articles = Database::fetchAll(
        'SELECT id, title, style, word_limit, output_format, api_source, generated_at
         FROM articles
         WHERE user_id = ?
         ORDER BY generated_at DESC
         LIMIT ? OFFSET ?',
        [$userId, $limit, $offset]
    );

    $totalPages = ceil($totalCount / $limit);

    Response::success([
        'items' => $articles,
        'total' => $totalCount,
        'pages' => $totalPages,
        'current_page' => $page,
        'per_page' => $limit,
    ], '获取成功');

} catch (Exception $e) {
    Response::serverError('获取文章列表失败: ' . $e->getMessage());
}
