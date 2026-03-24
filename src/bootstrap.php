<?php
/**
 * 应用启动文件 - 所有PHP文件都应该在开头包含此文件
 * 此文件负责：
 * 1. 加载所有核心类
 * 2. 初始化配置
 * 3. 启动Session
 * 4. 设置错误处理
 */

// 定义根目录
define('ROOT_DIR', dirname(__DIR__));
define('SRC_DIR', __DIR__);

// 自动加载器（简单的类加载）
spl_autoload_register(function ($class) {
    $classPath = SRC_DIR . DIRECTORY_SEPARATOR . $class . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

// 加载Config类（必须首先加载）
require_once SRC_DIR . '/Config.php';
require_once SRC_DIR . '/Database.php';
require_once SRC_DIR . '/Response.php';
require_once SRC_DIR . '/CSRF.php';

// 设置PHP配置
date_default_timezone_set('Asia/Shanghai');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// 设置错误处理（生产环境不显示错误）
if (Config::get('APP_ENV') === 'production') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', '/var/log/php/error.log');
} else {
    ini_set('display_errors', '1');
}

// 启动Session（如果还未启动）
if (!session_id() && !headers_sent()) {
    session_set_cookie_params([
        'lifetime' => Config::get('SESSION_LIFETIME', 7200),
        'path' => '/',
        'domain' => '',
        'secure' => false,  // 本地开发；生产环境改为true
        'httponly' => true,
        'samesite' => Config::get('SESSION_COOKIE_SAMESITE', 'Lax'),
    ]);
    session_start();
}

// 设置JSON返回的字符编码
header('Content-Type: application/json; charset=utf-8');
