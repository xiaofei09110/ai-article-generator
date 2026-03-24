<?php
/**
 * 系统设置测试页 - 验证基础设施是否正常
 * 部署后访问 http://localhost:8080/test_setup.php 检查
 * 注意：生产环境应删除此文件
 */

require_once '../src/bootstrap.php';

$results = [];
$allPassed = true;

// 1. 检查PHP版本
$results['PHP版本'] = [
    'status' => version_compare(PHP_VERSION, '8.2.0') >= 0 ? 'pass' : 'fail',
    'message' => PHP_VERSION,
];
if ($results['PHP版本']['status'] === 'fail') $allPassed = false;

// 2. 检查必要的PHP扩展
$requiredExtensions = ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    $results["扩展: $ext"] = [
        'status' => extension_loaded($ext) ? 'pass' : 'fail',
        'message' => extension_loaded($ext) ? '已加载' : '未加载',
    ];
    if ($results["扩展: $ext"]['status'] === 'fail') $allPassed = false;
}

// 3. 检查.env文件
$envFile = dirname(__DIR__) . '/.env';
$results['环境配置'] = [
    'status' => file_exists($envFile) ? 'pass' : 'fail',
    'message' => file_exists($envFile) ? '.env文件存在' : '.env文件不存在',
];
if ($results['环境配置']['status'] === 'fail') $allPassed = false;

// 4. 检查Config加载
try {
    $dbConfig = Config::getDB();
    $results['Config加载'] = [
        'status' => !empty($dbConfig['host']) ? 'pass' : 'fail',
        'message' => "主机: {$dbConfig['host']}, 数据库: {$dbConfig['name']}",
    ];
} catch (Exception $e) {
    $results['Config加载'] = [
        'status' => 'fail',
        'message' => $e->getMessage(),
    ];
    $allPassed = false;
}

// 5. 检查数据库连接
try {
    $pdo = Database::getInstance();
    $pdo->query('SELECT 1');
    $results['数据库连接'] = [
        'status' => 'pass',
        'message' => '连接成功',
    ];
} catch (Exception $e) {
    $results['数据库连接'] = [
        'status' => 'fail',
        'message' => $e->getMessage(),
    ];
    $allPassed = false;
}

// 6. 检查所需的表
$requiredTables = ['users', 'articles', 'ratings', 'announcements', 'rate_limits'];
foreach ($requiredTables as $table) {
    try {
        $result = Database::fetchOne(
            "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
            [Config::get('DB_NAME'), $table]
        );
        $results["表: $table"] = [
            'status' => $result ? 'pass' : 'fail',
            'message' => $result ? '表存在' : '表不存在',
        ];
        if (!$result) $allPassed = false;
    } catch (Exception $e) {
        $results["表: $table"] = [
            'status' => 'fail',
            'message' => $e->getMessage(),
        ];
        $allPassed = false;
    }
}

// 7. 检查src目录权限
$srcDir = dirname(__DIR__) . '/src';
$results['src目录权限'] = [
    'status' => is_readable($srcDir) ? 'pass' : 'fail',
    'message' => is_readable($srcDir) ? '可读' : '不可读',
];

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI文章生成系统 - 系统检查</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .status-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .status-item { padding: 15px; border: 2px solid #e0e0e0; border-radius: 6px; }
        .status-item.pass { border-color: #4caf50; background: #f1f8f4; }
        .status-item.fail { border-color: #f44336; background: #fef5f5; }
        .status-item.warning { border-color: #ff9800; background: #fff3f0; }
        .status-label { font-weight: 600; font-size: 14px; }
        .status-icon { display: inline-block; width: 20px; height: 20px; border-radius: 50%; margin-right: 8px; vertical-align: middle; }
        .pass .status-icon { background: #4caf50; }
        .fail .status-icon { background: #f44336; }
        .warning .status-icon { background: #ff9800; }
        .status-message { font-size: 13px; color: #666; margin-top: 5px; font-family: monospace; }
        .summary { margin-top: 30px; padding: 20px; border-radius: 6px; text-align: center; }
        .summary.all-passed { background: #f1f8f4; border: 2px solid #4caf50; color: #2e7d32; }
        .summary.has-issues { background: #fef5f5; border: 2px solid #f44336; color: #c62828; }
        .summary h2 { margin-bottom: 10px; }
        .action-buttons { margin-top: 20px; text-align: center; }
        .btn { display: inline-block; padding: 10px 20px; margin: 0 5px; border-radius: 4px; text-decoration: none; font-size: 14px; cursor: pointer; border: none; }
        .btn-primary { background: #667eea; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .btn-primary:hover { background: #5568d3; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 系统设置检查</h1>
            <p>AI文章生成系统 - 基础设施验证</p>
        </div>

        <div class="content">
            <div class="status-grid">
                <?php foreach ($results as $name => $result): ?>
                    <div class="status-item <?php echo $result['status']; ?>">
                        <div class="status-label">
                            <span class="status-icon"></span>
                            <?php echo htmlspecialchars($name); ?>
                        </div>
                        <div class="status-message"><?php echo htmlspecialchars($result['message']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary <?php echo $allPassed ? 'all-passed' : 'has-issues'; ?>">
                <h2><?php echo $allPassed ? '✅ 所有检查通过！' : '❌ 存在问题需要解决'; ?></h2>
                <p>
                    <?php if ($allPassed): ?>
                        系统已正确安装和配置，可以开始使用。建议删除此测试页面以提高安全性。
                    <?php else: ?>
                        请根据上面的检查结果修复问题后再继续。
                    <?php endif; ?>
                </p>
                <?php if ($allPassed): ?>
                    <div class="action-buttons">
                        <a href="/" class="btn btn-primary">进入系统</a>
                        <form method="POST" style="display:inline;">
                            <button type="submit" name="delete" value="1" class="btn btn-danger" onclick="return confirm('确定要删除此测试文件吗？');">
                                删除测试页面
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    // 处理删除请求
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
        $currentFile = __FILE__;
        if (unlink($currentFile)) {
            header('Location: /');
            exit;
        }
    }
    ?>
</body>
</html>
