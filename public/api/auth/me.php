<?php
/**
 * 获取当前登录用户信息
 * GET /api/auth/me.php
 *
 * 响应：
 *  - success: boolean 是否成功
 *  - data: object 用户信息（如果已登录）
 *  - message: string 说明文字
 */

require_once '../../../src/bootstrap.php';

// 只允许GET请求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('仅支持GET请求', 405);
}

// 获取当前用户信息
$user = Auth::getCurrentUser();

if ($user) {
    Response::success($user, '已登录');
} else {
    Response::error('未登录', 401);
}
