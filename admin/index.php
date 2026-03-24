<?php
/**
 * 管理后台 - 公告管理
 * 四川粒子通识网络科技有限公司
 */

// 设置中国时区
date_default_timezone_set('Asia/Shanghai');

// 启动会话
session_start();

// 检查是否已登录
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // 未登录，重定向到登录页面
    header('Location: login.php');
    exit;
}

// 处理退出登录
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// 公告文件路径
$announcementFile = '../data/announcement.txt';

// 保存公告
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement'])) {
    // 确保数据目录存在
    if (!file_exists('../data')) {
        mkdir('../data', 0755, true);
    }
    
    $newAnnouncement = trim($_POST['announcement']);
    file_put_contents($announcementFile, $newAnnouncement);
    $message = '公告已成功更新！';
}

// 获取当前公告
$currentAnnouncement = '';
if (file_exists($announcementFile)) {
    $currentAnnouncement = file_get_contents($announcementFile);
}

// 获取评分数据
$ratingsFile = '../data/ratings.json';
$ratings = [];
if (file_exists($ratingsFile)) {
    $ratingsJson = file_get_contents($ratingsFile);
    if (!empty($ratingsJson)) {
        $ratings = json_decode($ratingsJson, true);
    }
}

// 计算平均评分
$averageRating = 0;
$ratingCount = count($ratings);
if ($ratingCount > 0) {
    $totalRating = 0;
    foreach ($ratings as $rating) {
        $totalRating += $rating['rating'];
    }
    $averageRating = round($totalRating / $ratingCount, 1);
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI文章生成与评分系统 - 管理后台</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome图标 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border-radius: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        .card-header {
            background-color: #4361ee;
            color: white;
            border-radius: 0.5rem 0.5rem 0 0 !important;
        }
        footer {
            border-top: 1px solid #eee;
            padding: 1rem 0;
            margin-top: 2rem;
        }
        .admin-header {
            background-color: #4361ee;
            color: white;
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        .admin-header .btn-outline-light {
            border-color: rgba(255, 255, 255, 0.5);
        }
        .admin-header .btn-outline-light:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <!-- 管理后台头部 -->
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">AI文章生成与评分系统 - 管理后台</h1>
                <div>
                    <span class="me-3">
                        <i class="fas fa-user me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
                    </span>
                    <a href="?logout=1" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>退出登录
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container py-4">
        <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- 公告管理 -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bullhorn me-2"></i>公告管理</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="announcement" class="form-label">公告内容</label>
                                <textarea class="form-control" id="announcement" name="announcement" rows="4"><?php echo htmlspecialchars($currentAnnouncement); ?></textarea>
                                <div class="form-text">此内容将显示在网站顶部公告栏。留空则不显示公告。</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>保存公告
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-simple me-2"></i>数据统计</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="text-center p-3">
                                    <h3><?php echo $ratingCount; ?></h3>
                                    <p class="text-muted mb-0">评分总数</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3">
                                    <h3><?php echo $averageRating; ?> <small><i class="fas fa-star text-warning"></i></small></h3>
                                    <p class="text-muted mb-0">平均评分</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 评分记录 -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-star me-2"></i>评分记录</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($ratings)): ?>
                            <div class="text-center p-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-3"></i>
                                <p>暂无评分记录</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>文章标题</th>
                                            <th>评分</th>
                                            <th>评分时间</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_reverse($ratings) as $index => $item): ?>
                                            <?php if ($index < 10): // 只显示最近10条 ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                                                    <td>
                                                        <?php 
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            echo $i <= $item['rating'] 
                                                                ? '<i class="fas fa-star text-warning"></i>' 
                                                                : '<i class="far fa-star text-muted"></i>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($item['rating_time']); ?></td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($ratings) > 10): ?>
                                <div class="text-center p-2">
                                    <small class="text-muted">显示最近10条记录（共<?php echo count($ratings); ?>条）</small>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="../index.html" class="btn btn-outline-primary">
                        <i class="fas fa-home me-2"></i>返回网站首页
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="text-center text-muted">
        <div class="container">
            <p>© 2024 四川粒子通识网络科技有限公司 <a href="https://liztongshi.cn" target="_blank">liztongshi.cn</a></p>
            <p class="small">本项目由粒子通识科技开发，版权所有。</p>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 