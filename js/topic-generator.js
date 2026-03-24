/**
 * 行业话题生成器 - 前端脚本
 * 四川粒子通识网络科技有限公司
 */

document.addEventListener('DOMContentLoaded', function() {
    // 初始化Select2
    initSelect2();
    
    // 加载公告
    loadAnnouncement();
    
    // 表单提交事件
    const topicForm = document.getElementById('topic-form');
    topicForm.addEventListener('submit', handleFormSubmit);
});

/**
 * 初始化Select2下拉框
 */
function initSelect2() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: '请选择或输入行业...',
        allowClear: true,
        tags: true // 允许自由输入
    });
}

/**
 * 加载公告内容
 */
function loadAnnouncement() {
    fetch('/php/announcement.php')
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
    document.getElementById('topics-section').classList.add('d-none');
    
    // 获取表单数据
    const industry = document.getElementById('industry').value;
    const count = document.getElementById('topic-count').value;
    
    if (!industry) {
        showError('请选择或输入行业');
        document.getElementById('loading').classList.add('d-none');
        return;
    }
    
    // 准备请求数据
    const requestData = {
        industry: industry,
        count: count
    };
    
    // 发送到后端
    fetch('/php/generate_topics.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        // 检查HTTP状态码
        if (response.status === 403) {
            throw new Error('服务器拒绝访问，请检查文件权限');
        } else if (response.status === 404) {
            throw new Error('找不到生成话题的服务，请检查文件路径');
        } else if (!response.ok) {
            throw new Error('服务器响应错误: ' + response.status);
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
        
        // 前端再次清理话题内容，确保展示内容纯净
        const cleanedTopics = data.topics.map(cleanTopicText);
        
        // 显示结果
        displayTopics(cleanedTopics, industry);
        
        // 平滑滚动到结果区域
        document.getElementById('topics-section').scrollIntoView({ behavior: 'smooth' });
    })
    .catch(error => {
        console.error('生成话题失败:', error);
        document.getElementById('loading').classList.add('d-none');
        showError(error.message || '生成话题失败，请稍后重试');
    });
}

/**
 * 清理话题文本，确保前端显示内容纯净
 * @param {string} topic - 话题原文
 * @return {string} 清理后的话题
 */
function cleanTopicText(topic) {
    if (!topic) return '';
    
    // 移除可能的Markdown格式
    let cleaned = topic
        .replace(/^#+\s+/gm, '') // 移除标题符号
        .replace(/\*\*(.*?)\*\*/g, '$1') // 移除加粗
        .replace(/\*(.*?)\*/g, '$1') // 移除斜体
        .replace(/`([^`]+)`/g, '$1') // 移除代码格式
        .replace(/^\d+[\.\、]\s*/gm, '') // 移除序号
        .replace(/^\-\s*/gm, ''); // 移除列表符号
    
    // 移除多余的空格和换行
    cleaned = cleaned.trim();
    
    return cleaned;
}

/**
 * 显示生成的话题列表
 * @param {Array} topics - 话题列表
 * @param {string} industry - 行业名称
 */
function displayTopics(topics, industry) {
    const container = document.getElementById('topics-container');
    container.innerHTML = '';
    
    if (!topics || topics.length === 0) {
        container.innerHTML = '<div class="alert alert-warning">没有找到相关话题，请尝试其他行业。</div>';
        document.getElementById('topics-section').classList.remove('d-none');
        return;
    }
    
    // 生成话题卡片
    topics.forEach((topic, index) => {
        const topicCard = document.createElement('div');
        topicCard.className = 'topic-card';
        
        topicCard.innerHTML = `
            <div class="topic-content">${topic}</div>
            <div class="topic-actions">
                <button class="btn copy-btn" data-topic="${encodeURIComponent(topic)}">
                    <i class="fas fa-copy me-1"></i>复制
                </button>
                <button class="btn use-btn" data-topic="${encodeURIComponent(topic)}">
                    <i class="fas fa-pen-nib me-1"></i>使用话题生成文章
                </button>
            </div>
        `;
        
        container.appendChild(topicCard);
    });
    
    // 显示话题区域
    document.getElementById('topics-section').classList.remove('d-none');
    
    // 绑定按钮事件
    attachTopicButtonEvents();
}

/**
 * 绑定话题卡片上的按钮事件
 */
function attachTopicButtonEvents() {
    // 复制按钮
    const copyButtons = document.querySelectorAll('.copy-btn');
    copyButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const topic = decodeURIComponent(this.getAttribute('data-topic'));
            copyToClipboard(topic);
            
            // 显示复制成功提示
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check me-1"></i>已复制';
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 2000);
        });
    });
    
    // 使用话题按钮
    const useButtons = document.querySelectorAll('.use-btn');
    useButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const topic = decodeURIComponent(this.getAttribute('data-topic'));
            
            // 将话题存储到localStorage，以便在文章生成页面使用
            localStorage.setItem('selected_topic', topic);
            
            // 跳转到文章生成页面
            window.location.href = 'index.html';
        });
    });
}

/**
 * 复制文本到剪贴板
 * @param {string} text - 要复制的文本
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text)
        .catch(err => {
            console.error('复制失败:', err);
            
            // 回退方法
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        });
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