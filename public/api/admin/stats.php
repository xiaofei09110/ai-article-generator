<?php
/**
 * 管理后台统计API
 * GET /api/admin/stats.php
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

try {
    // 总文章数
    $totalArticles = Database::fetchColumn('SELECT COUNT(*) FROM articles');

    // 总用户数
    $totalUsers = Database::fetchColumn('SELECT COUNT(*) FROM users WHERE role = ?', ['user']);

    // 总评分数
    $totalRatings = Database::fetchColumn('SELECT COUNT(*) FROM ratings');

    // 平均评分
    $avgRating = Database::fetchColumn('SELECT ROUND(AVG(rating), 2) FROM ratings') ?? 0;

    // 最近的评分（最多10条）
    $recentRatings = Database::fetchAll(
        'SELECT r.id, r.rating, r.comment, a.title, r.rated_at
         FROM ratings r
         LEFT JOIN articles a ON r.article_id = a.id
         ORDER BY r.rated_at DESC
         LIMIT 10'
    );

    // 各评分等级的分布
    $ratingDistribution = Database::fetchAll(
        'SELECT rating, COUNT(*) as count
         FROM ratings
         GROUP BY rating
         ORDER BY rating DESC'
    );

    Response::success([
        'total_articles' => (int)$totalArticles,
        'total_users' => (int)$totalUsers,
        'total_ratings' => (int)$totalRatings,
        'avg_rating' => (float)$avgRating,
        'recent_ratings' => $recentRatings,
        'rating_distribution' => $ratingDistribution,
    ], '获取统计数据成功');

} catch (Exception $e) {
    Response::serverError('获取统计数据失败: ' . $e->getMessage());
}
