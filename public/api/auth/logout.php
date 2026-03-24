<?php
/**
 * 用户登出接口
 * POST /api/auth/logout.php
 *
 * 请求参数（JSON）：
 *  - _csrf: string CSRF Token
 *
 * 响应：
 *  - success: boolean 是否成功
 *  - message: string 说明文字
 */

require_once '../../../src/bootstrap.php';

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('仅支持POST请求', 405);
}

// CSRF保护
CSRF::verifyOrFail();

// 调用Auth类进行登出
Auth::logout();

// 返回成功
Response::success(
    null,
    '登出成功'
);
