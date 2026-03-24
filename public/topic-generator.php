<?php
/**
 * 行业话题生成器页面
 * 从topic-generator.html改造为PHP
 */

require_once '../src/bootstrap.php';

$currentUser = Auth::getCurrentUser();
$csrfToken = CSRF::getToken();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>行业话题生成器 - AI文章生成与评分系统</title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/topic-generator.css">
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="fas fa-pen-fancy"></i> AI文章生成
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav navbar-user" style="margin-left: auto;">
                    <li class="nav-item">
                        <a class="nav-link" href="/">
                            <i class="fas fa-home"></i> 首页
                        </a>
                    </li>
                    <?php if ($currentUser): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['nickname']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/profile.php"><i class="fas fa-history"></i> 我的文章</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> 退出登录</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">
                                <i class="fas fa-sign-in-alt"></i> 登录
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-primary btn-sm ms-2" href="/register.php">
                                <i class="fas fa-user-plus"></i> 注册
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 公告栏 -->
    <div id="announcement" class="bg-primary text-white p-2 text-center">
        <div class="container">
            <span id="announcement-text">正在加载公告...</span>
        </div>
    </div>

    <!-- 主内容区 -->
    <div class="container mt-4">
        <header class="text-center mb-5">
            <h1 class="display-4">行业话题生成器</h1>
            <p class="lead">选择您的行业，快速获取热门话题灵感</p>
            <a href="/" class="btn btn-outline-primary mt-2">
                <i class="fas fa-arrow-left me-2"></i>返回首页
            </a>
        </header>

        <!-- 话题生成表单 -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title mb-0"><i class="fas fa-lightbulb me-2"></i>生成行业话题</h3>
                    </div>
                    <div class="card-body">
                        <form id="topic-form">
                            <div class="mb-3">
                                <label for="industry" class="form-label">选择或输入您的行业</label>
                                <input type="text" class="form-control" id="industry" required placeholder="例如：互联网、金融、教育...">
                                <small class="form-text text-muted">支持自由输入，如互联网、金融、教育、医疗等</small>
                            </div>
                            <div class="mb-3">
                                <label for="topic-count" class="form-label">生成话题数量</label>
                                <select class="form-select" id="topic-count">
                                    <option value="5">5条</option>
                                    <option value="7" selected>7条</option>
                                    <option value="10">10条</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="generate-btn">
                                <i class="fas fa-magic me-2"></i>生成话题
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 加载提示 -->
                <div id="loading" class="text-center my-4 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">加载中...</span>
                    </div>
                    <p class="mt-2">正在为您生成高质量行业话题...</p>
                </div>

                <!-- 话题结果 -->
                <div id="topics-section" class="mb-4 d-none">
                    <h3 class="mb-3"><i class="fas fa-list-ul me-2"></i>话题列表</h3>
                    <div id="topics-container" class="topic-list"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p>© 2024 四川粒子通识网络科技有限公司</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        // 加载公告
        function loadAnnouncement() {
            fetch('/api/announcement/get.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.data.content) {
                        document.getElementById('announcement-text').textContent = data.data.content;
                    } else {
                        document.getElementById('announcement').classList.add('d-none');
                    }
                });
        }

        // 页面加载时初始化
        document.addEventListener('DOMContentLoaded', function() {
            loadAnnouncement();

            // 表单提交
            document.getElementById('topic-form').addEventListener('submit', async (e) => {
                e.preventDefault();

                const industry = document.getElementById('industry').value;
                const count = parseInt(document.getElementById('topic-count').value);

                document.getElementById('loading').classList.remove('d-none');
                document.getElementById('topics-section').classList.add('d-none');

                try {
                    const response = await fetch('/api/topic/generate.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrfToken
                        },
                        body: JSON.stringify({ industry, count })
                    });

                    const data = await response.json();
                    document.getElementById('loading').classList.add('d-none');

                    if (data.success) {
                        const html = data.data.topics.map(topic => `
                            <div class="topic-card">
                                <div class="topic-content">
                                    <p class="topic-text">${escapeHtml(topic)}</p>
                                    <div class="topic-actions">
                                        <button class="btn btn-sm btn-outline-primary" onclick="copyTopic('${escapeHtml(topic, true)}')">
                                            <i class="fas fa-copy"></i> 复制
                                        </button>
                                        <button class="btn btn-sm btn-success" onclick="useTopic('${escapeHtml(topic, true)}')">
                                            <i class="fas fa-check"></i> 使用这个话题
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `).join('');

                        document.getElementById('topics-container').innerHTML = html;
                        document.getElementById('topics-section').classList.remove('d-none');
                    } else {
                        alert(data.message || '生成失败');
                    }
                } catch (error) {
                    console.error('错误:', error);
                    document.getElementById('loading').classList.add('d-none');
                    alert('生成失败，请稍后重试');
                }
            });
        });

        // 复制话题
        function copyTopic(topic) {
            navigator.clipboard.writeText(topic).then(() => {
                alert('已复制');
            });
        }

        // 使用话题
        function useTopic(topic) {
            localStorage.setItem('selected_topic', topic);
            window.location.href = '/index.php';
        }

        // HTML转义
        function escapeHtml(text, isJs = false) {
            if (isJs) {
                return text.replace(/'/g, "\\'").replace(/"/g, '\\"');
            }
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // 登出
        function logout() {
            if (confirm('确定要退出登录吗？')) {
                logoutUser().then(() => {
                    location.href = '/';
                });
            }
        }
    </script>
</body>
</html>
