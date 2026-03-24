<?php
/**
 * 配置管理类 - 从.env文件读取环境变量
 * 所有敏感信息（API密钥、数据库密码等）通过此类访问
 */

class Config {
    private static array $config = [];
    private static bool $loaded = false;

    /**
     * 加载.env文件
     */
    public static function load(string $envFile = null): void {
        if (self::$loaded) {
            return;
        }

        if ($envFile === null) {
            $envFile = dirname(__DIR__) . '/.env';
        }

        if (!file_exists($envFile)) {
            // 开发环境允许缺少.env，使用默认值
            if (getenv('APP_ENV') !== 'production') {
                self::loadDefaults();
                self::$loaded = true;
                return;
            }
            throw new Exception(".env 文件不存在: $envFile");
        }

        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // 跳过注释
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // 解析 KEY=VALUE
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // 移除引号
                if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
                    (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
                    $value = substr($value, 1, -1);
                }

                self::$config[$key] = $value;
                putenv("$key=$value");
            }
        }

        self::$loaded = true;
    }

    /**
     * 获取配置值
     */
    public static function get(string $key, $default = null) {
        if (!self::$loaded) {
            self::load();
        }

        return self::$config[$key] ?? $default;
    }

    /**
     * 检查配置项是否存在
     */
    public static function has(string $key): bool {
        if (!self::$loaded) {
            self::load();
        }

        return isset(self::$config[$key]);
    }

    /**
     * 获取数据库配置
     */
    public static function getDB(): array {
        return [
            'host' => self::get('DB_HOST', 'localhost'),
            'port' => self::get('DB_PORT', 3306),
            'name' => self::get('DB_NAME', 'ai_article_system'),
            'user' => self::get('DB_USER', 'root'),
            'pass' => self::get('DB_PASS', ''),
        ];
    }

    /**
     * 获取AI API配置
     */
    public static function getAIConfig(string $source): array {
        $source = strtoupper($source);

        return [
            'api_key' => self::get("{$source}_API_KEY", ''),
            'api_url' => self::get("{$source}_API_URL", ''),
            'model' => self::get("{$source}_MODEL", ''),
        ];
    }

    /**
     * 开发环境默认配置
     */
    private static function loadDefaults(): void {
        self::$config = [
            'APP_ENV' => 'development',
            'DB_HOST' => 'localhost',
            'DB_PORT' => '3306',
            'DB_NAME' => 'ai_article_system',
            'DB_USER' => 'root',
            'DB_PASS' => '',
            'SESSION_LIFETIME' => '7200',
            'SESSION_COOKIE_SAMESITE' => 'Lax',
            'TOPIC_MIN_COUNT' => '5',
            'TOPIC_MAX_COUNT' => '10',
            'TOPIC_REQUEST_LIMIT' => '5',
            'TOPIC_REQUEST_WINDOW' => '60',
        ];
    }
}

// 自动加载配置
Config::load();
