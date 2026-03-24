<?php
/**
 * 用户登录接口
 * POST /api/auth/login.php
 *
 * 请求参数（JSON）：
 *  - email: string (必需) 邮箱
 *  - password: string (必需) 密码
 *  - remember_me: boolean (可选) 记住我
 *
 * 响应：
 *  - success: boolean 是否成功
 *  - message: string 说明文字
 *  - data: object 用户信息
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

// 验证必需参数
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email) {
    Response::validationError(['email' => '邮箱不能为空']);
}

if (!$password) {
    Response::validationError(['password' => '密码不能为空']);
}

// 调用Auth类进行登录
$result = Auth::login($email, $password);

if ($result['success']) {
    // 登录成功，返回用户信息
    Response::success(
        $result['user'],
        $result['message']
    );
} else {
    // 登录失败
    Response::error($result['message'], 401);
}
