<?php
/**
 * 公告系统
 * 四川粒子通识网络科技有限公司
 */

// 设置内容类型为JSON
header('Content-Type: application/json');

// 公告文件路径
$announcementFile = '../data/announcement.txt';

// 检查公告文件是否存在
if (file_exists($announcementFile)) {
    $message = file_get_contents($announcementFile);
    // 如果公告为空，返回空消息
    if (empty(trim($message))) {
        echo json_encode(['message' => '']);
    } else {
        echo json_encode(['message' => $message]);
    }
} else {
    // 文件不存在，创建默认公告
    $defaultAnnouncement = '欢迎使用AI文章生成与评分系统！由四川粒子通识网络科技有限公司提供技术支持。';
    
    // 确保数据目录存在
    if (!file_exists('../data')) {
        mkdir('../data', 0755, true);
    }
    
    file_put_contents($announcementFile, $defaultAnnouncement);
    echo json_encode(['message' => $defaultAnnouncement]);
}
?> 