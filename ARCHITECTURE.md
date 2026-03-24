# 系统架构设计

本文档详细说明AI文章生成系统的技术架构、核心设计原则、组件交互和扩展指南。

**目录**
1. [整体架构](#整体架构)
2. [核心组件](#核心组件)
3. [数据流](#数据流)
4. [认证与授权](#认证与授权)
5. [数据库设计](#数据库设计)
6. [API设计](#api设计)
7. [安全性](#安全性)
8. [性能优化](#性能优化)
9. [扩展指南](#扩展指南)

---

## 整体架构

### 分层架构（Layered Architecture）

```
┌─────────────────────────────────────────────┐
│           表现层 (Presentation)              │
│    HTML + Bootstrap + Vanilla JavaScript     │
└────────────────────┬────────────────────────┘
                     │ HTTP/HTTPS
┌────────────────────▼────────────────────────┐
│           应用层 (Application)               │
│         RESTful API Endpoints (.php)         │
│  ├── /api/auth/*          认证接口           │
│  ├── /api/article/*       文章接口           │
│  ├── /api/rating/*        评分接口           │
│  ├── /api/topic/*         话题接口           │
│  ├── /api/polish/*        润色接口           │
│  ├── /api/admin/*         管理接口           │
│  └── /api/announcement/*  公告接口           │
└────────────────────┬────────────────────────┘
                     │
┌────────────────────▼────────────────────────┐
│           业务逻辑层 (Business)              │
│              核心类库 (src/)                 │
│  ├── Auth.php             认证&授权         │
│  ├── AIClient.php         AI API调用        │
│  ├── RateLimit.php        速率限制          │
│  ├── Response.php         响应格式化        │
│  ├── CSRF.php             CSRF防护          │
│  └── Config.php           环境配置          │
└────────────────────┬────────────────────────┘
                     │
┌────────────────────▼────────────────────────┐
│           数据访问层 (Data Access)           │
│          Database.php (PDO Wrapper)         │
│  ├── query()              执行SQL           │
│  ├── fetchOne()           查询单条          │
│  ├── fetchAll()           查询多条          │
│  └── fetchColumn()        查询列值          │
└────────────────────┬────────────────────────┘
                     │
┌────────────────────▼────────────────────────┐
│           存储层 (Storage)                  │
│  ├── MySQL 8.0 (业务数据)                   │
│  ├── Session (用户会话)                     │
│  └── .env (配置信息)                        │
└─────────────────────────────────────────────┘
```

### 关键设计原则

1. **单一职责** - 每个类/函数只做一件事
2. **开闭原则** - 对扩展开放，对修改关闭
3. **依赖倒置** - 依赖抽象而非具体实现
4. **不重复原则** - AIClient.php合并重复逻辑
5. **安全优先** - 参数化查询、CSRF防护、认证检查

---

## 核心组件

### 1. Database.php - 数据访问层

**职责：** 封装PDO，提供类型安全的数据库操作

```php
// 单例模式 - 确保全局只有一个连接
$db = Database::getInstance();

// 参数化查询 - 防止SQL注入
$user = Database::fetchOne(
  'SELECT * FROM users WHERE email = ?',
  [$email]
);

// 返回值类型一致
$count = Database::fetchColumn('SELECT COUNT(*) FROM articles');  // int
$users = Database::fetchAll('SELECT * FROM users LIMIT ?', [$limit]);  // array
```

**关键特性：**
- ✓ PDO参数化查询（`::ATTR_EMULATE_PREPARES => false`）
- ✓ 自动转换数据类型
- ✓ 异常处理和错误日志
- ✓ 连接池管理

**扩展建议：**
如需高级功能，建议添加：
```php
// 事务支持
Database::beginTransaction();
Database::commit() / Database::rollback();

// 查询构建器
Database::table('users')->where('id', '>', 10)->get();

// 缓存层
Database::cache()->remember('key', 3600, fn => {...});
```

---

### 2. Auth.php - 认证与授权

**职责：** 用户认证、权限检查、会话管理

```php
// 用户认证流程
Auth::register($email, $password, $nickname);  // 注册
Auth::login($email, $password);               // 登录
Auth::logout();                               // 登出
Auth::getCurrentUser();                       // 获取当前用户

// 权限检查
Auth::isLoggedIn();                           // 是否已登录
Auth::isAdmin();                              // 是否管理员
Auth::requireLoginApi();                      // API登录检查
Auth::requireAdminApi();                      // API管理员检查
Auth::requireLoginPage();                     // 页面登录检查
Auth::requireAdminPage();                     // 页面管理员检查
```

**会话管理：**

```php
// 登录时 - 再生成Session ID防止会话固定
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['email'] = $user['email'];

// 登出时 - 清空所有会话数据
session_destroy();
```

**密码安全：**

```php
// 注册时 - bcrypt哈希（cost=12）
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// 登录时 - 安全验证
if (password_verify($password, $dbPassword)) {
  // 成功
}
```

**权限检查流程：**

```
API请求
  ↓
[检查CSRF Token] → 失败 → 403 CSRF验证失败
  ↓ 成功
[检查是否需要登录] → 失败 → 401 需要登录
  ↓ 成功
[检查是否需要管理员] → 失败 → 403 权限不足
  ↓ 成功
业务逻辑
```

---

### 3. AIClient.php - 第三方API调用

**职责：** 统一封装DeepSeek和Ailion API调用

```php
// 生成文章
$result = AIClient::generateArticle([
  'title' => '...',
  'requirements' => '...',
  'word_limit' => 1000,
  'style' => 'formal',
  'api_source' => 'deepseek'
]);

// 生成话题
$topics = AIClient::generateTopics('互联网', 7);

// 润色需求
$polished = AIClient::polishRequirements('需求描述...');
```

**设计模式 - 策略模式：**

```php
// 内部实现
private static function callAPI($config) {
  switch ($config['api_source']) {
    case 'deepseek':
      return self::callDeepSeek($config);
    case 'ailion':
      return self::callAilion($config);
    default:
      throw new Exception('Unknown API source');
  }
}
```

**错误处理：**

```php
try {
  $result = AIClient::generateArticle($data);
} catch (APITimeoutException $e) {
  // 超时 - 提示用户稍后重试
  Response::serverError('生成超时，请稍后重试');
} catch (APIRateLimitException $e) {
  // 限流 - API调用次数超限
  Response::tooManyRequests('API次数超限，请稍后重试');
} catch (APIException $e) {
  // 其他错误
  Response::serverError($e->getMessage());
}
```

---

### 4. RateLimit.php - 速率限制

**职责：** 防止API滥用，基于MySQL的限流

```php
// 检查限流
if (!RateLimit::check('topic_generate', $ipAddress)) {
  Response::tooManyRequests('请求过于频繁');
}

// 记录请求
RateLimit::record('topic_generate', $ipAddress);

// 支持自定义限流规则
RateLimit::check('custom_endpoint', $ipAddress, [
  'limit' => 5,        // 最多5次
  'window' => 3600     // 时间窗口（秒）
]);
```

**实现原理：**

```
接收请求
  ↓
从rate_limits表查询最近的请求
  ↓
统计时间窗口内的请求数
  ↓
如果超限 → 返回429
  ↓
记录本次请求 → 返回200
```

**数据清理：**

```php
// 定期清理过期记录（可通过cron或首次访问时调用）
RateLimit::cleanup();

// SQL: DELETE FROM rate_limits WHERE request_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
```

---

### 5. CSRF.php - 跨站请求伪造防护

**职责：** 生成和验证CSRF Token

```php
// 获取Token（在PHP页面中）
$token = CSRF::getToken();

// 在HTML中使用
<input type="hidden" name="_csrf" value="<?php echo $token; ?>">

// AJAX请求时
headers: {
  'X-CSRF-Token': csrfToken
}
```

**实现细节：**

```php
// 生成Token
public static function getToken(): string {
  if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));  // 64字符
  }
  return $_SESSION['_csrf'];
}

// 验证Token（自动在bootstrap.php中调用）
public static function verify(string $token): bool {
  $sessionToken = $_SESSION['_csrf'] ?? '';
  return !empty($token) && hash_equals($sessionToken, $token);
  // hash_equals防止时序攻击
}
```

**防护流程：**

```
用户访问页面
  ↓
生成CSRF Token放入Session
  ↓
页面显示Token
  ↓
用户提交表单
  ↓
[验证Token] → Token无效或缺失 → 403拒绝
  ↓ 验证成功
处理请求
```

---

### 6. Response.php - 统一响应格式

**职责：** 统一JSON响应格式，简化API开发

```php
// 成功响应
Response::success([
  'article_id' => 123,
  'content' => '...'
], '文章生成成功');

// 错误响应
Response::error('操作失败', 400);
Response::unauthorized('需要登录');
Response::forbidden('权限不足');
Response::notFound('资源不存在');
Response::tooManyRequests('请求过于频繁');
Response::serverError('服务器错误');

// 验证错误
Response::validationError(['email' => '邮箱格式不正确']);
```

**响应格式：**

```json
{
  "success": true,
  "message": "...",
  "data": {},
  "errors": {}
}
```

---

## 数据流

### 文章生成流程

```
前端HTML表单
  │
  ├─ 用户填写需求
  │
  └─ 点击"生成文章"
        │
        ▼
    JavaScript
  ├─ 表单验证
  ├─ 获取CSRF Token
  └─ 发送POST请求到/api/article/generate.php
        │
        ▼
   api/article/generate.php
  ├─ 检查CSRF Token ✗ → 403
  ├─ 检查参数有效性 ✗ → 400
  └─ 调用AIClient::generateArticle()
        │
        ▼
    AIClient.php
  ├─ 根据apiSource选择调用方式
  ├─ callDeepSeek() 或 callAilion()
  │  ├─ 构建请求体
  │  ├─ 设置cURL选项
  │  ├─ 发送HTTP请求
  │  └─ 解析响应
  └─ 返回生成的内容
        │
        ▼
   api/article/generate.php
  ├─ 保存到articles表（关联user_id）
  ├─ 返回article_id和内容
  └─ 返回200 JSON响应
        │
        ▼
    前端JavaScript
  ├─ 显示生成的文章
  ├─ 保存article_id到localStorage
  └─ 显示评分和复制按钮
```

### 用户登录流程

```
前端login.php表单
  │
  └─ 输入邮箱和密码 → 点击"登录"
        │
        ▼
   api/auth/login.php (POST)
  ├─ 检查CSRF Token ✗ → 403
  ├─ 验证参数 ✗ → 400
  └─ Database::fetchOne('SELECT * FROM users WHERE email = ?')
        │
        ├─ 查询失败 → 401 邮箱或密码错误
        │
        └─ 查询成功
             │
             ├─ password_verify(提交的密码, 数据库密码)
             │  ├─ 验证失败 → 401 邮箱或密码错误
             │  └─ 验证成功
             │       │
             │       ├─ session_regenerate_id(true)
             │       ├─ $_SESSION['user_id'] = ...
             │       ├─ 更新last_login时间戳
             │       └─ 返回200 {"success": true}
             │
             └─ 前端收到成功响应
                  │
                  └─ 重定向到首页或个人中心
```

---

## 认证与授权

### 角色权限模型

```
User (user)
  ├─ 可访问: 首页、文章生成、话题生成、个人中心
  └─ 不可访问: 管理后台、用户管理、统计数据

Admin (admin)
  ├─ 包含User的所有权限
  ├─ 可访问: 管理后台、统计数据、用户列表
  └─ 可执行: 修改公告、查看所有数据

Anonymous (未登录)
  ├─ 可访问: 首页、文章生成、话题生成、评分
  └─ 不可访问: 个人中心、登录后功能
```

### 权限检查矩阵

| 资源 | 匿名 | 用户 | 管理员 |
|---|---|---|---|
| GET /api/auth/me | ✗ | ✓ | ✓ |
| POST /api/article/generate | ✓ | ✓ | ✓ |
| GET /api/article/history | ✗ | ✓ | ✓ |
| POST /api/article/delete | ✗ | ✓（仅自己） | ✓ |
| POST /api/rating/submit | ✓ | ✓ | ✓ |
| GET /api/admin/stats | ✗ | ✗ | ✓ |
| POST /api/announcement/update | ✗ | ✗ | ✓ |

---

## 数据库设计

### ER图

```
┌──────────────┐
│    users     │
├──────────────┤
│ id (PK)      │
│ email (UNIQUE)
│ password     │
│ nickname     │
│ role         │
│ is_active    │
│ created_at   │
│ last_login   │
└──────────────┘
       │
       │ 1:N
       │
┌──────────────────────┐      ┌────────────────────┐
│    articles          │      │  rate_limits       │
├──────────────────────┤      ├────────────────────┤
│ id (PK)              │      │ id (PK)            │
│ user_id (FK)         │      │ ip_address         │
│ title                │      │ endpoint           │
│ requirements         │      │ request_at         │
│ content              │      └────────────────────┘
│ word_limit           │
│ style                │      ┌────────────────────┐
│ output_format        │      │ announcements      │
│ api_source           │      ├────────────────────┤
│ generated_at         │      │ id (PK)            │
└──────────────────────┘      │ content            │
       │                      │ is_active          │
       │ 1:N                  │ created_by (FK)    │
       │                      │ updated_at         │
┌──────────────┐               └────────────────────┘
│   ratings    │
├──────────────┤
│ id (PK)      │
│ article_id   │
│   (FK)       │
│ user_id (FK) │
│ rating       │
│ comment      │
│ rated_at     │
└──────────────┘
```

### 索引策略

```sql
-- 频繁查询的列建索引
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_articles_user_id ON articles(user_id);
CREATE INDEX idx_ratings_article_id ON ratings(article_id);

-- 范围查询/排序建索引
CREATE INDEX idx_articles_generated_at ON articles(generated_at DESC);
CREATE INDEX idx_ratings_rated_at ON ratings(rated_at DESC);

-- 复合索引（用于常见的多条件查询）
CREATE INDEX idx_rate_limits_check ON rate_limits(ip_address, endpoint, request_at);
```

### 数据一致性

**外键约束：**
```sql
-- 删除用户时，其文章变为匿名
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL

-- 删除文章时，级联删除评分
FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
```

---

## API设计

### RESTful原则

```
资源操作 → HTTP方法 → URL路径

文章生成 → POST → /api/article/generate.php
获取历史 → GET  → /api/article/history.php
查看文章 → GET  → /api/article/view.php?id=123
删除文章 → POST → /api/article/delete.php
```

### 请求验证流程

```
每个API端点都遵循此流程:

1. HTTP方法检查
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
     Response::error('仅支持POST请求', 405);
   }

2. CSRF Token验证（POST请求）
   if (!CSRF::verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
     Response::error('CSRF验证失败', 403);
   }

3. 认证检查
   Auth::requireLoginApi();  // 如果需要登录

4. 权限检查
   Auth::requireAdminApi();  // 如果需要管理员

5. 参数验证
   if (empty($_POST['title'])) {
     Response::validationError(['title' => '标题不能为空']);
   }

6. 业务逻辑
   $result = doSomething();

7. 响应
   Response::success($result);
```

---

## 安全性

### 防护措施总结

| 威胁 | 防护方式 | 实现位置 |
|---|---|---|
| SQL注入 | PDO参数化查询 | Database.php |
| CSRF | Token验证 | CSRF.php + bootstrap.php |
| XSS | HTML转义 | Response时 + CSP头 |
| 暴力破解 | 速率限制 | RateLimit.php |
| 会话固定 | Session再生成 | Auth.php::login() |
| 密码泄露 | bcrypt哈希 | Auth.php::register() |
| 权限提升 | 权限检查 | Auth.php::requireAdmin*() |
| 目录遍历 | .htaccess保护 | public/.htaccess |

### 环境变量保护

```
✓ API密钥在.env中，不在代码中
✓ .env文件在.gitignore中
✓ .env文件通过.htaccess保护
✗ 不会暴露在错误消息中
```

### HTTPS和安全头

```apache
# .htaccess中的安全头
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-Content-Type-Options "nosniff"
Header always set Content-Security-Policy "..."
Header always set Referrer-Policy "strict-origin-when-cross-origin"

# 生产环境启用
Header always set Strict-Transport-Security "max-age=31536000"
```

---

## 性能优化

### 数据库优化

1. **查询优化**
   ```php
   // ✓ 好 - 只查询需要的列
   SELECT id, email, nickname FROM users WHERE role = ?

   // ✗ 差 - 查询所有列
   SELECT * FROM users WHERE role = ?
   ```

2. **索引使用**
   ```php
   // ✓ 利用索引
   SELECT * FROM articles WHERE user_id = ?  // 已建索引

   // ✗ 全表扫描
   SELECT * FROM articles WHERE api_source LIKE 'deepseek%'  // 无索引
   ```

3. **分页查询**
   ```php
   // ✓ 分页 - 只获取需要的数据
   SELECT * FROM articles ORDER BY created_at DESC LIMIT 10 OFFSET 0

   // ✗ 一次性加载 - 浪费内存
   SELECT * FROM articles
   ```

### 缓存策略

*当前版本未实现，可在v1.1添加：*

```php
// 查询缓存示例
class Database {
  private static $cache = [];

  public static function cacheGet($key) {
    return self::$cache[$key] ?? null;
  }

  public static function cacheSet($key, $value, $ttl = 3600) {
    self::$cache[$key] = ['value' => $value, 'expires' => time() + $ttl];
  }
}

// 使用
$user = Database::cacheGet('user:' . $userId);
if (!$user) {
  $user = Database::fetchOne('SELECT * FROM users WHERE id = ?', [$userId]);
  Database::cacheSet('user:' . $userId, $user, 3600);
}
```

### 前端性能

1. **静态资源缓存** - .htaccess设置1年过期
2. **代码分割** - 大页面可分割为多个JS文件
3. **懒加载** - 文章列表可实现虚拟滚动

---

## 扩展指南

### 添加新API端点

1. **创建文件**
   ```
   public/api/{module}/{action}.php
   ```

2. **模板代码**
   ```php
   <?php
   require_once '../../../src/bootstrap.php';

   // 检查HTTP方法
   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
     Response::error('仅支持POST请求', 405);
   }

   // 检查认证（如需要）
   Auth::requireLoginApi();

   // 检查权限（如需要）
   Auth::requireAdminApi();

   try {
     // 参数验证
     $data = json_decode(file_get_contents('php://input'), true);
     if (empty($data['field'])) {
       Response::validationError(['field' => '字段不能为空']);
     }

     // 业务逻辑
     $result = Database::fetchOne('SELECT * FROM table WHERE id = ?', [$data['id']]);

     // 返回响应
     Response::success($result, '操作成功');
   } catch (Exception $e) {
     Response::serverError($e->getMessage());
   }
   ```

### 添加新的业务类

1. **创建类文件**
   ```
   src/YourClass.php
   ```

2. **遵循命名和结构**
   ```php
   <?php
   namespace App;  // 可选命名空间

   class YourClass {
     public static function yourMethod($param) {
       // 实现逻辑
       return $result;
     }
   }
   ```

3. **在bootstrap.php中加载**
   ```php
   require_once 'YourClass.php';
   ```

### 添加数据库表

1. **编辑sql/001_create_tables.sql**
2. **添加CREATE TABLE语句**
3. **添加必要的索引**
4. **创建迁移脚本**（可选）

### 集成新的AI服务

1. **在AIClient.php中添加方法**
   ```php
   private static function callYourAI($config) {
     // 实现API调用逻辑
   }
   ```

2. **在generateArticle()中添加选择分支**
   ```php
   case 'your_ai':
     return self::callYourAI($config);
   ```

3. **更新配置**
   ```env
   YOUR_AI_API_KEY=...
   YOUR_AI_API_URL=...
   ```

---

## 部署架构

### Docker容器结构

```
docker-compose
├── web (PHP 8.2 + Apache)
│   ├── public/         (Document Root)
│   ├── src/            (核心类库)
│   └── .env            (环境变量)
├── mysql (MySQL 8.0)
│   └── /var/lib/mysql  (数据持久化)
└── phpmyadmin (开发用)
```

### 生产部署建议

```
虚拟主机 / VPS / 云服务器
├── PHP 8.2 + Apache / Nginx
├── MySQL 8.0 (专用服务器)
├── SSL证书 (启用HTTPS)
├── WAF (Web应用防火墙)
├── CDN (静态资源)
└── 监控和日志系统
```

---

## 故障排查指南

### 调试模式

```php
// src/Config.php
if (Config::get('APP_DEBUG')) {
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
} else {
  error_reporting(E_ALL);
  ini_set('display_errors', 0);
  ini_set('log_errors', 1);
}
```

### 日志记录

```php
// 添加到Response类或Database类
error_log('[' . date('Y-m-d H:i:s') . '] ' . $message);

// 查看日志
docker-compose logs -f web
tail -f /var/log/apache2/error.log
```

### 常见问题

1. **"CSRF验证失败"**
   - 检查请求是否包含有效的CSRF Token
   - 检查Session是否过期

2. **"需要登录"**
   - 确认Session Cookie已设置
   - 检查浏览器是否启用Cookie

3. **"数据库连接失败"**
   - 检查.env中的DB_HOST和凭证
   - 确认MySQL服务运行

---

## 版本升级规划

### v1.1 计划功能
- 用户头像和个人资料
- 文章收藏和分享
- 评论系统
- 搜索功能

### v1.2 计划功能
- API限流可配置化
- 数据导出功能
- 邮件通知
- 国际化支持

### v2.0 愿景
- 团队协作功能
- 更多AI服务集成
- 插件系统
- 移动应用

---

**相关文档：**
- [README.md](README.md) - 项目概览
- [INSTALL.md](INSTALL.md) - 安装指南
- [API_DOCS.md](API_DOCS.md) - API文档
