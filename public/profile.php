<?php
/**
 * 用户个人中心 - 文章历史和账号管理
 */

require_once '../src/bootstrap.php';

// 需要登录
Auth::requireLoginPage();

$currentUser = Auth::getCurrentUser();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// 获取用户的文章总数
$totalCount = Database::fetchColumn(
    'SELECT COUNT(*) FROM articles WHERE user_id = ?',
    [$currentUser['id']]
);

$totalPages = ceil($totalCount / $perPage);

// 获取当前页的文章
$articles = Database::fetchAll(
    'SELECT id, title, style, word_limit, api_source, generated_at
     FROM articles
     WHERE user_id = ?
     ORDER BY generated_at DESC
     LIMIT ? OFFSET ?',
    [$currentUser['id'], $perPage, $offset]
);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人中心 - AI文章生成系统</title>
    <meta name="csrf-token" content="<?php echo CSRF::getToken(); ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .navbar-user { margin-left: auto; }
        .user-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .article-card { transition: transform 0.3s; }
        .article-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .badge-style { font-size: 12px; padding: 5px 10px; }
        .pagination-custom { justify-content: center; margin-top: 30px; }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state-icon { font-size: 64px; color: #ddd; margin-bottom: 20px; }
        .btn-delete { padding: 5px 12px; font-size: 13px; }
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">
                <i class="fas fa-pen-fancy"></i> AI文章生成
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">
                            <i class="fas fa-home"></i> 首页
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($currentUser['nickname']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item active" href="/profile.php"><i class="fas fa-history"></i> 文章历史</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="logout(); return false;"><i class="fas fa-sign-out-alt"></i> 退出登录</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 主内容区 -->
    <div class="container mt-5">
        <!-- 用户信息卡 -->
        <div class="row mb-4">
            <div class="col-md-8 mx-auto">
                <div class="card user-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="mb-1"><i class="fas fa-user"></i> <?php echo htmlspecialchars($currentUser['nickname']); ?></h4>
                                <p class="mb-2">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($currentUser['email']); ?>
                                </p>
                                <small>
                                    <i class="fas fa-file-alt"></i> 已生成 <strong><?php echo $totalCount; ?></strong> 篇文章
                                </small>
                            </div>
                            <div>
                                <a href="/" class="btn btn-light btn-sm">
                                    <i class="fas fa-pen"></i> 继续创作
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 文章列表 -->
        <div class="row">
            <div class="col-md-8 mx-auto">
                <h3 class="mb-4"><i class="fas fa-history"></i> 我的文章历史</h3>

                <?php if (empty($articles)): ?>
                    <!-- 空状态 -->
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                        <h5>还没有生成过文章</h5>
                        <p class="text-muted">快去创作你的第一篇文章吧！</p>
                        <a href="/" class="btn btn-primary">去创作</a>
                    </div>
                <?php else: ?>
                    <!-- 文章卡片列表 -->
                    <?php foreach ($articles as $article): ?>
                        <div class="card article-card mb-3 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div style="flex: 1;">
                                        <h5 class="card-title mb-2">
                                            <i class="fas fa-file-alt text-primary"></i>
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </h5>
                                        <p class="card-text text-muted small mb-2">
                                            <i class="fas fa-clock"></i>
                                            <?php echo date('Y-m-d H:i', strtotime($article['generated_at'])); ?>
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge badge-style bg-primary me-2">
                                            <?php echo $article['word_limit']; ?>字
                                        </span>
                                        <span class="badge badge-style bg-info me-2">
                                            <?php
                                            $styleMap = [
                                                'formal' => '正式',
                                                'casual' => '轻松',
                                                'tech' => '科技感',
                                                'professional' => '专业',
                                                'creative' => '创意'
                                            ];
                                            echo $styleMap[$article['style']] ?? $article['style'];
                                            ?>
                                        </span>
                                        <span class="badge badge-style bg-success">
                                            <?php echo strtoupper($article['api_source']); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- 操作按钮 -->
                                <div class="mt-3 d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary btn-delete"
                                            onclick="viewArticle(<?php echo $article['id']; ?>)">
                                        <i class="fas fa-eye"></i> 查看
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-delete"
                                            onclick="deleteArticle(<?php echo $article['id']; ?>)">
                                        <i class="fas fa-trash"></i> 删除
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- 分页 -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="分页导航" class="pagination-custom">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1">首页</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">上一页</a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">下一页</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $totalPages; ?>">末页</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 查看文章模态框 -->
    <div class="modal fade" id="articleModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="articleTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="articleContent"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-primary" id="copyArticleBtn">
                        <i class="fas fa-copy"></i> 复制
                    </button>
                    <button type="button" class="btn btn-outline-success" id="downloadArticleBtn">
                        <i class="fas fa-download"></i> 下载
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/auth.js"></script>
    <script>
        let currentArticleId = null;
        let currentArticleData = null;

        /**
         * 查看文章
         */
        function viewArticle(articleId) {
            fetch(`/api/article/view.php?id=${articleId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentArticleId = articleId;
                        currentArticleData = data.data;

                        document.getElementById('articleTitle').textContent = data.data.title;
                        document.getElementById('articleContent').innerHTML =
                            '<pre style="white-space: pre-wrap; word-wrap: break-word;">' +
                            escapeHtml(data.data.content) +
                            '</pre>';

                        new bootstrap.Modal(document.getElementById('articleModal')).show();
                    } else {
                        alert('加载文章失败');
                    }
                })
                .catch(error => {
                    console.error('错误:', error);
                    alert('加载文章出错');
                });
        }

        /**
         * 删除文章
         */
        function deleteArticle(articleId) {
            if (!confirm('确定要删除这篇文章吗？删除后无法恢复。')) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            fetch('/api/article/delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({
                    article_id: articleId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('文章已删除');
                    location.reload();
                } else {
                    alert(data.message || '删除失败');
                }
            })
            .catch(error => {
                console.error('错误:', error);
                alert('删除出错');
            });
        }

        /**
         * 复制文章内容
         */
        document.getElementById('copyArticleBtn')?.addEventListener('click', function() {
            if (!currentArticleData) return;

            const text = currentArticleData.content;
            navigator.clipboard.writeText(text).then(() => {
                alert('已复制到剪贴板');
            }).catch(() => {
                alert('复制失败');
            });
        });

        /**
         * 下载文章
         */
        document.getElementById('downloadArticleBtn')?.addEventListener('click', function() {
            if (!currentArticleData) return;

            const element = document.createElement('a');
            element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(currentArticleData.content));
            element.setAttribute('download', currentArticleData.title + '.txt');
            element.style.display = 'none';
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
        });

        /**
         * 转义HTML
         */
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        /**
         * 登出
         */
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
