-- ============================================================
-- AI文章生成系统 数据库初始化脚本
-- 创建所有必要的表结构
-- ============================================================

-- 数据库字符集设置：支持UTF-8和emoji
-- 此库应该在初始化时由docker或脚本自动创建

-- ============================================================
-- 表1：用户表
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '用户ID',
    email       VARCHAR(255) NOT NULL UNIQUE COMMENT '邮箱（登录账号）',
    password    VARCHAR(255) NOT NULL COMMENT 'bcrypt 哈希密码',
    nickname    VARCHAR(100) NOT NULL DEFAULT '' COMMENT '用户昵称',
    role        ENUM('user', 'admin') NOT NULL DEFAULT 'user' COMMENT '用户角色',
    is_active   TINYINT(1) NOT NULL DEFAULT 1 COMMENT '账号是否激活',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    last_login  DATETIME NULL COMMENT '最后登录时间',
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户账号表';

-- ============================================================
-- 表2：文章记录表
-- ============================================================
CREATE TABLE IF NOT EXISTS articles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '文章ID',
    user_id         INT UNSIGNED NULL COMMENT '关联用户ID，NULL表示匿名生成',
    title           VARCHAR(500) NOT NULL COMMENT '文章标题',
    requirements    TEXT NOT NULL COMMENT '用户输入的生成需求描述',
    content         LONGTEXT NOT NULL COMMENT 'AI 生成的文章内容',
    word_limit      INT UNSIGNED NOT NULL DEFAULT 800 COMMENT '生成时的字数限制',
    style           VARCHAR(50) NOT NULL DEFAULT 'formal' COMMENT '文章风格（formal/casual/tech/professional/creative）',
    output_format   VARCHAR(50) NOT NULL DEFAULT 'default' COMMENT '输出场景格式（default/work-report/xiaohongshu/wechat）',
    api_source      VARCHAR(50) NOT NULL COMMENT 'AI来源（deepseek 或 ailion）',
    ip_address      VARCHAR(45) NULL COMMENT '生成时的IP地址（支持IPv6）',
    generated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '生成时间',
    INDEX idx_user_id (user_id),
    INDEX idx_generated_at (generated_at),
    INDEX idx_api_source (api_source),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章生成记录表';

-- ============================================================
-- 表3：评分表（合并原 rating.php 和 rate_article.php）
-- ============================================================
CREATE TABLE IF NOT EXISTS ratings (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '评分ID',
    article_id  INT UNSIGNED NOT NULL COMMENT '关联文章ID',
    user_id     INT UNSIGNED NULL COMMENT '评分用户ID，NULL表示匿名评分',
    rating      TINYINT UNSIGNED NOT NULL COMMENT '评分值（1-5星）',
    comment     TEXT NULL COMMENT '评分备注/评论',
    ip_address  VARCHAR(45) NULL COMMENT '评分时的IP地址',
    rated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '评分时间',
    CONSTRAINT chk_rating_range CHECK (rating BETWEEN 1 AND 5),
    INDEX idx_article_id (article_id),
    INDEX idx_user_id (user_id),
    INDEX idx_rated_at (rated_at),
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章评分表';

-- ============================================================
-- 表4：公告表
-- ============================================================
CREATE TABLE IF NOT EXISTS announcements (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '公告ID',
    content     TEXT NOT NULL COMMENT '公告正文内容',
    is_active   TINYINT(1) NOT NULL DEFAULT 1 COMMENT '是否启用此公告',
    created_by  INT UNSIGNED NULL COMMENT '创建此公告的管理员ID',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_is_active (is_active),
    INDEX idx_updated_at (updated_at),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='网站公告表';

-- ============================================================
-- 表5：API 限流记录表（替代原 /tmp 文件方案）
-- ============================================================
CREATE TABLE IF NOT EXISTS rate_limits (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT '记录ID',
    ip_address  VARCHAR(45) NOT NULL COMMENT '请求IP地址',
    endpoint    VARCHAR(100) NOT NULL COMMENT '接口标识（如 topic_generate）',
    request_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '请求时间',
    INDEX idx_ip_endpoint_time (ip_address, endpoint, request_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='API限流记录表（定期清理过期数据）';

-- ============================================================
-- 初始数据：默认公告
-- ============================================================
INSERT INTO announcements (content, is_active)
VALUES ('欢迎使用AI文章生成与评分系统！由四川粒子通识网络科技有限公司提供技术支持。', 1);

-- 注意：初始管理员账号应该通过部署脚本创建，不直接写在此SQL中
-- 这是为了避免明文存储初始密码，部署脚本会询问强密码
