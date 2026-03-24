# API 文档

本文档详细说明AI文章生成系统的所有API端点、请求/响应格式、认证要求和错误处理。

## 目录

1. [基础信息](#基础信息)
2. [认证接口](#认证接口)
3. [文章接口](#文章接口)
4. [评分接口](#评分接口)
5. [话题接口](#话题接口)
6. [润色接口](#润色接口)
7. [公告接口](#公告接口)
8. [管理接口](#管理接口)
9. [错误处理](#错误处理)
10. [速率限制](#速率限制)

---

## 基础信息

### API基础URL

```
开发环境: http://localhost:8080/api
生产环境: https://your-domain.com/api
```

### 统一响应格式

所有API返回JSON格式，包含以下字段：

```json
{
  "success": true,
  "message": "操作成功",
  "data": {},
  "errors": {}
}
```

| 字段 | 类型 | 说明 |
|---|---|---|
| success | boolean | 请求是否成功 |
| message | string | 提示信息 |
| data | object | 返回的数据（成功时） |
| errors | object | 错误详情（失败时） |

### 认证方式

#### Session认证（推荐）

系统使用基于Session的认证。登录后自动生成Session Cookie。

```javascript
// 浏览器自动携带Cookie，无需手动处理
fetch('/api/article/history.php', {
  method: 'GET',
  credentials: 'include'  // 重要：包含Cookie
});
```

#### CSRF保护

所有POST/PUT/DELETE请求必须包含CSRF Token。

**获取Token：**

```html
<!-- 在HTML中 -->
<meta name="csrf-token" content="<?php echo CSRF::getToken(); ?>">

<!-- 或通过API获取 -->
GET /api/auth/csrf-token.php
```

**在请求中使用：**

```javascript
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

fetch('/api/article/generate.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': csrfToken
  },
  body: JSON.stringify(data)
});
```

### 常见请求头

```
Content-Type: application/json
X-CSRF-Token: {token}
User-Agent: 你的应用名称
```

---

## 认证接口

### 1. 用户注册

**POST** `/auth/register.php`

**请求体：**

```json
{
  "email": "user@example.com",
  "password": "SecurePass123!",
  "nickname": "User Name"
}
```

**响应（成功）：**

```json
{
  "success": true,
  "message": "注册成功，请登录",
  "data": {
    "user_id": 1,
    "email": "user@example.com",
    "nickname": "User Name"
  }
}
```

**响应（失败）：**

```json
{
  "success": false,
  "message": "邮箱已被注册",
  "errors": {
    "email": "该邮箱已存在"
  }
}
```

**字段验证：**

- `email`: 必须是有效的邮箱格式，唯一
- `password`: 最少8个字符，建议包含大小写字母和特殊符号
- `nickname`: 1-100个字符

**cURL示例：**

```bash
curl -X POST http://localhost:8080/api/auth/register.php \
  -H "Content-Type: application/json" \
  -H "X-CSRF-Token: your_csrf_token" \
  -d '{
    "email": "newuser@example.com",
    "password": "MyPassword123!",
    "nickname": "New User"
  }'
```

---

### 2. 用户登录

**POST** `/auth/login.php`

**请求体：**

```json
{
  "email": "user@example.com",
  "password": "SecurePass123!",
  "remember": false
}
```

**响应（成功）：**

```json
{
  "success": true,
  "message": "登录成功",
  "data": {
    "user_id": 1,
    "email": "user@example.com",
    "nickname": "User Name",
    "role": "user"
  }
}
```

**响应（失败）：**

```json
{
  "success": false,
  "message": "邮箱或密码错误",
  "errors": {
    "credentials": "邮箱或密码不正确"
  }
}
```

**参数说明：**

- `email`: 注册时使用的邮箱
- `password`: 密码
- `remember`: 是否记住登录状态（可选，目前未实现）

---

### 3. 获取当前用户

**GET** `/auth/me.php`

**认证要求：** ✓ 需登录

**响应（成功）：**

```json
{
  "success": true,
  "message": "获取用户信息成功",
  "data": {
    "user_id": 1,
    "email": "user@example.com",
    "nickname": "User Name",
    "role": "user",
    "created_at": "2024-03-24 10:30:00",
    "last_login": "2024-03-24 15:45:30"
  }
}
```

**响应（未登录）：**

```json
{
  "success": false,
  "message": "需要登录"
}
```

HTTP状态码：401

---

### 4. 用户登出

**POST** `/auth/logout.php`

**认证要求：** ✓ 需登录

**请求体：** 空或可选

**响应（成功）：**

```json
{
  "success": true,
  "message": "退出登录成功"
}
```

---

### 5. 获取CSRF Token

**GET** `/auth/csrf-token.php`

**响应：**

```json
{
  "success": true,
  "data": {
    "token": "abc123def456..."
  }
}
```

**用途：** 前端可通过此接口获取新的CSRF Token（如原Token过期）

---

## 文章接口

### 1. 生成文章

**POST** `/article/generate.php`

**认证要求：** ✗ 无（登录用户会关联账户，匿名用户不关联）

**请求体：**

```json
{
  "title": "如何使用AI提高写作效率",
  "requirements": "介绍三种AI工具，每种100字左右...",
  "wordLimit": 1000,
  "style": "formal",
  "outputFormat": "default",
  "apiSource": "deepseek"
}
```

**参数说明：**

| 参数 | 类型 | 必需 | 说明 |
|---|---|---|---|
| title | string | ✓ | 文章标题（1-500字符） |
| requirements | string | ✓ | 生成需求/大纲 |
| wordLimit | number | ✓ | 字数限制（100-5000） |
| style | enum | ✓ | 文章风格（formal/casual/technical） |
| outputFormat | enum | ✓ | 输出格式（default/markdown/html） |
| apiSource | enum | ✓ | AI源（deepseek/ailion） |

**响应（成功）：**

```json
{
  "success": true,
  "message": "文章生成成功",
  "data": {
    "article_id": 123,
    "title": "如何使用AI提高写作效率",
    "content": "## 引言\n\n人工智能技术...",
    "word_count": 998,
    "generated_at": "2024-03-24 14:30:00",
    "api_source": "deepseek"
  }
}
```

**响应（失败）：**

```json
{
  "success": false,
  "message": "生成失败，请稍后重试",
  "errors": {
    "api_error": "API调用超时"
  }
}
```

HTTP状态码：500

**注意：**
- 登录用户生成的文章会自动保存到个人历史
- 文章生成通常需要20-60秒
- 生成内容的质量取决于需求描述的详细程度

---

### 2. 获取文章详情

**GET** `/article/view.php?id=123`

**认证要求：** ✗ 无（但有权限限制）

**权限规则：**
- 匿名文章：任何人可访问
- 登录用户文章：仅本人和管理员可访问
- 已删除文章：404 Not Found

**响应（成功）：**

```json
{
  "success": true,
  "data": {
    "id": 123,
    "title": "如何使用AI提高写作效率",
    "content": "## 引言\n\n人工智能技术...",
    "requirements": "介绍三种AI工具...",
    "word_limit": 1000,
    "style": "formal",
    "output_format": "default",
    "api_source": "deepseek",
    "user_id": null,
    "generated_at": "2024-03-24 14:30:00",
    "word_count": 998,
    "ratings_count": 5,
    "avg_rating": 4.2
  }
}
```

---

### 3. 获取文章历史

**GET** `/article/history.php?page=1&limit=10`

**认证要求：** ✓ 需登录

**参数说明：**

| 参数 | 类型 | 默认 | 说明 |
|---|---|---|---|
| page | number | 1 | 页码（从1开始） |
| limit | number | 10 | 每页数量（1-100） |

**响应（成功）：**

```json
{
  "success": true,
  "message": "获取文章历史成功",
  "data": {
    "items": [
      {
        "id": 125,
        "title": "最新文章",
        "word_count": 1200,
        "generated_at": "2024-03-24 16:00:00",
        "ratings_count": 2,
        "avg_rating": 4.5
      },
      {
        "id": 123,
        "title": "如何使用AI提高写作效率",
        "word_count": 998,
        "generated_at": "2024-03-24 14:30:00",
        "ratings_count": 5,
        "avg_rating": 4.2
      }
    ],
    "total": 15,
    "pages": 2,
    "current_page": 1,
    "per_page": 10
  }
}
```

---

### 4. 删除文章

**POST** `/article/delete.php`

**认证要求：** ✓ 需登录

**请求体：**

```json
{
  "article_id": 123
}
```

**响应（成功）：**

```json
{
  "success": true,
  "message": "文章已删除"
}
```

**权限规则：**
- 普通用户：只能删除自己的文章
- 管理员：可删除任何文章

**级联删除：**
删除文章时，相关的评分记录也会一起删除。

---

## 评分接口

### 提交评分

**POST** `/rating/submit.php`

**认证要求：** ✗ 无

**请求体：**

```json
{
  "article_id": 123,
  "rating": 4,
  "comment": "写得很好，深入浅出"
}
```

**参数说明：**

| 参数 | 类型 | 必需 | 说明 |
|---|---|---|---|
| article_id | number | ✓ | 文章ID |
| rating | number | ✓ | 评分（1-5，整数） |
| comment | string | ✗ | 评论（最多500字） |

**响应（成功）：**

```json
{
  "success": true,
  "message": "评分成功",
  "data": {
    "rating_id": 456,
    "article_id": 123,
    "rating": 4,
    "comment": "写得很好，深入浅出",
    "rated_at": "2024-03-24 14:35:00"
  }
}
```

**响应（失败）：**

```json
{
  "success": false,
  "message": "文章不存在",
  "errors": {
    "article_id": "无效的文章ID"
  }
}
```

**验证规则：**
- `rating` 必须是 1-5 之间的整数
- `comment` 最多500字符
- 每个IP+文章只能评分一次（防止重复）

---

## 话题接口

### 生成话题

**POST** `/topic/generate.php`

**认证要求：** ✗ 无

**速率限制：** ⚠️ 每IP每小时最多10次

**请求体：**

```json
{
  "industry": "互联网",
  "count": 7
}
```

**参数说明：**

| 参数 | 类型 | 必需 | 说明 |
|---|---|---|---|
| industry | string | ✓ | 行业名称 |
| count | number | ✓ | 生成数量（5-10） |

**响应（成功）：**

```json
{
  "success": true,
  "message": "话题生成成功",
  "data": {
    "industry": "互联网",
    "topics": [
      "如何评估创业公司的技术选型",
      "2024年Web开发的新趋势",
      "云计算在初创企业中的应用",
      "前端性能优化的最佳实践",
      "开源项目的商业化路径",
      "DevOps工程师的核心技能",
      "AI在软件测试中的应用"
    ]
  }
}
```

**响应（限流）：**

```json
{
  "success": false,
  "message": "请求过于频繁，请稍后再试",
  "errors": {
    "rate_limit": "超出限制"
  }
}
```

HTTP状态码：429

**特殊说明：**
- 话题直接在前端显示，不保存到数据库
- 用户可将喜欢的话题保存到localStorage
- 点击"使用话题"按钮可跳转到首页并预填标题

---

## 润色接口

### 润色文章需求

**POST** `/polish/requirements.php`

**认证要求：** ✗ 无

**速率限制：** ⚠️ 每IP每小时最多20次

**请求体：**

```json
{
  "requirements": "写一篇关于ai的文章，要求1000字左右，要讲清楚神经网络..."
}
```

**参数说明：**

| 参数 | 类型 | 必需 | 说明 |
|---|---|---|---|
| requirements | string | ✓ | 原始需求描述 |

**响应（成功）：**

```json
{
  "success": true,
  "message": "需求润色成功",
  "data": {
    "original": "写一篇关于ai的文章，要求1000字左右，要讲清楚神经网络...",
    "polished": "撰写一篇关于人工智能（AI）的深度技术文章，具体要求如下：\n\n1. 核心内容\n- 详细介绍神经网络的基本原理和架构...",
    "tokens_used": 150
  }
}
```

**用途：**
- 帮助用户优化和扩展文章需求描述
- 提高最终生成文章的质量和准确性
- 用户可以直接使用润色后的需求来生成文章

---

## 公告接口

### 获取公告

**GET** `/announcement/get.php`

**认证要求：** ✗ 无

**响应（有公告）：**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "content": "系统已升级到v1.0，新增用户认证和个人中心功能",
    "is_active": 1,
    "updated_at": "2024-03-24 10:00:00"
  }
}
```

**响应（无公告）：**

```json
{
  "success": true,
  "data": null
}
```

**用途：**
- 在网站顶部显示重要公告
- 用于系统维护提示、功能更新等

---

### 更新公告

**POST** `/announcement/update.php`

**认证要求：** ✓ 需管理员权限

**请求体：**

```json
{
  "content": "系统正在进行定期维护，预计今晚24点完成"
}
```

**参数说明：**

| 参数 | 类型 | 必需 | 说明 |
|---|---|---|---|
| content | string | ✓ | 公告内容（1-5000字） |

**响应（成功）：**

```json
{
  "success": true,
  "message": "公告已更新"
}
```

**权限控制：**
- 仅管理员用户可修改公告
- 非管理员访问返回403

---

## 管理接口

### 获取统计数据

**GET** `/admin/stats.php`

**认证要求：** ✓ 需管理员权限

**响应（成功）：**

```json
{
  "success": true,
  "data": {
    "total_articles": 245,
    "total_users": 12,
    "total_ratings": 89,
    "avg_rating": 4.3,
    "recent_ratings": [
      {
        "id": 1,
        "rating": 5,
        "comment": "非常好",
        "title": "如何学习AI",
        "rated_at": "2024-03-24 16:30:00"
      }
    ],
    "rating_distribution": [
      {"rating": 5, "count": 45},
      {"rating": 4, "count": 30},
      {"rating": 3, "count": 10},
      {"rating": 2, "count": 3},
      {"rating": 1, "count": 1}
    ]
  }
}
```

---

### 获取用户列表

**GET** `/admin/users.php?page=1&limit=20`

**认证要求：** ✓ 需管理员权限

**参数说明：**

| 参数 | 类型 | 默认 | 说明 |
|---|---|---|---|
| page | number | 1 | 页码 |
| limit | number | 20 | 每页数量（1-100） |

**响应（成功）：**

```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "email": "user1@example.com",
        "nickname": "User One",
        "role": "user",
        "is_active": 1,
        "created_at": "2024-03-20 10:00:00",
        "last_login": "2024-03-24 15:30:00"
      }
    ],
    "total": 12,
    "pages": 1,
    "current_page": 1,
    "per_page": 20
  }
}
```

---

## 错误处理

### HTTP状态码

| 状态码 | 说明 | 常见原因 |
|---|---|---|
| 200 | OK | 请求成功 |
| 400 | Bad Request | 参数无效 |
| 401 | Unauthorized | 未登录或Token无效 |
| 403 | Forbidden | 权限不足 |
| 404 | Not Found | 资源不存在 |
| 405 | Method Not Allowed | 请求方法不允许 |
| 429 | Too Many Requests | 请求过于频繁（速率限制） |
| 500 | Server Error | 服务器错误 |

### 错误响应示例

```json
{
  "success": false,
  "message": "邮箱已被注册",
  "errors": {
    "email": "该邮箱已存在"
  }
}
```

### 常见错误

**CSRF验证失败**
```json
{
  "success": false,
  "message": "CSRF validation failed"
}
```
解决：确保请求包含正确的 `X-CSRF-Token` 头

**未登录**
```json
{
  "success": false,
  "message": "需要登录"
}
```
HTTP状态码：401
解决：先调用 `/auth/login.php`

**权限不足**
```json
{
  "success": false,
  "message": "您没有权限执行此操作"
}
```
HTTP状态码：403
解决：确保用户具有admin角色

**限流**
```json
{
  "success": false,
  "message": "请求过于频繁，请稍后再试"
}
```
HTTP状态码：429
解决：等待1小时后重试

---

## 速率限制

### 限制规则

| 端点 | 限制 | 周期 | 说明 |
|---|---|---|---|
| /topic/generate.php | 10次 | 1小时 | 防止话题生成滥用 |
| /polish/requirements.php | 20次 | 1小时 | 防止润色功能滥用 |
| /api/auth/login.php | 10次 | 1小时 | 防止暴力破解 |

### 速率限制响应

**超出限制时：**

```json
{
  "success": false,
  "message": "请求过于频繁，请稍后再试",
  "errors": {
    "rate_limit": "超出限制"
  }
}
```

HTTP状态码：429

**响应头：** （可选）
```
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1711270800
```

### 绕过方法

- 使用不同的IP地址（仅用于测试）
- 等待足够的时间
- 联系管理员提高限制

---

## JavaScript SDK示例

### 基础配置

```javascript
// 自动处理CSRF Token和认证
const API = {
  baseURL: '/api',

  async request(endpoint, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    const config = {
      method: options.method || 'GET',
      headers: {
        'Content-Type': 'application/json',
        ...options.headers
      },
      ...options
    };

    if (csrfToken && ['POST', 'PUT', 'DELETE'].includes(config.method)) {
      config.headers['X-CSRF-Token'] = csrfToken;
    }

    const response = await fetch(`${this.baseURL}${endpoint}`, config);
    const data = await response.json();

    if (!response.ok && data.message) {
      throw new Error(data.message);
    }

    return data;
  },

  // 认证接口
  auth: {
    register: (email, password, nickname) =>
      API.request('/auth/register.php', {
        method: 'POST',
        body: JSON.stringify({ email, password, nickname })
      }),

    login: (email, password) =>
      API.request('/auth/login.php', {
        method: 'POST',
        body: JSON.stringify({ email, password })
      }),

    logout: () =>
      API.request('/auth/logout.php', { method: 'POST' }),

    me: () => API.request('/auth/me.php')
  },

  // 文章接口
  article: {
    generate: (data) =>
      API.request('/article/generate.php', {
        method: 'POST',
        body: JSON.stringify(data)
      }),

    history: (page = 1, limit = 10) =>
      API.request(`/article/history.php?page=${page}&limit=${limit}`),

    view: (id) =>
      API.request(`/article/view.php?id=${id}`),

    delete: (id) =>
      API.request('/article/delete.php', {
        method: 'POST',
        body: JSON.stringify({ article_id: id })
      })
  },

  // 话题接口
  topic: {
    generate: (industry, count) =>
      API.request('/topic/generate.php', {
        method: 'POST',
        body: JSON.stringify({ industry, count })
      })
  }
};

// 使用示例
try {
  const result = await API.auth.login('user@example.com', 'password');
  if (result.success) {
    console.log('登录成功', result.data);
  }
} catch (error) {
  console.error('登录失败', error);
}
```

---

## 最佳实践

1. **始终检查 `success` 字段**
   ```javascript
   if (data.success) {
     // 处理成功
   } else {
     // 处理错误
   }
   ```

2. **实现适当的错误处理**
   ```javascript
   try {
     const data = await API.request(...);
   } catch (error) {
     console.error('API错误:', error);
     showUserError('操作失败，请稍后重试');
   }
   ```

3. **用户反馈**
   - 显示加载状态
   - 在成功时显示成功提示
   - 在失败时显示错误信息

4. **请求超时处理**
   ```javascript
   const controller = new AbortController();
   const timeoutId = setTimeout(() => controller.abort(), 30000);

   try {
     const response = await fetch(url, {
       signal: controller.signal
     });
   } finally {
     clearTimeout(timeoutId);
   }
   ```

---

**更多帮助：** 查看 [README.md](README.md) 或 [INSTALL.md](INSTALL.md)
