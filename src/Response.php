<?php
/**
 * API响应统一处理类
 * 所有API接口都返回统一格式的JSON
 */

class Response {
    /**
     * 成功响应
     */
    public static function success($data = null, string $message = ''): never {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $response = [
            'success' => true,
            'message' => $message ?: '操作成功',
            'data' => $data,
            'errors' => [],
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * 错误响应
     */
    public static function error(
        string $message = '操作失败',
        int $httpCode = 400,
        array $errors = []
    ): never {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $response = [
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ];

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * 401 - 未登录
     */
    public static function unauthorized(string $message = '请先登录'): never {
        self::error($message, 401);
    }

    /**
     * 403 - 权限不足
     */
    public static function forbidden(string $message = '权限不足'): never {
        self::error($message, 403);
    }

    /**
     * 404 - 不存在
     */
    public static function notFound(string $message = '请求的资源不存在'): never {
        self::error($message, 404);
    }

    /**
     * 429 - 限流
     */
    public static function tooManyRequests(string $message = '请求过于频繁，请稍后再试'): never {
        self::error($message, 429);
    }

    /**
     * 422 - 验证错误
     */
    public static function validationError(array $errors, string $message = '验证失败'): never {
        self::error($message, 422, $errors);
    }

    /**
     * 500 - 服务器错误
     */
    public static function serverError(string $message = '服务器内部错误'): never {
        self::error($message, 500);
    }
}
