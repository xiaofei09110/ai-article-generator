<?php
/**
 * 管理员用户列表API
 * GET /api/admin/users.php?page=1&limit=20
 *
 * 需要管理员权限
 */

require_once '../../../src/bootstrap.php';

// 只允许GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('仅支持GET请求', 405);
}

// 检查管理员权限
Auth::requireAdminApi();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
$offset = ($page - 1) * $limit;

try {
    // 获取总用户数
    $totalCount = Database::fetchColumn('SELECT COUNT(*) FROM users');

    // 获取用户列表
    $users = Database::fetchAll(
        'SELECT id, email, nickname, role, is_active, created_at, last_login
         FROM users
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?',
        [$limit, $offset]
    );

    $totalPages = ceil($totalCount / $limit);

    Response::success([
        'items' => $users,
        'total' => (int)$totalCount,
        'pages' => (int)$totalPages,
        'current_page' => (int)$page,
        'per_page' => (int)$limit,
    ], '获取用户列表成功');

} catch (Exception $e) {
    Response::serverError('获取用户列表失败: ' . $e->getMessage());
}
