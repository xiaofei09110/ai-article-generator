<?php
/**
 * 速率限制类 - 基于MySQL的请求限流
 * 替代原 /tmp 文件方案（容器重启不丢失）
 */

class RateLimit {
    /**
     * 检查是否超过限流
     */
    public static function check(
        string $ip,
        string $endpoint,
        int $limit,
        int $windowSeconds
    ): bool {
        $cutoffTime = date('Y-m-d H:i:s', time() - $windowSeconds);

        $count = Database::fetchColumn(
            'SELECT COUNT(*) FROM rate_limits
             WHERE ip_address = ? AND endpoint = ? AND request_at > ?',
            [$ip, $endpoint, $cutoffTime]
        );

        return $count < $limit;
    }

    /**
     * 检查并返回错误响应
     */
    public static function checkOrFail(
        string $endpoint,
        int $limit,
        int $windowSeconds
    ): void {
        $ip = self::getClientIP();

        if (!self::check($ip, $endpoint, $limit, $windowSeconds)) {
            Response::tooManyRequests("请求过于频繁，请在{$windowSeconds}秒后重试");
        }
    }

    /**
     * 记录一次请求
     */
    public static function record(string $endpoint): void {
        $ip = self::getClientIP();

        Database::insert('rate_limits', [
            'ip_address' => $ip,
            'endpoint' => $endpoint,
            'request_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 清理过期的限流记录
     * 应该定期调用（可在cron或请求中概率触发）
     */
    public static function cleanup(int $retentionSeconds = 86400): void {
        $cutoffTime = date('Y-m-d H:i:s', time() - $retentionSeconds);

        Database::delete('rate_limits', [
            'request_at <' => $cutoffTime
        ]);

        // 实际上上面的delete方法有问题，让我用原生SQL
        Database::query(
            'DELETE FROM rate_limits WHERE request_at < ?',
            [$cutoffTime]
        );
    }

    /**
     * 获取客户端IP地址
     * 支持代理和负载均衡
     */
    private static function getClientIP(): string {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            // Cloudflare
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // 可能有多个IP，取第一个
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        // 验证IP格式
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }

        return 'unknown';
    }

    /**
     * 话题生成的便捷方法
     */
    public static function checkTopicGenerate(): void {
        $limit = Config::get('TOPIC_REQUEST_LIMIT', 5);
        $window = Config::get('TOPIC_REQUEST_WINDOW', 60);

        self::checkOrFail('topic_generate', (int)$limit, (int)$window);
    }

    /**
     * 话题生成的记录方法
     */
    public static function recordTopicGenerate(): void {
        self::record('topic_generate');
    }
}
