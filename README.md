# AI文章生成与评分系统 v1.0

一个功能完整的AI文章生成与评分系统，支持用户注册登录、文章生成历史管理、智能话题生成、文章润色等核心功能。

## ✨ 核心功能

- **用户认证系统** - 邮箱注册、安全登录、Session会话管理（bcrypt密码哈希）
- **AI文章生成** - 支持多种风格和格式，可选择DeepSeek或Ailion接口
- **个人中心** - 文章生成历史、分页浏览、文章详情查看、一键删除
- **智能话题生成** - 按行业生成热门话题灵感，支持自定义数量
- **文章润色** - 智能优化文章需求描述，提升生成质量
- **评分系统** - 用户评分（1-5星）和评论，完整的评分数据追踪
- **管理后台** - 数据统计、用户管理、公告管理（仅管理员）
- **CSRF防护** - 所有POST请求均受保护，防止跨站请求伪造
- **响应式设计** - Bootstrap 5 UI，适配所有设备

## 🏗️ 技术架构

### 后端
- **PHP 8.2** - 现代PHP版本，支持类型声明、命名空间
- **MySQL 8.0** - 关系型数据库替代JSON存储
- **PDO** - 防SQL注入的参数化查询
- **Session认证** - 基于会话的用户认证系统
- **RESTful API** - 统一的JSON响应格式

### 前端
- **HTML 5** + **Bootstrap 5** - 现代响应式UI框架
- **Vanilla JavaScript** - 无框架依赖，原生JS实现
- **Fetch API** - 异步请求数据

### 部署
- **Docker Compose** - 一键启动完整开发/生产环境
- **Apache 2.4** - 成熟稳定的Web服务器
- **phpMyAdmin** - 数据库管理工具（开发环境）

## 📦 系统要求

### 最小化要求（虚拟主机）
- PHP 8.2+（需pdo_mysql扩展）
- MySQL 8.0+
- Apache 2.4+（需mod_rewrite和mod_headers）

### 推荐方案（Docker）
- Docker 20.10+
- Docker Compose 1.29+
- 4GB RAM（MySQL + PHP + Application）

## 🚀 快速开始

### 方案A：Docker Compose（推荐）

```bash
# 1. 克隆项目
git clone https://github.com/yourusername/ai-1.0.git
cd ai-1.0

# 2. 配置环境变量
cp .env.example .env
# 编辑.env，修改数据库密码和API密钥

# 3. 启动服务
docker-compose up -d

# 4. 等待MySQL初始化（首次启动需要30秒左右）
docker-compose logs -f mysql

# 5. 访问应用
# 前台: http://localhost:8080
# 管理后台: http://localhost:8080/admin/login.php
# phpMyAdmin: http://localhost:8081
```

### 方案B：虚拟主机部署

详见 [INSTALL.md](INSTALL.md)

## 📖 文档

- [INSTALL.md](INSTALL.md) - 详细的安装和部署指南
- [API_DOCS.md](API_DOCS.md) - 完整的API文档
- [ARCHITECTURE.md](ARCHITECTURE.md) - 系统架构设计说明

## 🔐 安全特性

- ✅ **密码加密** - 使用bcrypt（cost=12）安全哈希
- ✅ **CSRF保护** - 所有POST请求需要有效的CSRF Token
- ✅ **SQL防注入** - PDO参数化查询
- ✅ **XSS防护** - HTML转义和CSP策略
- ✅ **安全头** - X-Frame-Options、X-Content-Type-Options等
- ✅ **环境变量** - API密钥和数据库凭证不在代码中
- ✅ **权限控制** - 基于角色的访问控制（user/admin）
- ✅ **速率限制** - 基于IP的生成和话题生成请求限制

## 📁 目录结构

```
/
├── .env                          # 环境变量（不上传git）
├── .env.example                  # 环境变量模板
├── .gitignore                    # git忽略规则
├── Dockerfile                    # PHP 8.2镜像定义
├── docker-compose.yml            # 容器编排配置
├── README.md                     # 本文件
├── INSTALL.md                    # 安装指南
├── API_DOCS.md                   # API文档
├── ARCHITECTURE.md               # 架构说明
├── LICENSE                       # MIT许可证
├── php.ini                       # PHP配置
│
├── sql/
│   └── 001_create_tables.sql     # 数据库初始化脚本
│
├── src/                          # 核心类库（不在web root）
│   ├── bootstrap.php             # 应用启动文件
│   ├── Config.php                # 环境变量配置
│   ├── Database.php              # PDO数据库单例
│   ├── Response.php              # JSON响应格式化
│   ├── CSRF.php                  # CSRF Token生成/验证
│   ├── Auth.php                  # 用户认证和权限
│   ├── AIClient.php              # AI API调用封装
│   └── RateLimit.php             # 速率限制
│
└── public/                       # Web根目录（Apache documentroot）
    ├── .htaccess                 # URL重写和安全配置
    ├── index.php                 # 首页
    ├── login.php                 # 用户登录
    ├── register.php              # 用户注册
    ├── profile.php               # 个人中心
    ├── topic-generator.php       # 话题生成
    │
    ├── admin/
    │   ├── index.php             # 管理后台首页
    │   └── login.php             # 管理员登录
    │
    ├── api/
    │   ├── .htaccess             # API安全配置
    │   ├── auth/
    │   │   ├── register.php      # POST /api/auth/register
    │   │   ├── login.php         # POST /api/auth/login
    │   │   ├── logout.php        # POST /api/auth/logout
    │   │   ├── me.php            # GET /api/auth/me
    │   │   └── csrf-token.php    # GET /api/auth/csrf-token
    │   ├── article/
    │   │   ├── generate.php      # POST /api/article/generate
    │   │   ├── history.php       # GET /api/article/history
    │   │   ├── view.php          # GET /api/article/view
    │   │   └── delete.php        # POST /api/article/delete
    │   ├── rating/
    │   │   └── submit.php        # POST /api/rating/submit
    │   ├── topic/
    │   │   └── generate.php      # POST /api/topic/generate
    │   ├── polish/
    │   │   └── requirements.php  # POST /api/polish/requirements
    │   ├── announcement/
    │   │   ├── get.php           # GET /api/announcement/get
    │   │   └── update.php        # POST /api/announcement/update
    │   └── admin/
    │       ├── stats.php         # GET /api/admin/stats
    │       └── users.php         # GET /api/admin/users
    │
    ├── css/
    │   ├── styles.css            # 主样式表
    │   ├── topic-generator.css   # 话题生成器样式
    │   └── auth.css              # 认证页面样式
    │
    └── js/
        ├── main.js               # 主JavaScript逻辑
        └── auth.js               # 认证相关函数
```

## 💾 数据库表设计

### users（用户表）
```
id           INT UNSIGNED PRIMARY KEY
email        VARCHAR(255) UNIQUE       - 邮箱
password     VARCHAR(255)              - bcrypt哈希
nickname     VARCHAR(100)              - 昵称
role         ENUM('user','admin')      - 用户角色
is_active    TINYINT(1)               - 是否启用
created_at   DATETIME                 - 注册时间
last_login   DATETIME                 - 最后登录
```

### articles（文章表）
```
id           INT UNSIGNED PRIMARY KEY
user_id      INT UNSIGNED             - 用户ID（null=匿名）
title        VARCHAR(500)             - 文章标题
requirements TEXT                     - 生成需求
content      LONGTEXT                 - 文章内容
word_limit   INT UNSIGNED             - 字数限制
style        VARCHAR(50)              - 文章风格
output_format VARCHAR(50)             - 输出格式
api_source   VARCHAR(50)              - AI源（deepseek/ailion）
generated_at DATETIME                 - 生成时间
```

### ratings（评分表）
```
id           INT UNSIGNED PRIMARY KEY
article_id   INT UNSIGNED             - 文章ID
user_id      INT UNSIGNED             - 用户ID（null=匿名）
rating       TINYINT UNSIGNED         - 评分（1-5）
comment      TEXT                     - 评论
ip_address   VARCHAR(45)              - IP地址
rated_at     DATETIME                 - 评分时间
```

### announcements（公告表）
```
id           INT UNSIGNED PRIMARY KEY
content      TEXT                     - 公告内容
is_active    TINYINT(1)              - 是否启用
created_by   INT UNSIGNED             - 创建者
updated_at   DATETIME                 - 更新时间
```

### rate_limits（限流表）
```
id           INT UNSIGNED PRIMARY KEY
ip_address   VARCHAR(45)              - 客户端IP
endpoint     VARCHAR(100)             - API端点
request_at   DATETIME                 - 请求时间
```

## 🔑 环境变量配置

复制 `.env.example` 为 `.env` 并配置：

```env
# 数据库配置
DB_HOST=mysql
DB_PORT=3306
DB_NAME=ai_article
DB_USER=root
DB_PASSWORD=your_secure_password

# DeepSeek API配置
DEEPSEEK_API_KEY=sk-xxxxx
DEEPSEEK_API_URL=https://api.deepseek.com/v1

# Ailion API配置
AILION_API_KEY=your_ailion_key
AILION_API_URL=https://ai.ailion.top/v1

# 应用配置
APP_NAME="AI文章生成系统"
APP_ENV=production
APP_DEBUG=false
```

## 👤 默认账户

部署后在phpMyAdmin中查看users表，可以创建第一个管理员账户：

```sql
-- 创建管理员账户（密码: admin123）
INSERT INTO users (email, password, nickname, role, is_active)
VALUES ('admin@example.com', '$2y$12$...', 'Admin', 'admin', 1);
```

使用 `password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12])` 生成bcrypt哈希

## 🧪 API基本示例

### 用户注册
```bash
curl -X POST http://localhost:8080/api/auth/register.php \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: {csrfToken}" \
  -d '{
    "email": "user@example.com",
    "password": "password123",
    "nickname": "User Name"
  }'
```

### 生成文章
```bash
curl -X POST http://localhost:8080/api/article/generate.php \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: {csrfToken}" \
  -d '{
    "title": "AI在医疗中的应用",
    "requirements": "介绍AI技术在医疗诊断中的应用...",
    "wordLimit": 1000,
    "style": "formal",
    "outputFormat": "default",
    "apiSource": "deepseek"
  }'
```

完整API文档见 [API_DOCS.md](API_DOCS.md)

## 📊 系统工作流

1. **用户注册** → 邮箱验证唯一性 → bcrypt密码加密 → 创建账户
2. **用户登录** → 验证凭证 → Session再生成 → 记录登录时间
3. **生成文章** → 调用AI API → 存储到数据库（关联user_id）
4. **查看历史** → 分页加载个人文章 → 支持删除操作
5. **评分** → 验证文章存在 → 记录评分和IP → 防重复评分
6. **管理后台** → 统计数据 → 用户列表 → 公告管理

## 🔄 部署更新

因为使用Docker，更新流程非常简单：

```bash
# 1. 获取最新代码
git pull origin main

# 2. 重建容器
docker-compose down
docker-compose up -d

# 3. 检查日志
docker-compose logs -f
```

## 📝 开发指南

### 添加新API端点

1. 在 `public/api/{module}/` 创建PHP文件
2. 在顶部 `require_once '../../src/bootstrap.php'`
3. 使用 `Auth::requireLoginApi()` 或 `Auth::requireAdminApi()` 做权限检查
4. 使用 `Response::success()` 或 `Response::error()` 返回JSON
5. 所有POST请求自动检查CSRF Token（在bootstrap.php中）

### 数据库查询

```php
// 查询单条
$user = Database::fetchOne('SELECT * FROM users WHERE id = ?', [$userId]);

// 查询多条
$articles = Database::fetchAll('SELECT * FROM articles WHERE user_id = ? LIMIT ?, ?', [$userId, $offset, $limit]);

// 获取单个值
$count = Database::fetchColumn('SELECT COUNT(*) FROM articles');

// 插入/更新/删除
Database::query('INSERT INTO articles (user_id, title) VALUES (?, ?)', [$userId, $title]);
```

## 🐛 故障排查

### MySQL连接失败
```bash
# 检查MySQL容器状态
docker-compose ps

# 查看MySQL日志
docker-compose logs mysql

# 重启MySQL
docker-compose restart mysql
```

### API返回403错误
- 确认CSRF Token是否正确传递（`X-CSRF-Token` Header）
- 检查Session是否有效
- 对于需要登录的接口，确认已登录

### 文章生成超时
- 检查API密钥是否有效
- 确认网络连接到AI服务
- 增加超时时间（src/AIClient.php中 `CURLOPT_TIMEOUT`）

## 📄 许可证

MIT License - 见 [LICENSE](LICENSE) 文件

## 👥 联系信息

- **开发** - 四川粒子通识网络科技有限公司
- **网站** - https://liztongshi.cn

## 🎉 更新日志

### v1.0.0 (2026-03-24)
- 完整系统重构：从JSON存储迁移到MySQL
- 新增用户认证系统（注册/登录）
- 新增个人中心（文章历史管理）
- 新增管理后台（数据统计、用户管理）
- 完整的CSRF防护
- Docker Compose部署支持
- 更安全的API密钥管理（.env）
- 生产级别的代码架构
