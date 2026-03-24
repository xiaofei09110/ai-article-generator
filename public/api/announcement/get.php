<?php
/**
 * 获取公告接口
 * GET /api/announcement/get.php
 *
 * 响应：
 *  - success: boolean
 *  - data: {content: string} 公告内容
 */

require_once '../../../src/bootstrap.php';

try {
    // 获取最新的激活公告
    $announcement = Database::fetchOne(
        'SELECT content FROM announcements WHERE is_active = 1 ORDER BY updated_at DESC LIMIT 1',
        []
    );

    if ($announcement) {
        Response::success(
            ['content' => $announcement['content']],
            '获取成功'
        );
    } else {
        Response::success(
            ['content' => ''],
            '暂无公告'
        );
    }
} catch (Exception $e) {
    Response::serverError('获取公告失败: ' . $e->getMessage());
}
