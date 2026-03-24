<?php
/**
 * 用户注册接口
 * POST /api/auth/register.php
 *
 * 请求参数（JSON）：
 *  - email: string (必需) 邮箱
 *  - password: string (必需) 密码
 *  - password_confirm: string (必需) 确认密码
 *  - nickname: string (可选) 昵称
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
$passwordConfirm = $input['password_confirm'] ?? '';
$nickname = trim($input['nickname'] ?? '');

// 前端验证通常已做，但后端也要验证
if (!$email) {
    Response::validationError(['email' => '邮箱不能为空']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::validationError(['email' => '邮箱格式不正确']);
}

if (strlen($password) < 8) {
    Response::validationError(['password' => '密码长度至少8个字符']);
}

if ($password !== $passwordConfirm) {
    Response::validationError(['password_confirm' => '两次输入的密码不一致']);
}

// 调用Auth类进行注册
$result = Auth::register($email, $password, $nickname);

if ($result['success']) {
    // 注册成功，返回用户信息
    Response::success(
        [
            'user_id' => $result['user_id'],
            'email' => $email,
            'nickname' => $nickname ?: $email,
        ],
        $result['message']
    );
} else {
    // 注册失败
    Response::error($result['message'], 400);
}
