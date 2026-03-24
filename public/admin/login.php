<?php
/**
 * 管理员登录页
 */

require_once '../../src/bootstrap.php';

// 如果已经登录且是管理员，重定向到后台
$currentUser = Auth::getCurrentUser();
if ($currentUser && $currentUser['role'] === 'admin') {
    header('Location: /admin/');
    exit;
}

$csrfToken = CSRF::getToken();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - AI文章生成系统</title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        .form-control, .btn {
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 14px;
        }
        .form-control {
            border: 1px solid #ddd;
            margin-bottom: 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            padding: 10px;
            width: 100%;
            margin-top: 10px;
        }
        .btn-login:hover {
            color: white;
            opacity: 0.9;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .login-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        .alert {
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 12px;
            top: 10px;
            color: #666;
        }
        .form-floating-icon {
            position: relative;
        }
        .form-floating-icon .password-toggle {
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-shield-alt me-2"></i>管理员登录</h2>
            <p>AI文章生成与评分系统</p>
        </div>

        <div id="error-message"></div>

        <form id="admin-login-form">
            <div class="mb-3">
                <label for="email" class="form-label">邮箱</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    required
                    placeholder="请输入管理员邮箱"
                    autocomplete="email"
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">密码</label>
                <div class="form-floating-icon">
                    <input
                        type="password"
                        class="form-control pe-5"
                        id="password"
                        name="password"
                        required
                        placeholder="请输入密码"
                        autocomplete="current-password"
                    >
                    <i class="fas fa-eye password-toggle" onclick="togglePassword()" title="显示/隐藏密码"></i>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input
                    type="checkbox"
                    class="form-check-input"
                    id="remember"
                    name="remember"
                >
                <label class="form-check-label" for="remember">
                    记住我
                </label>
            </div>

            <button type="submit" class="btn btn-login">
                <i class="fas fa-sign-in-alt me-2"></i>登录
            </button>
        </form>

        <div class="login-footer">
            <a href="/" class="me-3">
                <i class="fas fa-home me-1"></i>返回首页
            </a>
            <a href="/login.php">
                <i class="fas fa-user me-1"></i>用户登录
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/auth.js"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        // 密码可见性切换
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.querySelector('.password-toggle');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.add('fa-eye-slash');
                icon.classList.remove('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // 处理登录表单
        document.getElementById('admin-login-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;

            // 前端验证
            if (!email || !password) {
                showError('请填写所有字段');
                return;
            }

            if (!validateEmail(email)) {
                showError('邮箱格式不正确');
                return;
            }

            try {
                const response = await fetch('/api/auth/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken
                    },
                    body: JSON.stringify({ email, password, remember })
                });

                const data = await response.json();

                if (data.success) {
                    // 登录成功，检查是否为管理员
                    const meResponse = await fetch('/api/auth/me.php');
                    const meData = await meResponse.json();

                    if (meData.success && meData.data.role === 'admin') {
                        // 重定向到管理后台
                        window.location.href = '/admin/';
                    } else {
                        showError('您没有管理员权限');
                    }
                } else {
                    showError(data.message || '登录失败');
                }
            } catch (error) {
                console.error('错误:', error);
                showError('登录失败，请稍后重试');
            }
        });

        function showError(message) {
            const errorDiv = document.getElementById('error-message');
            errorDiv.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`;
        }
    </script>
</body>
</html>
