<?php
header('Content-Type: application/json');

// 获取POST数据
$data = json_decode(file_get_contents('php://input'), true);
$article = $data['article'] ?? '';
$format = $data['format'] ?? 'markdown';

if (empty($article)) {
    echo json_encode(['success' => false, 'error' => '文章内容不能为空']);
    exit;
}

// 根据格式处理文章
$formatted_article = formatArticle($article, $format);

if ($formatted_article) {
    echo json_encode(['success' => true, 'formatted_article' => $formatted_article]);
} else {
    echo json_encode(['success' => false, 'error' => '格式化失败，请稍后重试']);
}

// 格式化文章
function formatArticle($article, $format) {
    switch ($format) {
        case 'markdown':
            return formatAsMarkdown($article);
        case 'html':
            return formatAsHTML($article);
        case 'text':
            return formatAsText($article);
        default:
            return null;
    }
}

// 格式化为Markdown
function formatAsMarkdown($article) {
    // 基本的Markdown格式化
    $formatted = $article;
    
    // 添加标题
    $formatted = "# " . $formatted;
    
    // 添加段落分隔
    $formatted = preg_replace('/\n\n+/', "\n\n", $formatted);
    
    // 添加列表标记
    $formatted = preg_replace('/^\s*[-•]\s*/m', "- ", $formatted);
    
    return $formatted;
}

// 格式化为HTML
function formatAsHTML($article) {
    // 基本的HTML格式化
    $formatted = htmlspecialchars($article, ENT_QUOTES, 'UTF-8');
    
    // 添加HTML标签
    $formatted = "<article>\n<h1>" . $formatted . "</h1>\n";
    
    // 处理段落
    $formatted = preg_replace('/\n\n+/', "</p>\n<p>", $formatted);
    
    // 处理列表
    $formatted = preg_replace('/^\s*[-•]\s*/m', "<li>", $formatted);
    $formatted = preg_replace('/\n<li>/', "</li>\n<li>", $formatted);
    
    $formatted .= "\n</article>";
    
    return $formatted;
}

// 格式化为纯文本
function formatAsText($article) {
    // 基本的文本格式化
    $formatted = strip_tags($article);
    
    // 规范化换行
    $formatted = preg_replace('/\n\n+/', "\n\n", $formatted);
    
    // 移除多余空格
    $formatted = preg_replace('/\s+/', ' ', $formatted);
    
    return trim($formatted);
} 