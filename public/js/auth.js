/**
 * 认证页面共享的JavaScript逻辑
 */

document.addEventListener('DOMContentLoaded', function() {
    // 密码显示/隐藏切换
    setupPasswordToggle();

    // CSRF Token注入（如果需要）
    injectCSRFToken();
});

/**
 * 设置密码显示/隐藏
 */
function setupPasswordToggle() {
    const toggleButtons = document.querySelectorAll('.toggle-password');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();

            const targetId = this.dataset.target;
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.add('fa-eye');
                icon.classList.remove('fa-eye-slash');
            }
        });
    });
}

/**
 * 注入CSRF Token到meta标签
 */
function injectCSRFToken() {
    // 如果已存在CSRF token meta标签，不需要做任何事
    if (document.querySelector('meta[name="csrf-token"]')) {
        return;
    }

    // 否则请求服务器获取新的token
    fetch('/api/auth/csrf-token.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.token) {
                const meta = document.createElement('meta');
                meta.name = 'csrf-token';
                meta.content = data.data.token;
                document.head.appendChild(meta);
            }
        })
        .catch(error => console.warn('CSRF Token 获取失败:', error));
}

/**
 * 显示加载状态
 */
function showLoading(element, message = '加载中...') {
    const originalHTML = element.innerHTML;
    element.disabled = true;
    element.innerHTML = `<i class="fas fa-spinner fa-spin"></i> ${message}`;
    return originalHTML;
}

/**
 * 恢复按钮状态
 */
function restoreButton(element, originalHTML) {
    element.disabled = false;
    element.innerHTML = originalHTML;
}

/**
 * 显示通知
 */
function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show`;
    notification.setAttribute('role', 'alert');
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    // 插入到页面顶部
    document.body.insertBefore(notification, document.body.firstChild);

    // 自动关闭
    if (duration > 0) {
        setTimeout(() => {
            notification.remove();
        }, duration);
    }

    return notification;
}

/**
 * 验证邮箱格式
 */
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * 验证密码强度
 */
function validatePasswordStrength(password) {
    const strength = {
        score: 0,
        level: 'weak',
        feedback: []
    };

    if (password.length >= 8) strength.score++;
    if (password.length >= 12) strength.score++;
    if (/[a-z]/.test(password)) strength.score++;
    if (/[A-Z]/.test(password)) strength.score++;
    if (/[0-9]/.test(password)) strength.score++;
    if (/[!@#$%^&*]/.test(password)) strength.score++;

    if (strength.score <= 2) {
        strength.level = 'weak';
        strength.feedback.push('密码强度较弱');
    } else if (strength.score <= 4) {
        strength.level = 'medium';
        strength.feedback.push('密码强度中等');
    } else {
        strength.level = 'strong';
        strength.feedback.push('密码强度良好');
    }

    return strength;
}

/**
 * 统一的fetch包装器（自动带CSRF Token）
 */
async function apiFetch(url, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            ...options.headers
        }
    };

    if (csrfToken) {
        defaultOptions.headers['X-CSRF-Token'] = csrfToken;
    }

    return fetch(url, {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    });
}

/**
 * 处理API响应
 */
async function handleApiResponse(response) {
    const data = await response.json();

    // 如果返回401，说明Session过期，重定向到登录
    if (response.status === 401) {
        window.location.href = '/login.php';
        return null;
    }

    // 如果返回403，说明权限不足
    if (response.status === 403) {
        showNotification('您没有权限执行此操作', 'warning');
        return null;
    }

    return data;
}

/**
 * 获取当前登录用户信息
 */
async function getCurrentUser() {
    try {
        const response = await apiFetch('/api/auth/me.php');
        const data = await response.json();

        if (data.success && data.data) {
            return data.data;
        }
    } catch (error) {
        console.error('获取用户信息失败:', error);
    }

    return null;
}

/**
 * 注册用户
 */
async function registerUser(email, password, nickname = '') {
    try {
        const response = await apiFetch('/api/auth/register.php', {
            method: 'POST',
            body: JSON.stringify({
                email: email,
                password: password,
                nickname: nickname
            })
        });

        return await handleApiResponse(response);
    } catch (error) {
        console.error('注册失败:', error);
        return { success: false, message: '网络错误' };
    }
}

/**
 * 登录用户
 */
async function loginUser(email, password) {
    try {
        const response = await apiFetch('/api/auth/login.php', {
            method: 'POST',
            body: JSON.stringify({
                email: email,
                password: password
            })
        });

        return await handleApiResponse(response);
    } catch (error) {
        console.error('登录失败:', error);
        return { success: false, message: '网络错误' };
    }
}

/**
 * 登出用户
 */
async function logoutUser() {
    try {
        const response = await apiFetch('/api/auth/logout.php', {
            method: 'POST'
        });

        const data = await response.json();

        if (data.success) {
            window.location.href = '/';
        }

        return data;
    } catch (error) {
        console.error('登出失败:', error);
        return { success: false, message: '网络错误' };
    }
}
