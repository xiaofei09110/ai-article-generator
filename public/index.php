<?php
/**
 * AI文章生成系统主页
 * 从index.html改造为PHP，加入用户认证和CSRF Token
 */

require_once '../src/bootstrap.php';

// 获取当前用户（如果已登录）
$currentUser = Auth::getCurrentUser();

// 生成CSRF Token
$csrfToken = CSRF::getToken();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI文章生成与评分系统</title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- 自定义CSS -->
    <link rel="stylesheet" href="css/styles.css">
    <!-- Font Awesome图标 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar-user { margin-left: auto; }
        .navbar-user .dropdown-toggle::after { margin-left: 5px; }
    </style>
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
                <ul class="navbar-nav navbar-user">
                    <?php if ($currentUser): ?>
                        <!-- 已登录用户菜单 -->
                        <li class="nav-item">
                            <a class="nav-link" href="/topic-generator.php">
                                <i class="fas fa-lightbulb"></i> 话题生成器
                            </a>
                        </li>
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
                        <!-- 未登录用户菜单 -->
                        <li class="nav-item">
                            <a class="nav-link" href="/topic-generator.php">
                                <i class="fas fa-lightbulb"></i> 话题生成器
                            </a>
                        </li>
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
            <h1 class="display-4">AI文章生成与评分系统</h1>
            <p class="lead">输入您的需求，AI将为您生成高质量文章</p>
            <?php if (!$currentUser): ?>
                <p class="text-muted">
                    <i class="fas fa-info-circle"></i>
                    <a href="/login.php">登录</a>后可保存文章历史记录
                </p>
            <?php endif; ?>
        </header>

        <!-- 文章生成表单 -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h3 class="card-title mb-0"><i class="fas fa-pen-fancy me-2"></i>文章生成</h3>
                    </div>
                    <div class="card-body">
                        <form id="article-form">
                            <div class="mb-3">
                                <label for="title" class="form-label">文章标题</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="title" required>
                                    <a href="/topic-generator.php" class="btn btn-outline-secondary" id="generate-topic-btn">
                                        <i class="fas fa-lightbulb me-1"></i>生成话题
                                    </a>
                                </div>
                                <div class="form-text text-muted">想不到好标题？点击"生成话题"按钮获取灵感</div>
                            </div>
                            <div class="mb-3">
                                <label for="content-requirements" class="form-label">内容需求</label>
                                <div class="input-group">
                                    <textarea class="form-control" id="content-requirements" rows="3" required placeholder="例如：帮我写一篇关于AI技术如何提升工作效率的文章，要列举具体的工具案例。"></textarea>
                                    <button type="button" class="btn btn-outline-primary" id="polish-btn">
                                        <i class="fas fa-magic me-1"></i>AI润色
                                    </button>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="word-limit" class="form-label">字数限制</label>
                                    <input type="number" class="form-control" id="word-limit" min="100" max="10000" value="800" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="style" class="form-label">输出风格</label>
                                    <select class="form-select" id="style" required>
                                        <option value="formal">正式</option>
                                        <option value="casual">轻松</option>
                                        <option value="tech">科技感</option>
                                        <option value="professional">专业</option>
                                        <option value="creative">创意</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="output-format" class="form-label">输出场景格式</label>
                                <select class="form-select" id="output-format" required>
                                    <option value="default">默认格式</option>
                                    <option value="work-report">工作报表（严谨正式格式）</option>
                                    <option value="xiaohongshu">小红书文案（活泼、有趣、吸睛风格）</option>
                                    <option value="wechat">微信公众号文章（适合微信公众号传播的排版和风格）</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="api-source" class="form-label">选择AI接口</label>
                                <select class="form-select" id="api-source" required>
                                    <option value="ailion">通用AI模型</option>
                                    <option value="deepseek">DeepSeek 大模型</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" id="generate-btn">
                                <i class="fas fa-magic me-2"></i>生成文章
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 加载提示 -->
                <div id="loading" class="text-center my-4 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">加载中...</span>
                    </div>
                    <p class="mt-2">AI正在努力创作中，请稍候...</p>
                </div>

                <!-- 文章结果 -->
                <div id="result-section" class="card shadow-sm mb-4 d-none">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0"><i class="fas fa-file-alt me-2"></i>生成结果</h3>
                        <div>
                            <button id="copy-btn" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-copy me-1"></i>复制
                            </button>
                            <button id="download-btn" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-download me-1"></i>下载
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="article-content" class="border p-3 rounded mb-3" style="min-height: 200px; white-space: pre-wrap; word-wrap: break-word;"></div>

                        <!-- 评分系统 -->
                        <div class="border-top pt-3 mt-3">
                            <h5><i class="fas fa-star me-2"></i>为文章质量评分</h5>
                            <div class="d-flex align-items-center my-2">
                                <div id="rating" class="rating">
                                    <i class="far fa-star rating-star" data-rating="1" style="cursor: pointer; margin-right: 10px;"></i>
                                    <i class="far fa-star rating-star" data-rating="2" style="cursor: pointer; margin-right: 10px;"></i>
                                    <i class="far fa-star rating-star" data-rating="3" style="cursor: pointer; margin-right: 10px;"></i>
                                    <i class="far fa-star rating-star" data-rating="4" style="cursor: pointer; margin-right: 10px;"></i>
                                    <i class="far fa-star rating-star" data-rating="5" style="cursor: pointer;"></i>
                                </div>
                                <button id="submit-rating" class="btn btn-primary ms-3" disabled>提交评分</button>
                            </div>
                            <div id="rating-message" class="mt-2 text-muted"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 页脚 -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p>© 2024 四川粒子通识网络科技有限公司 <a href="https://liztongshi.cn" target="_blank">liztongshi.cn</a></p>
            <p class="text-muted small">本项目由粒子通识科技开发，版权所有。</p>
        </div>
    </footer>

    <!-- 隐私协议弹窗 -->
    <div class="modal fade" id="privacy-modal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">欢迎使用AI文章生成系统</h5>
                </div>
                <div class="modal-body">
                    <h6>网站介绍</h6>
                    <p>本网站提供AI驱动的文章生成服务，通过先进的人工智能技术，根据您的需求生成高质量文章内容。</p>

                    <h6>用户隐私协议</h6>
                    <p>我们重视您的隐私保护。使用本服务时，我们会收集：</p>
                    <ul>
                        <li>您输入的文章需求信息</li>
                        <li>您对生成文章的评分数据</li>
                    </ul>
                    <p>我们承诺：</p>
                    <ul>
                        <li>不会分享您的个人信息给任何第三方</li>
                        <li>只使用收集的数据来改进我们的服务</li>
                        <li>采取合理措施保护您的数据安全</li>
                    </ul>

                    <h6>开发团队信息</h6>
                    <p>本网站由<strong>四川粒子通识网络科技有限公司</strong>开发维护</p>
                    <p>官方网站：<a href="https://liztongshi.cn" target="_blank">liztongshi.cn</a></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary w-100" id="accept-privacy">同意并继续</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- 认证脚本库 -->
    <script src="js/auth.js"></script>
    <!-- 自定义JS -->
    <script src="js/main.js"></script>
    <script>
        // 更新fetch路径为新的API端点
        document.addEventListener('DOMContentLoaded', function() {
            // 获取CSRF Token（来自meta标签，由PHP注入）
            window.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            // 登出函数
            window.logout = function() {
                if (confirm('确定要退出登录吗？')) {
                    logoutUser().then(() => {
                        location.href = '/';
                    });
                }
            };
        });

        // 覆盖loadAnnouncement函数，更新API路径
        const originalLoadAnnouncement = window.loadAnnouncement;
        window.loadAnnouncement = function() {
            fetch('/api/announcement/get.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.content) {
                        document.getElementById('announcement-text').textContent = data.data.content;
                    } else {
                        document.getElementById('announcement').classList.add('d-none');
                    }
                })
                .catch(error => {
                    console.error('加载公告失败:', error);
                    document.getElementById('announcement').classList.add('d-none');
                });
        };

        // 覆盖handleFormSubmit函数，更新API路径和CSRF Token
        const originalHandleFormSubmit = window.handleFormSubmit;
        window.handleFormSubmit = function(event) {
            event.preventDefault();

            document.getElementById('loading').classList.remove('d-none');
            document.getElementById('result-section').classList.add('d-none');

            const title = document.getElementById('title').value;
            const requirements = document.getElementById('content-requirements').value;
            const wordLimit = document.getElementById('word-limit').value;
            const style = document.getElementById('style').value;
            const outputFormat = document.getElementById('output-format').value;
            const apiSource = document.getElementById('api-source').value;

            const requestData = {
                title: title,
                requirements: requirements,
                wordLimit: parseInt(wordLimit),
                style: style,
                outputFormat: outputFormat,
                apiSource: apiSource,
                _csrf: window.csrfToken
            };

            fetch('/api/article/generate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.csrfToken
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').classList.add('d-none');

                if (data.success) {
                    const cleanedContent = cleanMarkdownFormat(data.data.content, apiSource);
                    document.getElementById('result-section').classList.remove('d-none');
                    document.getElementById('article-content').textContent = cleanedContent;
                    resetRating();

                    // 保存article_id用于评分
                    window.currentArticleId = data.data.article_id;
                    localStorage.setItem('current_article_title', title);
                    document.getElementById('result-section').scrollIntoView({ behavior: 'smooth' });
                } else {
                    showError(data.message || '生成失败');
                }
            })
            .catch(error => {
                console.error('错误:', error);
                document.getElementById('loading').classList.add('d-none');
                showError('生成文章失败，请稍后重试。');
            });
        };

        // 覆盖setupRatingSystem函数，更新评分API
        const originalSetupRatingSystem = window.setupRatingSystem;
        window.setupRatingSystem = function() {
            const stars = document.querySelectorAll('.rating-star');
            const submitRatingBtn = document.getElementById('submit-rating');
            let currentRating = 0;

            stars.forEach(star => {
                star.addEventListener('click', () => {
                    const rating = parseInt(star.getAttribute('data-rating'));
                    currentRating = rating;

                    stars.forEach(s => {
                        const starRating = parseInt(s.getAttribute('data-rating'));
                        if (starRating <= rating) {
                            s.classList.remove('far');
                            s.classList.add('fas');
                        } else {
                            s.classList.remove('fas');
                            s.classList.add('far');
                        }
                    });

                    submitRatingBtn.disabled = false;
                });
            });

            submitRatingBtn.addEventListener('click', () => {
                if (currentRating === 0) return;

                const ratingData = {
                    article_id: window.currentArticleId || 0,
                    rating: currentRating,
                    comment: '',
                    _csrf: window.csrfToken
                };

                fetch('/api/rating/submit.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': window.csrfToken
                    },
                    body: JSON.stringify(ratingData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('rating-message').textContent = data.message || '感谢您的评分！';
                        submitRatingBtn.disabled = true;
                    } else {
                        document.getElementById('rating-message').textContent = data.message || '评分提交失败';
                    }
                })
                .catch(error => {
                    console.error('错误:', error);
                    document.getElementById('rating-message').textContent = '评分提交失败';
                });
            });
        };

        // 覆盖polishRequirements函数，更新API路径
        const originalPolishRequirements = window.polishRequirements;
        window.polishRequirements = function() {
            const requirementsInput = document.getElementById('content-requirements');
            const requirements = requirementsInput.value.trim();

            if (!requirements) {
                showError('内容需求不能为空');
                return;
            }

            const polishBtn = document.getElementById('polish-btn');
            const originalText = polishBtn.innerHTML;
            polishBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>润色中...';
            polishBtn.disabled = true;

            fetch('/api/polish/requirements.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.csrfToken
                },
                body: JSON.stringify({ requirements: requirements })
            })
            .then(response => response.json())
            .then(data => {
                polishBtn.innerHTML = originalText;
                polishBtn.disabled = false;

                if (data.success) {
                    const polishedContent = cleanPolishOutput(data.data.polished_requirements);
                    requirementsInput.value = polishedContent;
                    requirementsInput.classList.add('highlight');
                    setTimeout(() => {
                        requirementsInput.classList.remove('highlight');
                    }, 2000);
                } else {
                    showError(data.message || '润色失败');
                }
            })
            .catch(error => {
                console.error('错误:', error);
                polishBtn.innerHTML = originalText;
                polishBtn.disabled = false;
                showError('润色失败，请稍后重试');
            });
        };
    </script>
</body>
</html>
