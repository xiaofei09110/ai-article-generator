<?php
/**
 * 管理后台主页
 */

require_once '../../src/bootstrap.php';

// 需要管理员权限
Auth::requireAdminPage();

$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - AI文章生成系统</title>
    <meta name="csrf-token" content="<?php echo CSRF::getToken(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar { background: #f8f9fa; min-height: 100vh; padding: 20px 0; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; }
        .stat-card.users { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.articles { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card.ratings { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-value { font-size: 32px; font-weight: bold; }
        .nav-link { color: #333; border-left: 3px solid transparent; }
        .nav-link.active { border-left-color: #667eea; background: #e8eaf6; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- 侧边栏 -->
            <div class="col-md-2 sidebar">
                <div class="mb-4">
                    <h5 class="fw-bold ms-3">管理面板</h5>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link active" href="#" onclick="loadSection('dashboard')">
                        <i class="fas fa-chart-line me-2"></i> 数据统计
                    </a>
                    <a class="nav-link" href="#" onclick="loadSection('articles')">
                        <i class="fas fa-file-alt me-2"></i> 文章管理
                    </a>
                    <a class="nav-link" href="#" onclick="loadSection('users')">
                        <i class="fas fa-users me-2"></i> 用户管理
                    </a>
                    <a class="nav-link" href="#" onclick="loadSection('announcement')">
                        <i class="fas fa-bullhorn me-2"></i> 公告管理
                    </a>
                    <hr>
                    <a class="nav-link" href="/" onclick="return confirm('确定要回到首页吗？')">
                        <i class="fas fa-home me-2"></i> 返回首页
                    </a>
                    <a class="nav-link" href="#" onclick="logout(); return false;">
                        <i class="fas fa-sign-out-alt me-2"></i> 退出登录
                    </a>
                </nav>
            </div>

            <!-- 主内容区 -->
            <div class="col-md-10 p-4">
                <!-- 顶部信息栏 -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tachometer-alt me-2"></i> 管理后台</h2>
                    <div>
                        <span class="text-muted">欢迎，<?php echo htmlspecialchars($currentUser['nickname']); ?></span>
                    </div>
                </div>

                <!-- 动态内容区 -->
                <div id="content-area">
                    <!-- 数据统计 -->
                    <div id="dashboard" class="section">
                        <h4 class="mb-4">数据统计</h4>

                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card stat-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <p class="mb-2 opacity-75">总文章数</p>
                                                <div class="stat-value" id="stat-articles">-</div>
                                            </div>
                                            <i class="fas fa-file-alt fa-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card users">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <p class="mb-2 opacity-75">注册用户</p>
                                                <div class="stat-value" id="stat-users">-</div>
                                            </div>
                                            <i class="fas fa-users fa-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card articles">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <p class="mb-2 opacity-75">总评分数</p>
                                                <div class="stat-value" id="stat-ratings">-</div>
                                            </div>
                                            <i class="fas fa-star fa-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card stat-card ratings">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <p class="mb-2 opacity-75">平均评分</p>
                                                <div class="stat-value" id="stat-avg">-</div>
                                            </div>
                                            <i class="fas fa-chart-bar fa-2x opacity-50"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 最近评分 -->
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>最近评分</h5>
                            </div>
                            <div class="card-body">
                                <div id="recent-ratings-container">
                                    <div class="text-center py-4">
                                        <i class="fas fa-spinner fa-spin"></i> 加载中...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 用户管理 -->
                    <div id="users" class="section d-none">
                        <h4 class="mb-4">用户管理</h4>
                        <div class="card">
                            <div class="card-body">
                                <div id="users-container">
                                    <div class="text-center py-4">
                                        <i class="fas fa-spinner fa-spin"></i> 加载中...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 公告管理 -->
                    <div id="announcement" class="section d-none">
                        <h4 class="mb-4">公告管理</h4>
                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="card-title mb-0">编辑公告</h5>
                            </div>
                            <div class="card-body">
                                <form id="announcement-form">
                                    <div class="mb-3">
                                        <label for="announcement-content" class="form-label">公告内容</label>
                                        <textarea class="form-control" id="announcement-content" rows="5" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> 保存公告
                                    </button>
                                </form>
                                <div id="announcement-message" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/auth.js"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        // 加载不同的板块
        function loadSection(section) {
            // 隐藏所有section
            document.querySelectorAll('.section').forEach(s => s.classList.add('d-none'));
            // 显示选中的section
            document.getElementById(section).classList.remove('d-none');
            // 更新导航激活状态
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.closest('.nav-link').classList.add('active');

            // 加载对应的数据
            if (section === 'dashboard') {
                loadDashboard();
            } else if (section === 'users') {
                loadUsers();
            } else if (section === 'announcement') {
                loadAnnouncement();
            }
        }

        // 加载数据统计
        function loadDashboard() {
            fetch('/api/admin/stats.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('stat-articles').textContent = data.data.total_articles;
                        document.getElementById('stat-users').textContent = data.data.total_users;
                        document.getElementById('stat-ratings').textContent = data.data.total_ratings;
                        document.getElementById('stat-avg').textContent = data.data.avg_rating + ' ⭐';

                        // 显示最近评分
                        const html = data.data.recent_ratings.map(r => `
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <div>
                                    <strong>${escapeHtml(r.title || '（已删除文章）')}</strong>
                                    <br>
                                    <small class="text-muted">${new Date(r.rated_at).toLocaleString('zh-CN')}</small>
                                </div>
                                <div>
                                    <span class="badge bg-warning text-dark">${r.rating}⭐</span>
                                </div>
                            </div>
                        `).join('');
                        document.getElementById('recent-ratings-container').innerHTML = html || '<p class="text-muted">暂无评分</p>';
                    }
                });
        }

        // 加载用户列表
        function loadUsers() {
            fetch('/api/admin/users.php?page=1&limit=20')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        const html = `
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>邮箱</th>
                                        <th>昵称</th>
                                        <th>身份</th>
                                        <th>状态</th>
                                        <th>注册时间</th>
                                        <th>最后登录</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${data.data.items.map(user => `
                                        <tr>
                                            <td>${user.id}</td>
                                            <td>${escapeHtml(user.email)}</td>
                                            <td>${escapeHtml(user.nickname)}</td>
                                            <td><span class="badge bg-info">${user.role}</span></td>
                                            <td>${user.is_active ? '<span class="badge bg-success">启用</span>' : '<span class="badge bg-danger">禁用</span>'}</td>
                                            <td>${new Date(user.created_at).toLocaleDateString('zh-CN')}</td>
                                            <td>${user.last_login ? new Date(user.last_login).toLocaleString('zh-CN') : '-'}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                            <div class="text-center text-muted">
                                <small>共 ${data.data.total} 个用户</small>
                            </div>
                        `;
                        document.getElementById('users-container').innerHTML = html;
                    }
                });
        }

        // 加载公告
        function loadAnnouncement() {
            fetch('/api/announcement/get.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('announcement-content').value = data.data.content;
                    }
                });
        }

        // 处理公告表单提交
        document.getElementById('announcement-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();

            const content = document.getElementById('announcement-content').value;

            const response = await fetch('/api/announcement/update.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ content })
            });

            const data = await response.json();
            const msgEl = document.getElementById('announcement-message');

            if (data.success) {
                msgEl.innerHTML = '<div class="alert alert-success">公告已保存</div>';
            } else {
                msgEl.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }

            setTimeout(() => {
                msgEl.innerHTML = '';
            }, 3000);
        });

        // HTML转义
        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        // 页面加载时初始化
        window.addEventListener('load', loadDashboard);
    </script>
</body>
</html>
