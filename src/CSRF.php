<?php
/**
 * CSRF Token 管理类
 * 防跨站请求伪造 (Cross-Site Request Forgery)
 */

class CSRF {
    const TOKEN_NAME = '_csrf';
    const TOKEN_LENGTH = 32;

    /**
     * 生成CSRF Token（每次页面加载时调用）
     */
    public static function generate(): string {
        if (!session_id()) {
            session_start();
        }

        // 生成新token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $_SESSION[self::TOKEN_NAME] = $token;

        return $token;
    }

    /**
     * 验证CSRF Token
     * 从多个位置检查：POST数据、JSON Body、HTTP Header
     */
    public static function verify(string $providedToken = null): bool {
        if (!session_id()) {
            session_start();
        }

        // 获取已存储的token
        $storedToken = $_SESSION[self::TOKEN_NAME] ?? null;

        if (!$storedToken) {
            return false;
        }

        // 如果没有提供token，尝试从多个位置获取
        if ($providedToken === null) {
            // 1. 从POST数据
            $providedToken = $_POST[self::TOKEN_NAME] ?? null;
            // 2. 从JSON body
            if (!$providedToken && $_SERVER['CONTENT_TYPE'] === 'application/json') {
                $json = json_decode(file_get_contents('php://input'), true);
                $providedToken = $json[self::TOKEN_NAME] ?? null;
            }
            // 3. 从HTTP Header
            if (!$providedToken) {
                $providedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            }
        }

        // 安全对比（防时序攻击）
        return hash_equals($storedToken, $providedToken ?: '');
    }

    /**
     * 验证并在失败时返回错误响应
     */
    public static function verifyOrFail(string $providedToken = null): void {
        if (!self::verify($providedToken)) {
            Response::error('CSRF Token 验证失败', 403);
        }
    }

    /**
     * 获取隐藏表单字段HTML
     */
    public static function getField(): string {
        $token = self::generate();
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::TOKEN_NAME,
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * 获取Token值（用于JSON API或AJAX）
     */
    public static function getToken(): string {
        return self::generate();
    }
}
