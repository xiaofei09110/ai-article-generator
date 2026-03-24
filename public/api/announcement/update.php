<?php
/**
 * 更新公告接口（仅管理员）
 * POST /api/announcement/update.php
 *
 * 请求参数（JSON）：
 *  - content: string 新公告内容
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

// 检查管理员权限
Auth::requireAdminApi();

// 获取JSON请求体
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    Response::error('无效的JSON数据', 400);
}

$content = trim($input['content'] ?? '');

if (empty($content)) {
    Response::validationError(['content' => '公告内容不能为空']);
}

// 更新公告（若不存在则插入）
try {
    // 先检查是否有公告存在
    $existing = Database::fetchOne('SELECT id FROM announcements WHERE is_active = 1 LIMIT 1');

    if ($existing) {
        // 更新现有公告
        Database::update(
            'announcements',
            [
                'content' => $content,
                'created_by' => Auth::getUserId(),
                'updated_at' => date('Y-m-d H:i:s'),
            ],
            ['id' => $existing['id']]
        );
    } else {
        // 插入新公告
        Database::insert(
            'announcements',
            [
                'content' => $content,
                'is_active' => 1,
                'created_by' => Auth::getUserId(),
            ]
        );
    }

    Response::success(null, '公告已更新');
} catch (Exception $e) {
    Response::serverError('更新公告失败: ' . $e->getMessage());
}
