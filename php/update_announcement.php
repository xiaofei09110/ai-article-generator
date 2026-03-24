<?php
header('Content-Type: application/json');

// 启动会话
session_start();

// 检查是否已登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'error' => '未登录或会话已过期']);
    exit;
}

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$announcement = $data['announcement'] ?? '';

// 保存公告
$result = saveAnnouncement($announcement);

if ($result) {
    echo json_encode(['success' => true, 'message' => '公告已成功更新']);
} else {
    echo json_encode(['success' => false, 'error' => '公告更新失败，请稍后重试']);
}

// 保存公告
function saveAnnouncement($announcement) {
    // 公告文件路径
    $announcementFile = '../data/announcement.txt';
    
    // 确保数据目录存在
    if (!file_exists('../data')) {
        mkdir('../data', 0755, true);
    }
    
    // 保存公告
    $result = file_put_contents($announcementFile, $announcement);
    
    return $result !== false;
} 