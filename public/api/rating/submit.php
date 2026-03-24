<?php
/**
 * 评分提交接口（合并原 rating.php 和 rate_article.php）
 * POST /api/rating/submit.php
 *
 * 请求参数（JSON）：
 *  - article_id: int (必需) 文章ID
 *  - rating: int (必需) 评分 1-5
 *  - comment: string (可选) 评论
 *  - _csrf: string CSRF Token
 *
 * 响应：
 *  - success: boolean
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
$articleId = (int)($input['article_id'] ?? 0);
$rating = (int)($input['rating'] ?? 0);
$comment = trim($input['comment'] ?? '');

if ($articleId <= 0) {
    Response::validationError(['article_id' => '无效的文章ID']);
}

if ($rating < 1 || $rating > 5) {
    Response::validationError(['rating' => '评分必须在1-5之间']);
}

try {
    // 检查文章是否存在
    $article = Database::fetchOne(
        'SELECT id FROM articles WHERE id = ?',
        [$articleId]
    );

    if (!$article) {
        Response::notFound('文章不存在');
    }

    // 获取当前用户ID（如果已登录）
    $userId = Auth::getUserId();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    // 检查是否已评分过（可选，暂不实施限制）
    // 如需防重复评分，可在此检查

    // 插入评分记录
    $ratingId = Database::insert('ratings', [
        'article_id' => $articleId,
        'user_id' => $userId,
        'rating' => $rating,
        'comment' => $comment ?: null,
        'ip_address' => $ipAddress,
        'rated_at' => date('Y-m-d H:i:s'),
    ]);

    Response::success(
        ['rating_id' => $ratingId],
        '评分已提交，感谢您的反馈！'
    );

} catch (Exception $e) {
    error_log('Rating submission error: ' . $e->getMessage());
    Response::serverError('评分提交失败: ' . $e->getMessage());
}
