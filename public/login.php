<?php
require_once '../src/bootstrap.php';

// 如果已登录，重定向到首页
if (Auth::isLoggedIn()) {
    header('Location: /');
    exit;
}

$pageTitle = '用户登录';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - AI文章生成系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body class="auth-page">
    <div class="container auth-container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <h1 class="mb-2"><i class="fas fa-sign-in-alt"></i> 登录</h1>
                        <p class="text-muted">欢迎回来</p>
                    </div>

                    <form id="loginForm" class="auth-form">
                        <div class="mb-3">
                            <label for="email" class="form-label">邮箱</label>
                            <input
                                type="email"
                                class="form-control form-control-lg"
                                id="email"
                                name="email"
                                placeholder="your@email.com"
                                required
                                autofocus
                            >
                            <div class="invalid-feedback" id="emailFeedback">请输入有效的邮箱地址</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">密码</label>
                            <div class="input-group input-group-lg">
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    placeholder="••••••••"
                                    required
                                >
                                <button
                                    class="btn btn-outline-secondary toggle-password"
                                    type="button"
                                    data-target="password"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="passwordFeedback">请输入密码</div>
                        </div>

                        <div class="mb-3 d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="rememberMe">
                                <label class="form-check-label" for="rememberMe">
                                    记住我
                                </label>
                            </div>
                            <a href="#" class="text-decoration-none" onclick="alert('如需重置密码，请联系管理员'); return false;">
                                忘记密码？
                            </a>
                        </div>

                        <div id="alertBox"></div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg" id="submitBtn">
                            <i class="fas fa-sign-in-alt"></i> 登录
                        </button>
                    </form>

                    <div class="auth-footer">
                        <hr class="my-4">
                        <p class="text-center text-muted">
                            还没有账号？ <a href="/register.php" class="fw-bold">立即注册</a>
                        </p>
                    </div>

                    <!-- 演示账号提示 -->
                    <div class="alert alert-info alert-sm mt-3" role="alert">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            未登录用户也可生成文章，但不会保存历史记录。登录后可查看所有生成的文章。
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js"></script>
    <script>
        // 页面特定的登录逻辑
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const rememberMe = document.getElementById('rememberMe').checked;

            // 前端验证
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showAlert('请输入有效的邮箱地址', 'danger');
                return;
            }

            if (!password) {
                showAlert('请输入密码', 'danger');
                return;
            }

            // 禁用提交按钮
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 登录中...';

            try {
                // 获取CSRF Token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                const response = await fetch('/api/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken || ''
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password,
                        remember_me: rememberMe,
                        _csrf: csrfToken || ''
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('登录成功！正在跳转...', 'success');
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 1000);
                } else {
                    showAlert(data.message || '登录失败', 'danger');
                }
            } catch (error) {
                console.error('登录错误:', error);
                showAlert('网络错误，请稍后重试', 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> 登录';
            }
        });

        // 显示提示消息
        function showAlert(message, type = 'info') {
            const alertBox = document.getElementById('alertBox');
            const alertHtml = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            alertBox.innerHTML = alertHtml;

            // 自动关闭
            if (type !== 'danger') {
                setTimeout(() => {
                    alertBox.innerHTML = '';
                }, 3000);
            }
        }
    </script>
</body>
</html>
