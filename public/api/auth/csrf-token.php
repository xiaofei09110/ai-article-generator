<?php
/**
 * 获取CSRF Token
 * GET /api/auth/csrf-token.php
 *
 * 响应：
 *  - success: boolean
 *  - data: {token: string}
 */

require_once '../../../src/bootstrap.php';

// 生成新的CSRF Token
$token = CSRF::generate();

Response::success(
    ['token' => $token],
    'Token已生成'
);
