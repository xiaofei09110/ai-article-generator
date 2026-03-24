<?php
/**
 * 用户认证类 - 管理用户注册、登录、Session、权限检查
 */

class Auth {
    const SESSION_USER_ID = 'user_id';
    const SESSION_EMAIL = 'email';
    const SESSION_NICKNAME = 'nickname';
    const SESSION_ROLE = 'role';
    const SESSION_LOGIN_TIME = 'login_time';

    /**
     * 用户注册
     */
    public static function register(string $email, string $password, string $nickname = ''): array {
        // 验证邮箱格式
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => '邮箱格式不正确'];
        }

        // 验证密码强度
        if (strlen($password) < 8) {
            return ['success' => false, 'message' => '密码长度至少8位'];
        }

        // 检查邮箱是否已存在
        $existingUser = Database::fetchOne(
            'SELECT id FROM users WHERE email = ? LIMIT 1',
            [$email]
        );

        if ($existingUser) {
            return ['success' => false, 'message' => '该邮箱已被注册'];
        }

        // 哈希密码
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // 插入用户
        try {
            $userId = Database::insert('users', [
                'email' => $email,
                'password' => $hashedPassword,
                'nickname' => $nickname ?: $email,
                'role' => 'user',
                'is_active' => 1,
            ]);

            // 创建Session
            self::createSession($userId, $email, $nickname ?: $email, 'user');

            return [
                'success' => true,
                'message' => '注册成功',
                'user_id' => $userId,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => '注册失败，请稍后重试'];
        }
    }

    /**
     * 用户登录
     */
    public static function login(string $email, string $password): array {
        // 查找用户
        $user = Database::fetchOne(
            'SELECT id, password, nickname, role, is_active FROM users WHERE email = ?',
            [$email]
        );

        if (!$user) {
            return ['success' => false, 'message' => '邮箱或密码错误'];
        }

        if (!$user['is_active']) {
            return ['success' => false, 'message' => '账号已被禁用'];
        }

        // 验证密码
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => '邮箱或密码错误'];
        }

        // Session安全强化：重新生成ID防止会话固定攻击
        if (session_id()) {
            session_regenerate_id(true);
        }

        // 创建Session
        self::createSession($user['id'], $email, $user['nickname'], $user['role']);

        // 更新最后登录时间
        Database::update('users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $user['id']]);

        return [
            'success' => true,
            'message' => '登录成功',
            'user' => [
                'id' => $user['id'],
                'email' => $email,
                'nickname' => $user['nickname'],
                'role' => $user['role'],
            ],
        ];
    }

    /**
     * 退出登录
     */
    public static function logout(): void {
        session_destroy();
    }

    /**
     * 创建Session
     */
    private static function createSession(int $userId, string $email, string $nickname, string $role): void {
        $_SESSION[self::SESSION_USER_ID] = $userId;
        $_SESSION[self::SESSION_EMAIL] = $email;
        $_SESSION[self::SESSION_NICKNAME] = $nickname;
        $_SESSION[self::SESSION_ROLE] = $role;
        $_SESSION[self::SESSION_LOGIN_TIME] = time();
    }

    /**
     * 获取当前登录用户信息
     */
    public static function getCurrentUser(): ?array {
        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION[self::SESSION_USER_ID],
            'email' => $_SESSION[self::SESSION_EMAIL],
            'nickname' => $_SESSION[self::SESSION_NICKNAME],
            'role' => $_SESSION[self::SESSION_ROLE],
        ];
    }

    /**
     * 检查是否已登录
     */
    public static function isLoggedIn(): bool {
        return isset($_SESSION[self::SESSION_USER_ID]) && $_SESSION[self::SESSION_USER_ID] > 0;
    }

    /**
     * 检查是否是管理员
     */
    public static function isAdmin(): bool {
        return self::isLoggedIn() && $_SESSION[self::SESSION_ROLE] === 'admin';
    }

    /**
     * 获取当前用户ID
     */
    public static function getUserId(): ?int {
        return $_SESSION[self::SESSION_USER_ID] ?? null;
    }

    /**
     * API中间件：要求登录，否则返回401
     */
    public static function requireLoginApi(): void {
        if (!self::isLoggedIn()) {
            Response::unauthorized('请先登录');
        }
    }

    /**
     * API中间件：要求管理员，否则返回403
     */
    public static function requireAdminApi(): void {
        if (!self::isAdmin()) {
            Response::forbidden('您没有管理员权限');
        }
    }

    /**
     * 页面中间件：要求登录，否则重定向到登录页
     */
    public static function requireLoginPage(): void {
        if (!self::isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }

    /**
     * 页面中间件：要求管理员，否则重定向
     */
    public static function requireAdminPage(): void {
        if (!self::isAdmin()) {
            header('Location: /');
            exit;
        }
    }

    /**
     * 修改密码
     */
    public static function changePassword(int $userId, string $oldPassword, string $newPassword): array {
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => '新密码长度至少8位'];
        }

        // 获取当前密码哈希
        $user = Database::fetchOne('SELECT password FROM users WHERE id = ?', [$userId]);

        if (!$user) {
            return ['success' => false, 'message' => '用户不存在'];
        }

        // 验证旧密码
        if (!password_verify($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => '原密码错误'];
        }

        // 更新新密码
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::update('users', ['password' => $hashedPassword], ['id' => $userId]);

        return ['success' => true, 'message' => '密码已更改'];
    }
}
