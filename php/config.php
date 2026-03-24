<?php
/**
 * API配置文件
 * 四川粒子通识网络科技有限公司
 * 
 * 安全说明：
 * 此文件包含敏感的API密钥信息，应妥善保护
 * 1. 确保此文件不被公开访问
 * 2. 通过服务器配置限制对此文件的访问
 * 3. 定期更新API密钥
 */

// DeepSeek API配置
return [
    'deepseek' => [
        'api_key' => 'sk-1977cad2e2794c5bb65664fb7301a04c',
        'api_base_url' => 'https://api.deepseek.com',
        'model' => 'deepseek-chat',
        'temperature' => 0.8,
        'max_tokens' => 1000
    ],
    
    // 其他API配置可在此添加
    'ailion' => [
        'api_key' => 'sk-s7MuNA41JTsF5XexGxgIf7cmSK55klRk8zuKqJGDv1UxATwx',
        'api_base_url' => 'https://api.ailion.top',
        'model' => 'gpt-3.5-turbo'
    ],
    
    // 话题生成器配置
    'topic_generator' => [
        'min_count' => 5,         // 最少生成话题数量
        'max_count' => 10,        // 最多生成话题数量
        'min_topic_length' => 10, // 话题最小字符数
        'max_topic_length' => 30, // 话题最大字符数
        'request_limit' => 5,     // 每分钟请求限制
        'request_window' => 60    // 请求窗口时间(秒)
    ]
]; 