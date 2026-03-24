<?php
require_once '../src/bootstrap.php';

// 如果已登录，重定向到首页
if (Auth::isLoggedIn()) {
    header('Location: /');
    exit;
}

$pageTitle = '用户注册';
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
            <div class="col-md-6">
                <div class="auth-card">
                    <div class="auth-header">
                        <h1 class="mb-2"><i class="fas fa-rocket"></i> 创建账号</h1>
                        <p class="text-muted">加入AI文章生成社区</p>
                    </div>

                    <form id="registerForm" class="auth-form">
                        <div class="mb-3">
                            <label for="nickname" class="form-label">昵称</label>
                            <input
                                type="text"
                                class="form-control"
                                id="nickname"
                                name="nickname"
                                placeholder="输入你的昵称（可选，默认为邮箱）"
                            >
                            <small class="form-text text-muted">用于显示你的身份</small>
                            <div class="invalid-feedback" id="nicknameFeedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">邮箱</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="example@email.com"
                                required
                            >
                            <div class="invalid-feedback" id="emailFeedback">请输入有效的邮箱地址</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">密码</label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    class="form-control"
                                    id="password"
                                    name="password"
                                    placeholder="至少8个字符"
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
                            <small class="form-text text-muted d-block mt-2">
                                密码要求：至少8个字符
                            </small>
                            <div class="invalid-feedback" id="passwordFeedback">密码至少需要8个字符</div>
                        </div>

                        <div class="mb-3">
                            <label for="passwordConfirm" class="form-label">确认密码</label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    class="form-control"
                                    id="passwordConfirm"
                                    name="passwordConfirm"
                                    placeholder="再次输入密码"
                                    required
                                >
                                <button
                                    class="btn btn-outline-secondary toggle-password"
                                    type="button"
                                    data-target="passwordConfirm"
                                >
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="passwordConfirmFeedback">两次输入的密码不一致</div>
                        </div>

                        <div class="mb-3 form-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="agreeTerms"
                                required
                            >
                            <label class="form-check-label" for="agreeTerms">
                                我已阅读并同意 <a href="#" onclick="alert('使用条款内容'); return false;">《服务条款》</a>
                            </label>
                            <div class="invalid-feedback">请同意服务条款</div>
                        </div>

                        <div id="alertBox"></div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg mb-3" id="submitBtn">
                            <i class="fas fa-user-plus"></i> 立即注册
                        </button>
                    </form>

                    <div class="auth-footer">
                        <p class="text-center text-muted">
                            已有账号？ <a href="/login.php" class="fw-bold">登录</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js"></script>
    <script>
        // 页面特定的注册逻辑
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            // 前端验证
            const nickname = document.getElementById('nickname').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('passwordConfirm').value;
            const agreeTerms = document.getElementById('agreeTerms').checked;

            // 验证邮箱格式
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showAlert('请输入有效的邮箱地址', 'danger');
                return;
            }

            // 验证密码长度
            if (password.length < 8) {
                showAlert('密码长度至少8个字符', 'danger');
                return;
            }

            // 验证密码一致
            if (password !== passwordConfirm) {
                showAlert('两次输入的密码不一致', 'danger');
                return;
            }

            // 验证服务条款
            if (!agreeTerms) {
                showAlert('请同意服务条款', 'danger');
                return;
            }

            // 禁用提交按钮
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 注册中...';

            try {
                // 获取CSRF Token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                const response = await fetch('/api/auth/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken || ''
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password,
                        password_confirm: passwordConfirm,
                        nickname: nickname || null,
                        _csrf: csrfToken || ''
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showAlert('注册成功！正在跳转到首页...', 'success');
                    setTimeout(() => {
                        window.location.href = '/';
                    }, 1500);
                } else {
                    showAlert(data.message || '注册失败', 'danger');
                }
            } catch (error) {
                console.error('注册错误:', error);
                showAlert('网络错误，请稍后重试', 'danger');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-user-plus"></i> 立即注册';
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

            // 自动关闭提示
            if (type !== 'danger') {
                setTimeout(() => {
                    alertBox.innerHTML = '';
                }, 3000);
            }
        }
    </script>
</body>
</html>
