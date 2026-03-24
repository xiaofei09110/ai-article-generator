/**
 * AI文章生成与评分系统 - 主脚本
 * 四川粒子通识网络科技有限公司
 */

document.addEventListener('DOMContentLoaded', function() {
    // 检查是否首次访问，显示隐私协议
    checkFirstVisit();
    
    // 加载公告
    loadAnnouncement();
    
    // 表单提交事件
    const articleForm = document.getElementById('article-form');
    articleForm.addEventListener('submit', handleFormSubmit);
    
    // 评分星星点击事件
    setupRatingSystem();
    
    // 复制按钮事件
    document.getElementById('copy-btn').addEventListener('click', copyArticleContent);
    
    // 下载按钮事件
    document.getElementById('download-btn').addEventListener('click', downloadArticleContent);
    
    // AI润色按钮事件
    document.getElementById('polish-btn').addEventListener('click', polishRequirements);
    
    // 检查是否有从话题生成器传递的标题
    checkForSelectedTopic();
});

/**
 * 检查是否首次访问，显示隐私协议
 */
function checkFirstVisit() {
    if (!localStorage.getItem('privacy_accepted')) {
        const privacyModal = new bootstrap.Modal(document.getElementById('privacy-modal'));
        privacyModal.show();
        
        document.getElementById('accept-privacy').addEventListener('click', function() {
            localStorage.setItem('privacy_accepted', 'true');
            privacyModal.hide();
        });
    }
}

/**
 * 检查是否有从话题生成器传递的标题
 */
function checkForSelectedTopic() {
    const selectedTopic = localStorage.getItem('selected_topic');
    if (selectedTopic) {
        // 填充标题输入框
        document.getElementById('title').value = selectedTopic;
        
        // 高亮显示输入框
        const titleInput = document.getElementById('title');
        titleInput.classList.add('highlight');
        setTimeout(() => {
            titleInput.classList.remove('highlight');
        }, 2000);
        
        // 清除localStorage中的选择话题
        localStorage.removeItem('selected_topic');
    }
}

/**
 * 加载公告内容
 */
function loadAnnouncement() {
    fetch('php/announcement.php')
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                document.getElementById('announcement-text').textContent = data.message;
            } else {
                document.getElementById('announcement').classList.add('d-none');
            }
        })
        .catch(error => {
            console.error('加载公告失败:', error);
            document.getElementById('announcement').classList.add('d-none');
        });
}

/**
 * 处理表单提交
 * @param {Event} event - 表单提交事件
 */
function handleFormSubmit(event) {
    event.preventDefault();
    
    // 显示加载中
    document.getElementById('loading').classList.remove('d-none');
    document.getElementById('result-section').classList.add('d-none');
    
    // 获取表单数据
    const title = document.getElementById('title').value;
    const requirements = document.getElementById('content-requirements').value;
    const wordLimit = document.getElementById('word-limit').value;
    const style = document.getElementById('style').value;
    const apiSource = document.getElementById('api-source').value;
    
    // 准备请求数据
    const requestData = {
        title: title,
        requirements: requirements,
        wordLimit: wordLimit,
        style: style,
        apiSource: apiSource
    };
    
    // 发送到后端
    fetch('php/generate.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('网络响应不正常');
        }
        return response.json();
    })
    .then(data => {
        // 隐藏加载中
        document.getElementById('loading').classList.add('d-none');
        
        if (data.error) {
            showError(data.error);
            return;
        }
        
        // 清理AI输出的Markdown格式（新增功能）
        const cleanedContent = cleanMarkdownFormat(data.content, apiSource);
        
        // 显示结果
        document.getElementById('result-section').classList.remove('d-none');
        document.getElementById('article-content').textContent = cleanedContent;
        
        // 重置评分
        resetRating();
        
        // 保存当前文章信息用于评分
        localStorage.setItem('current_article_title', title);
        
        // 平滑滚动到结果区域
        document.getElementById('result-section').scrollIntoView({ behavior: 'smooth' });
    })
    .catch(error => {
        console.error('生成文章失败:', error);
        document.getElementById('loading').classList.add('d-none');
        showError('生成文章失败，请稍后重试。');
    });
}

/**
 * 清理Markdown格式，去除标题符号和加粗符号
 * @param {string} content - 原始内容
 * @param {string} apiSource - API来源
 * @return {string} 清理后的内容
 */
function cleanMarkdownFormat(content, apiSource) {
    if (!content) return '';
    
    // 1. 移除标题符号 (#, ##, ###)
    let cleaned = content.replace(/^#+\s+/gm, '');
    
    // 2. 移除加粗符号 (**text**)
    cleaned = cleaned.replace(/\*\*(.*?)\*\*/g, '$1');
    
    // 3. 移除其他常见的Markdown符号，如斜体、代码块等
    cleaned = cleaned.replace(/\*(.*?)\*/g, '$1');  // 斜体
    cleaned = cleaned.replace(/`([^`]+)`/g, '$1');  // 行内代码
    
    return cleaned;
}

/**
 * 显示错误消息
 * @param {string} message - 错误信息
 */
function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-danger alert-dismissible fade show';
    errorDiv.innerHTML = `
        <strong>错误：</strong> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(errorDiv, container.firstChild);
    
    // 5秒后自动消失
    setTimeout(() => {
        errorDiv.classList.add('fade');
        setTimeout(() => errorDiv.remove(), 500);
    }, 5000);
}

/**
 * 设置评分系统
 */
function setupRatingSystem() {
    const stars = document.querySelectorAll('.rating-star');
    const submitRatingBtn = document.getElementById('submit-rating');
    let currentRating = 0;
    
    stars.forEach(star => {
        star.addEventListener('click', () => {
            const rating = parseInt(star.getAttribute('data-rating'));
            currentRating = rating;
            
            // 更新星星显示
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
            
            // 启用提交按钮
            submitRatingBtn.disabled = false;
        });
    });
    
    // 提交评分
    submitRatingBtn.addEventListener('click', () => {
        if (currentRating === 0) return;
        
        const articleTitle = localStorage.getItem('current_article_title') || '未命名文章';
        
        // 准备评分数据
        const ratingData = {
            title: articleTitle,
            rating: currentRating,
            time: new Date().toISOString()
        };
        
        // 发送评分到后端
        fetch('php/rating.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(ratingData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('rating-message').textContent = '感谢您的评分！';
                submitRatingBtn.disabled = true;
            } else {
                document.getElementById('rating-message').textContent = '评分提交失败，请稍后重试。';
            }
        })
        .catch(error => {
            console.error('提交评分失败:', error);
            document.getElementById('rating-message').textContent = '评分提交失败，请稍后重试。';
        });
    });
}

/**
 * 重置评分
 */
function resetRating() {
    const stars = document.querySelectorAll('.rating-star');
    stars.forEach(star => {
        star.classList.remove('fas');
        star.classList.add('far');
    });
    
    document.getElementById('submit-rating').disabled = true;
    document.getElementById('rating-message').textContent = '';
}

/**
 * 复制文章内容到剪贴板
 */
function copyArticleContent() {
    const content = document.getElementById('article-content').textContent;
    
    // 使用Clipboard API
    navigator.clipboard.writeText(content)
        .then(() => {
            // 显示成功提示
            const copyBtn = document.getElementById('copy-btn');
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check me-1"></i>已复制';
            
            setTimeout(() => {
                copyBtn.innerHTML = originalText;
            }, 2000);
        })
        .catch(err => {
            console.error('复制失败:', err);
            showError('复制失败，请手动选择文本复制。');
        });
}

/**
 * 下载文章内容为txt文件
 */
function downloadArticleContent() {
    const content = document.getElementById('article-content').textContent;
    const title = localStorage.getItem('current_article_title') || '未命名文章';
    const fileName = `${title}.txt`;
    
    // 创建下载链接
    const element = document.createElement('a');
    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
    element.setAttribute('download', fileName);
    
    // 模拟点击下载
    element.style.display = 'none';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}

/**
 * 润色内容需求
 */
function polishRequirements() {
    // 获取内容需求
    const requirementsInput = document.getElementById('content-requirements');
    const requirements = requirementsInput.value.trim();
    
    // 验证输入
    if (!requirements) {
        showError('内容需求不能为空');
        return;
    }
    
    // 显示加载状态
    const polishBtn = document.getElementById('polish-btn');
    const originalText = polishBtn.innerHTML;
    polishBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>润色中...';
    polishBtn.disabled = true;
    
    // 发送请求到后端
    fetch('php/polish_requirements.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ requirements: requirements })
    })
    .then(response => {
        // 检查HTTP状态码
        if (response.status === 403) {
            throw new Error('服务器拒绝访问，请检查文件权限');
        } else if (response.status === 404) {
            throw new Error('找不到润色服务，请检查文件路径');
        } else if (!response.ok) {
            throw new Error('服务器响应错误: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        // 恢复按钮状态
        polishBtn.innerHTML = originalText;
        polishBtn.disabled = false;
        
        if (data.success) {
            // 清理Deepseek返回的开头提示和结尾总结（新增功能）
            const polishedContent = cleanPolishOutput(data.polished_requirements);
            
            // 更新输入框内容
            requirementsInput.value = polishedContent;
            
            // 添加视觉反馈
            requirementsInput.classList.add('highlight');
            setTimeout(() => {
                requirementsInput.classList.remove('highlight');
            }, 2000);
        } else {
            showError(data.error || '润色失败，请稍后重试');
        }
    })
    .catch(error => {
        console.error('润色失败:', error);
        // 恢复按钮状态
        polishBtn.innerHTML = originalText;
        polishBtn.disabled = false;
        showError(error.message || '润色失败，请稍后重试');
    });
}

/**
 * 清理Deepseek润色输出的开头提示和结尾总结
 * @param {string} content - 原始润色内容
 * @return {string} 清理后的内容
 */
function cleanPolishOutput(content) {
    if (!content) return '';
    
    // 识别常见的开头提示语
    const startPatterns = [
        /^以下是优化后的文章需求描述[：:]/i,
        /^我已经优化了你的文章需求[，,]/i,
        /^这是优化后的需求描述[：:]/i,
        /^优化后的需求如下[：:]/i
    ];
    
    // 识别常见的结尾总结语
    const endPatterns = [
        /这个优化后的需求描述[^]*?$/i,
        /以上是优化后的内容[^]*?$/i,
        /希望这个优化后的描述[^]*?$/i,
        /总结[：:].*$/i,
        /这样的描述更加[^]*?$/i
    ];
    
    let cleanedContent = content;
    
    // 删除开头提示
    for (const pattern of startPatterns) {
        cleanedContent = cleanedContent.replace(pattern, '');
    }
    
    // 删除结尾总结
    for (const pattern of endPatterns) {
        cleanedContent = cleanedContent.replace(pattern, '');
    }
    
    // 删除多余的空行和空格
    cleanedContent = cleanedContent.trim();
    
    return cleanedContent;
} 