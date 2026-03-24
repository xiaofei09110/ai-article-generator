# 安装和部署指南

本文档提供AI文章生成系统的完整安装步骤，包括Docker方案和传统虚拟主机方案。

## 目录

1. [Docker Compose部署（推荐）](#docker-compose部署推荐)
2. [虚拟主机部署](#虚拟主机部署)
3. [初始化管理员账户](#初始化管理员账户)
4. [环境变量配置](#环境变量配置)
5. [故障排查](#故障排查)

---

## Docker Compose部署（推荐）

### 系统要求

- Docker Desktop 20.10+ 或 Docker Engine 20.10+
- Docker Compose 1.29+
- 4GB RAM 最小
- 20GB 磁盘空间

### Windows 用户

1. 下载并安装 [Docker Desktop for Windows](https://docs.docker.com/desktop/install/windows-install/)
2. 启用 WSL 2 后端（推荐）
3. 启动 Docker Desktop

### macOS 用户

1. 下载并安装 [Docker Desktop for Mac](https://docs.docker.com/desktop/install/mac-install/)
2. 启动 Docker Desktop

### Linux 用户

```bash
# Ubuntu/Debian
sudo apt-get install docker.io docker-compose

# 添加当前用户到docker组（避免每次都用sudo）
sudo usermod -aG docker $USER
newgrp docker
```

### 部署步骤

#### 1. 克隆项目

```bash
git clone https://github.com/yourusername/ai-1.0.git
cd ai-1.0
```

#### 2. 配置环境变量

```bash
# 复制环境变量模板
cp .env.example .env

# 编辑.env文件，配置关键参数
# 使用你喜欢的编辑器打开 .env
# 修改以下关键项：
# - DB_PASSWORD: 设置MySQL root密码（强密码，包含大小写字母和特殊符号）
# - DEEPSEEK_API_KEY: 从https://platform.deepseek.com获取
# - AILION_API_KEY: 从https://ai.ailion.top获取（可选）
```

**示例 .env 配置：**

```env
# 数据库
DB_HOST=mysql
DB_PORT=3306
DB_NAME=ai_article
DB_USER=root
DB_PASSWORD=MySecure@Pass123!

# DeepSeek API (可从https://platform.deepseek.com/account/api_keys获取)
DEEPSEEK_API_KEY=sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
DEEPSEEK_API_URL=https://api.deepseek.com/v1

# Ailion API (可选，如果有key可配置)
AILION_API_KEY=your_ailion_api_key
AILION_API_URL=https://ai.ailion.top/v1

# 应用配置
APP_NAME="AI文章生成系统"
APP_ENV=production
APP_DEBUG=false
```

#### 3. 启动容器

```bash
# 构建并启动所有服务（首次运行）
docker-compose up -d

# 查看启动日志
docker-compose logs -f

# 只查看MySQL日志（等待初始化完成）
docker-compose logs -f mysql
```

**预期输出：**
```
mysql       | ready for connections
web         | Apache/2.4.41 (Ubuntu) built on...
phpmyadmin  | ...
```

#### 4. 验证安装

```bash
# 检查所有容器都在运行
docker-compose ps

# 预期输出：
# NAME        STATUS         PORTS
# web         Up 2 minutes   0.0.0.0:8080->80/tcp
# mysql       Up 2 minutes   3306/tcp
# phpmyadmin  Up 2 minutes   0.0.0.0:8081->80/tcp
```

#### 5. 访问应用

| 服务 | URL | 用途 |
|---|---|---|
| 前台网站 | http://localhost:8080 | 文章生成、话题生成 |
| 管理后台登录 | http://localhost:8080/admin/login.php | 管理员入口 |
| 数据库管理 | http://localhost:8081 | phpMyAdmin（仅开发） |

---

## 虚拟主机部署

### 系统要求

- PHP 8.2+（需pdo_mysql, curl, json扩展）
- MySQL 8.0+（或MariaDB 10.5+）
- Apache 2.4+（需mod_rewrite, mod_headers）
- 至少 50MB 磁盘空间
- FTP/SSH 访问

### 托管商推荐

- **阿里云ECS** - 灵活的云主机
- **腾讯云CVM** - 高性价比
- **Linode** - 国外服务器
- **DigitalOcean** - 便宜可靠

### 部署步骤

#### 1. 上传文件

```bash
# 使用FTP/SFTP上传项目文件到web根目录（通常是public_html）
# 注意：src/ 目录应该在web根目录外面（或者web根目录指向public/）

# 最终目录结构应该是：
# /home/user/
#   ├── public_html/      <- web root (Apache documentroot)
#   │   └── 所有public/中的文件...
#   └── ai-1.0/           <- 项目根目录
#       ├── src/          <- 所有PHP类库
#       ├── sql/
#       ├── .env
#       └── ...

# 或者（如果web root就是项目目录）：
# /home/user/public_html/
#   ├── index.php (来自public/)
#   ├── api/
#   ├── admin/
#   ├── css/
#   ├── js/
#   └── ... 其他public内容
```

#### 2. 创建数据库

使用托管商提供的面板（cPanel/Plesk）或SSH：

```bash
# 通过SSH连接到服务器
ssh user@your-host.com

# 登录MySQL
mysql -u root -p

# 创建数据库和用户
CREATE DATABASE ai_article CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ai_user'@'localhost' IDENTIFIED BY 'secure_password_123';
GRANT ALL PRIVILEGES ON ai_article.* TO 'ai_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 导入数据库结构
mysql -u ai_user -p ai_article < sql/001_create_tables.sql
```

#### 3. 配置文件权限

```bash
# SSH登录后执行
cd /home/user/public_html

# 设置目录权限
chmod 755 .
chmod 755 api/
chmod 755 admin/
chmod 755 css/
chmod 755 js/

# 如果src目录在web root内（不推荐），保护它
chmod 755 ../src/
find ../src -type f -name "*.php" -exec chmod 644 {} \;
```

#### 4. 配置 .env 文件

```bash
# 在项目根目录创建.env文件
nano .env

# 或使用cPanel文件管理器创建/编辑
```

**填充以下内容：**

```env
DB_HOST=localhost
DB_PORT=3306
DB_NAME=ai_article
DB_USER=ai_user
DB_PASSWORD=secure_password_123

DEEPSEEK_API_KEY=sk-xxxxxxxxxxxxxxxx
DEEPSEEK_API_URL=https://api.deepseek.com/v1

AILION_API_KEY=your_key
AILION_API_URL=https://ai.ailion.top/v1

APP_NAME="AI文章生成系统"
APP_ENV=production
APP_DEBUG=false
```

#### 5. 配置 Apache

在 `public/.htaccess` 中确保已配置（应该已有）：

```apache
RewriteEngine On
RewriteBase /
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
```

#### 6. 检查PHP扩展

通过cPanel或创建 `public/test.php`：

```php
<?php
phpinfo();
?>
```

访问 http://your-domain.com/test.php，检查是否加载了：
- `pdo_mysql` ✓
- `curl` ✓
- `json` ✓

完成后删除测试文件。

#### 7. 访问应用

访问 http://your-domain.com/index.php，应该能看到主页。

---

## 初始化管理员账户

### 方案A：使用 phpMyAdmin（推荐）

1. 访问 phpMyAdmin：http://localhost:8081
2. 登录（用户名：root，密码：从.env中的 DB_PASSWORD）
3. 选择数据库 `ai_article`
4. 选择表 `users`
5. 点击 "Insert" 按钮，添加新记录

**填充以下值：**

```
email:      admin@example.com
password:   $2y$12$abc123...  (使用下面生成的bcrypt哈希)
nickname:   Administrator
role:       admin
is_active:  1
created_at: (使用当前日期/时间)
last_login: NULL
```

### 方案B：使用SQL语句

首先在PHP中生成bcrypt哈希，或使用在线工具。

**生成密码哈希：**

创建 `public/generate_hash.php`：

```php
<?php
// 在浏览器中访问此文件一次，然后立即删除
$password = 'YourSecurePassword123';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo "Hash: " . $hash . "<br>";
echo "记住这个哈希值，删除此文件。";
?>
```

访问 http://localhost:8080/generate_hash.php，复制输出的哈希值。

**执行SQL：**

使用phpMyAdmin的SQL选项卡，或通过命令行：

```sql
INSERT INTO users (email, password, nickname, role, is_active, created_at)
VALUES (
  'admin@example.com',
  '$2y$12$abc123def456ghi789jkl...',  -- 粘贴上面生成的哈希
  'Administrator',
  'admin',
  1,
  NOW()
);
```

### 第一次登录

1. 访问 http://localhost:8080/admin/login.php
2. 使用邮箱：admin@example.com
3. 使用密码：YourSecurePassword123（或你设置的密码）
4. 点击 "登录"

成功登录后，你会看到管理后台面板。

---

## 环境变量配置

### .env 文件详解

```env
# ==================
# 数据库配置
# ==================
DB_HOST=localhost          # MySQL主机（Docker时用"mysql"，本地用"localhost"）
DB_PORT=3306              # MySQL端口
DB_NAME=ai_article        # 数据库名
DB_USER=root              # MySQL用户名
DB_PASSWORD=password      # MySQL密码

# ==================
# DeepSeek AI API
# ==================
# 获取密钥：https://platform.deepseek.com/account/api_keys
DEEPSEEK_API_KEY=sk-xxxxxxxx
DEEPSEEK_API_URL=https://api.deepseek.com/v1

# ==================
# Ailion API
# ==================
# 可选，如果不使用Ailion可以留空
AILION_API_KEY=your_ailion_key
AILION_API_URL=https://ai.ailion.top/v1

# ==================
# 应用配置
# ==================
APP_NAME=AI文章生成系统     # 应用名称
APP_ENV=production         # 环境（production/development）
APP_DEBUG=false            # 是否显示详细错误（生产环境必须false）
```

### 安全提示

⚠️ **重要安全提示：**

1. **不要提交 .env 到Git**
   - `.env` 已在 `.gitignore` 中
   - 只提交 `.env.example` 模板

2. **使用强密码**
   - MySQL密码：至少16个字符，包含大小写字母、数字和特殊符号
   - 示例：`MySecure@2024!Db`

3. **API密钥安全**
   - 不要在前端代码中暴露API密钥
   - 所有AI API调用都在服务端进行
   - 定期轮换API密钥

4. **生产环境**
   - 设置 `APP_ENV=production`
   - 设置 `APP_DEBUG=false`
   - 启用HSTS（HTTPS）

---

## 故障排查

### 连接数据库失败

**错误信息：** `SQLSTATE[HY000]: General error: 2002 No such file or directory`

**解决方案：**

```bash
# Docker环境
1. 检查MySQL是否运行
   docker-compose ps mysql

2. 查看MySQL日志
   docker-compose logs mysql

3. 检查.env中DB_HOST是否为"mysql"（不是localhost）

4. 重启MySQL
   docker-compose restart mysql
   sleep 10  # 等待MySQL初始化
```

### CSRF Token错误

**错误信息：** `{"success": false, "message": "CSRF validation failed"}`

**解决方案：**

1. 确保表单包含CSRF token：
```html
<input type="hidden" name="_csrf" value="<?php echo CSRF::getToken(); ?>">
```

2. AJAX请求包含CSRF头：
```javascript
fetch('/api/article/generate.php', {
  method: 'POST',
  headers: {
    'X-CSRF-Token': csrfToken  // 从meta标签读取
  },
  body: JSON.stringify(data)
});
```

### PHP扩展缺失

**错误信息：** `Class "PDO" not found` 或 `Call to undefined function curl_init()`

**解决方案：**

```bash
# Docker环境 - 自动包含
# 虚拟主机 - 通过cPanel启用扩展

# 验证扩展
php -m | grep -E "pdo_mysql|curl|json"
```

### 权限问题

**错误信息：** `Permission denied` 或 `无法写入文件`

**解决方案：**

```bash
# 设置正确的权限
chmod 755 public/
chmod 755 public/api/
chmod 644 public/*.php
chmod 644 public/api/*/*.php
chmod 755 src/
chmod 644 src/*.php

# 如果src在web root内（不推荐）
chmod 000 src/  # 禁止web访问
```

### API返回403错误

**原因：** CSRF验证失败或权限不足

**检查清单：**

1. ✓ 已登录（如果端点需要）
2. ✓ CSRF token有效
3. ✓ Content-Type正确（application/json）
4. ✓ 用户有相应权限（admin端点需要admin角色）

### 文章生成超时

**错误信息：** `cURL error XX: Timeout reached`

**解决方案：**

1. 检查API密钥是否有效
2. 检查网络连接
3. 增加超时时间（src/AIClient.php）：

```php
const TIMEOUT = 60;  // 从30改为60秒
```

### MySQL磁盘满

**错误信息：** `Disk full or quota exceeded`

**清理步骤：**

```bash
# Docker环境
docker-compose exec mysql mysql -u root -p -e "
  SELECT table_schema as 'Database',
         ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as 'Size(MB)'
  FROM information_schema.tables
  GROUP BY table_schema;
"

# 清理旧评分数据（可选）
DELETE FROM ratings WHERE rated_at < DATE_SUB(NOW(), INTERVAL 180 DAY);
OPTIMIZE TABLE ratings;
```

### 更多帮助

- 查看 [README.md](README.md) 了解系统概况
- 查看 [API_DOCS.md](API_DOCS.md) 了解API细节
- 检查Docker日志：`docker-compose logs -f`
- 检查PHP错误日志：`docker-compose exec web tail -f /var/log/apache2/error.log`

---

## 升级指南

### Docker升级

```bash
# 拉取最新代码
git pull origin main

# 停止现有容器
docker-compose down

# 重建并启动
docker-compose up -d

# 查看日志确认成功
docker-compose logs -f
```

### 虚拟主机升级

```bash
# 1. 备份当前文件和数据库
mysqldump -u ai_user -p ai_article > backup_$(date +%Y%m%d).sql

# 2. 通过FTP上传新文件（覆盖除.env外的所有文件）

# 3. 如果有新的数据库更改，执行SQL脚本
mysql -u ai_user -p ai_article < sql/001_create_tables.sql

# 4. 访问网站验证功能正常
```

---

**完成安装后，你可以：**
- 访问首页生成文章
- 注册新账户
- 在管理后台查看统计数据
- 修改公告内容
- 查看用户评分

祝部署顺利！ 🎉
