<?php
/**
 * 文章评分处理
 * 四川粒子通识网络科技有限公司
 */

// 设置内容类型为JSON
header('Content-Type: application/json');

// 设置中国时区
date_default_timezone_set('Asia/Shanghai');

// 定义评分数据文件路径
$ratingFile = '../data/ratings.json';

// 检查并创建数据目录
if (!file_exists('../data')) {
    mkdir('../data', 0755, true);
}

// 获取POST数据
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// 检查必需参数
if (!isset($input['title']) || !isset($input['rating'])) {
    echo json_encode(['success' => false, 'message' => '缺少必需参数']);
    exit;
}

// 提取参数
$title = $input['title'];
$rating = (int)$input['rating'];
$time = isset($input['time']) ? $input['time'] : date('Y-m-d H:i:s');

// 验证评分范围
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => '评分必须在1-5之间']);
    exit;
}

try {
    // 获取现有评分数据
    $ratings = [];
    if (file_exists($ratingFile)) {
        $ratingsJson = file_get_contents($ratingFile);
        if (!empty($ratingsJson)) {
            $ratings = json_decode($ratingsJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $ratings = []; // 如果JSON解析错误，重置为空数组
            }
        }
    }
    
    // 添加新评分
    $newRating = [
        'title' => $title,
        'rating' => $rating,
        'generation_time' => $time, // 生成文章时间（从客户端传来）
        'rating_time' => date('Y-m-d H:i:s') // 提交评分时间（服务器时间）
    ];
    
    $ratings[] = $newRating;
    
    // 保存评分数据到JSON文件
    file_put_contents($ratingFile, json_encode($ratings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // 返回成功
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 