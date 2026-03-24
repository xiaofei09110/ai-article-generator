<?php
/**
 * 后台登录页面
 * 四川粒子通识网络科技有限公司
 */

// 设置中国时区
date_default_timezone_set('Asia/Shanghai');

// 启动会话
session_start();

// 检查是否已登录
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // 已登录，重定向到管理页面
    header('Location: index.php');
    exit;
}

// 处理登录请求
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 验证账号密码
    if ($username === 'admin' && $password === '123456') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_login_time'] = time();
        
        // 重定向到管理页面
        header('Location: index.php');
        exit;
    } else {
        $error = '账号或密码错误';
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI文章生成与评分系统 - 管理后台登录</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome图标 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            max-width: 400px;
            width: 90%;
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #4361ee;
            color: white;
            border-radius: 0.5rem 0.5rem 0 0 !important;
            text-align: center;
            padding: 1.5rem;
        }
        .form-control {
            border-radius: 0.5rem;
            padding: 0.75rem;
        }
        .btn-primary {
            border-radius: 0.5rem;
            padding: 0.75rem;
        }
        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            padding: 1rem;
            background-color: rgba(255, 255, 255, 0.8);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card login-card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-lock me-2"></i>管理后台登录</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">账号</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="username" name="username" required autofocus>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label">密码</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>登录
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="../index.html" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i>返回网站首页
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="text-muted">
        <div class="container">
            <p class="mb-0">© 2024 四川粒子通识网络科技有限公司 <a href="https://liztongshi.cn" target="_blank">liztongshi.cn</a></p>
            <p class="small">本项目由粒子通识科技开发，版权所有。</p>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 