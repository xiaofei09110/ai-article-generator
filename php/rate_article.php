<?php
header('Content-Type: application/json');

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$title = $data['title'] ?? '';
$rating = $data['rating'] ?? 0;
$comment = $data['comment'] ?? '';

// 验证输入
if (empty($title) || $rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => '标题和评分不能为空，评分必须在1-5之间']);
    exit;
}

// 保存评分数据
$result = saveRating($title, $rating, $comment);

if ($result) {
    echo json_encode(['success' => true, 'message' => '评分已成功提交']);
} else {
    echo json_encode(['success' => false, 'error' => '评分提交失败，请稍后重试']);
}

// 保存评分数据
function saveRating($title, $rating, $comment) {
    // 评分数据文件路径
    $ratingsFile = '../data/ratings.json';
    
    // 确保数据目录存在
    if (!file_exists('../data')) {
        mkdir('../data', 0755, true);
    }
    
    // 读取现有评分数据
    $ratings = [];
    if (file_exists($ratingsFile)) {
        $ratingsJson = file_get_contents($ratingsFile);
        if (!empty($ratingsJson)) {
            $ratings = json_decode($ratingsJson, true);
        }
    }
    
    // 添加新评分
    $ratings[] = [
        'title' => $title,
        'rating' => $rating,
        'comment' => $comment,
        'rating_time' => date('Y-m-d H:i:s')
    ];
    
    // 保存评分数据
    $result = file_put_contents($ratingsFile, json_encode($ratings, JSON_PRETTY_PRINT));
    
    return $result !== false;
} 