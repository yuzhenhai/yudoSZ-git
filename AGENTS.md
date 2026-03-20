# 项目上下文文档

## 项目概述

这是一个基于 **ThinkPHP 6.0** 框架开发的企业级销售管理系统，主要用于管理企业的销售业务、市场分析、生产管理、采购管理等多个业务模块。系统采用 RESTful API 架构，支持多语言（中文、英文、韩文、日文），使用 JWT 进行身份验证，并连接多个 SQL Server 数据库实例。

### 核心特性

- **框架**: ThinkPHP 6.0 (PHP 7.2+，兼容 PHP 8.1)
- **身份验证**: JWT (HS256 算法，Token 有效期 8-12 小时)
- **数据库**: SQL Server (支持多数据库实例切换)
- **多语言**: 中文(CHN)、英文(ENG)、韩文(KOR)、日文(JPN)
- **API 架构**: RESTful 风格，基于 Controller 的模块化设计

### 业务模块

系统包含以下主要业务模块：

- **businessInfo**: 业务信息管理（日报数据、销售状况）
- **designManage**: 设计管理（日报数据）
- **produceManage**: 生产管理
- **purchaseManage**: 采购管理
- **salesBusiness**: 销售业务（订单、报价、试用、交付等）
- **salesManage**: 销售管理（销售统计、市场分析、年度报表等）

## 项目结构

```
yudoSZ-git/
├── app/                        # 应用目录
│   ├── controller/             # 控制器
│   │   ├── Api.php            # API 基础控制器（JWT 验证）
│   │   ├── Index.php          # 首页控制器（版本检查、语言文件构建）
│   │   ├── User.php           # 用户控制器（登录、设备注册、密码修改）
│   │   ├── KAPPAPI.php        # KAPP API 控制器
│   │   ├── Menu.php           # 菜单控制器
│   │   ├── Test.php           # 测试控制器
│   │   └── modules/           # 业务模块控制器
│   │       ├── Base.php       # 模块基础类
│   │       ├── businessInfo/  # 业务信息模块
│   │       ├── designManage/  # 设计管理模块
│   │       ├── produceManage/ # 生产管理模块
│   │       ├── purchaseManage/# 采购管理模块
│   │       ├── salesBusiness/ # 销售业务模块
│   │       └── salesManage/   # 销售管理模块
│   ├── model/                 # 模型
│   │   ├── BaseModel.php      # 基础模型
│   │   ├── UserModel.php      # 用户模型
│   │   ├── MenuModel.php      # 菜单模型
│   │   └── [各业务模块模型]/
│   ├── common/                # 公共工具类
│   │   ├── FtpUtil.php        # FTP 工具
│   │   ├── JlampMail.php      # 邮件工具
│   │   └── Util.php           # 通用工具
│   ├── BaseController.php     # 控制器基类
│   ├── AppService.php         # 应用服务
│   ├── Request.php            # 请求类
│   ├── ExceptionHandle.php    # 异常处理
│   ├── middleware.php         # 中间件配置
│   └── service.php            # 服务配置
├── config/                    # 配置目录
│   ├── app.php               # 应用配置
│   ├── appmenu.php           # 菜单配置
│   ├── jwt.php               # JWT 配置
│   ├── statuscode.php        # 状态码配置
│   ├── zh-cn.php             # 中文配置
│   ├── route.php             # 路由配置
│   └── lang/                 # 语言文件
│       ├── LANG_CHN.json     # 中文语言包
│       ├── LANG_ENG.json     # 英文语言包
│       └── LANG_KOR.json     # 韩文语言包
├── route/                     # 路由目录
│   ├── app.php               # 主路由文件
│   └── [各业务模块路由].php
├── public/                    # Web 根目录
│   ├── index.php             # 入口文件
│   └── static/               # 静态资源
├── vendor/                    # Composer 依赖
├── composer.json              # Composer 配置
├── think                      # 命令行工具
└── .env                       # 环境变量
```

## 核心架构

### 控制器层次

1. **BaseController**: 所有控制器的基类
   - 提供数据验证方法 `validate()`
   - 提供 JWT 用户信息获取方法 `getUserId()`, `getLangID()`
   - 提供语言包构建方法 `builders()`, `doBuilder()`
   - 提供状态码获取方法 `getCode()`

2. **Api**: API 基础控制器（继承 BaseController）
   - 在构造函数中自动验证 JWT Token
   - 提供数据库连接选择方法 `db()`
   - 提供存储过程调用方法 `call()`
   - 提供多语言获取方法 `Language()`

3. **modules/Base**: 业务模块基类（继承 Api）
   - 提供百分比颜色渲染方法 `percentColor()`, `percentColorDetail()`
   - 提供金额格式化方法 `formatAmt()`
   - 提供语言代码转换方法 `langCode()`
   - 提供系统类代码映射 `systemClass()`

4. **业务模块控制器**: 具体业务实现（继承 modules/Base）

### 数据库配置

系统连接多个 SQL Server 数据库实例，通过 `db()` 方法切换：

- **SZ**: sqlSZsrv
- **GD**: sqlGDsrv
- **QD**: sqlQDsrv
- **XR**: sqlXRsrv
- **HS**: sqlHSsrv
- **LLSZ**: sqlRASZsrv
- **SH/LL/CL/ABE**: sqlYCHsrv
- **默认**: sqlsrv

### 身份验证流程

1. **登录**: 用户通过 `User::login()` 或 `User::login2()` 登录
   - 验证用户权限
   - 验证用户名密码
   - 验证设备码
   - 生成 JWT Token（包含 username, langID, exp）
   - 返回 Token 和用户信息

2. **API 访问**: 所有继承 `Api` 的控制器在构造函数中自动验证 JWT
   - 从 `Authorization` 头部获取 Bearer Token
   - 验证 Token 有效性
   - 解码获取用户信息存储到 `$this->user`

3. **密码修改**: 通过 `User::changePassword()` 修改密码
   - 需要验证当前密码
   - 验证 JWT Token

### 多语言实现

- 语言包文件: `config/lang/LANG_{LANGID}.json`
- 支持: CHN（中文）、ENG（英文）、KOR（韩文）、JPN（日文）
- JWT Token 中包含 `langID` 字段
- 通过 `Api::Language($LangID)` 或 `BaseController::Language($LangID)` 获取语言包
- 语言包构建: 通过 `BaseController::builders()` 从数据库 `brpWordInfo` 表构建语言包

### 状态码系统

- 配置文件: `config/statuscode.php`
- 获取方法: `BaseController::getCode($key)`
- 常用状态码:
  - SUCCESS: 成功
  - TOKEN_NULL: Token 为空
  - TOKEN_EXPIRED: Token 过期
  - AUTH_FAILURE: 认证失败
  - DEVICE_FAILURE: 设备验证失败
  - DB_OPERATION_FAIL: 数据库操作失败

## 构建和运行

### 环境要求

- PHP >= 7.2.5
- Composer
- SQL Server 数据库
- Web 服务器（Apache/Nginx）

### 安装依赖

```bash
composer install
```

### 运行开发服务器

```bash
php think run
```

### 常用命令

```bash
# 服务发现
php think service:discover

# 发布配置
php think vendor:publish

# 清除缓存
php think clear

# 构建语言包
php think Index/LangFile
```

### 环境配置

复制 `.example.env` 为 `.env` 并配置：

```env
APP_DEBUG = true
[APP]
DEFAULT_TIMEZONE = Asia/Shanghai

[DATABASE]
TYPE = sqlsrv
HOSTNAME = your_host
DATABASE = your_database
USERNAME = your_username
PASSWORD = your_password

[JWT]
SECRET = your_jwt_secret
```

## 开发约定

### 编码规范

- 使用 PHP 7 强类型（严格模式）
- 遵循 PSR-4 自动加载规范
- 使用 ThinkPHP 6.0 命名空间约定
- 控制器类名使用大驼峰（PascalCase）
- 方法名使用小驼峰（camelCase）

### 目录结构规范

- 控制器: `app/controller/` 或 `app/controller/modules/{模块}/`
- 模型: `app/model/` 或 `app/model/{模块}/`
- 路由: `route/`（每个业务模块一个文件）
- 配置: `config/`

### API 开发规范

1. 所有需要身份验证的 API 控制器继承 `Api`
2. 业务模块控制器继承 `modules\Base`
3. 使用 `$this->user` 获取当前用户信息
4. 使用 `$this->getLangID()` 获取当前语言
5. 使用 `$this->db($DB)` 切换数据库
6. 使用 `$this->call($DB, $spName, $input, $output)` 调用存储过程
7. 使用 `self::getCode($key)` 获取状态码
8. 返回格式:
   ```json
   {
     "statusCode": 200,
     "message": "成功",
     "data": {}
   }
   ```

### 路由规范

- 路由文件按业务模块分离在 `route/` 目录
- 主路由文件 `route/app.php` 会自动引入其他路由文件
- 使用 `Route::post()`, `Route::get()` 定义路由
- 示例:
  ```php
  Route::post('login', 'User/login');
  Route::get('getLanguage', 'User/getLanguage');
  ```

### 数据库操作规范

- 使用 ThinkORM 进行数据库操作
- 使用 `Db::connect($db)->query()` 执行 SQL 查询
- 使用存储过程处理复杂业务逻辑
- 数据库配置在 `config/database.php`

### 多语言开发规范

- 语言包键名使用中文拼音或英文
- 在 `config/zh-cn.php` 中定义默认键名列表
- 语言包构建从 `brpWordInfo` 表读取
- 支持的语言代码: CHN, ENG, KOR, JPN

### 安全规范

- 所有 API 使用 JWT 验证（除登录、设备注册等接口）
- 使用参数化查询防止 SQL 注入
- 敏感信息（JWT 密钥）存储在环境变量
- 使用 HTTPS 传输数据
- 设备码验证限制访问

## 关键技术点

### JWT Token 生成和验证

```php
// 生成 Token
$key = Config::get('jwt.secret');
$payload = [
    'iat' => time(),
    'exp' => time() + 43200,
    'username' => $username,
    'langID' => $langID,
];
$token = JWT::encode($payload, $key, 'HS256');

// 验证 Token
$decoded = JWT::decode($token, new Key($key, 'HS256'));
```

### 多数据库连接

```php
// 切换数据库
$db = $this->db('SZ'); // 返回 sqlSZsrv

// 执行查询
$result = Db::connect($db)->query("EXEC spName @param1 = ?, @param2 = ?", [$value1, $value2]);
```

### 存储过程调用

```php
public function call($DB, $spName, $input, $output) {
    return Db::connect($DB)->query("EXEC $spName $input;", $output);
}
```

### 多语言实现

```php
// 获取语言包
$langs = json_decode(file_get_contents(app()->getRootPath() . 'config/lang/LANG_' . $LangID . '.json'), true);
$lists = Config::get('zh-cn');

// 构建语言包
$res = [];
foreach ($lists as $k => $v) {
    $res[$k] = isset($langs[$v]) ? $langs[$v] : '';
}
```

## 测试和调试

### 开发模式

在 `.env` 中设置:
```env
APP_DEBUG = true
```

### 日志记录

使用 `Log::record()` 或 `Log::info()` 记录日志，日志文件在 `runtime/log/`。

### 调试工具

- 使用 `dump()` 或 `dd()` 输出变量
- 使用 Symfony VarDumper (`symfony/var-dumper`)
- 使用 ThinkTrace (`topthink/think-trace`)

## 常见问题

### JWT Token 过期

- Token 默认有效期为 8-12 小时
- 过期后需要重新登录获取新 Token
- 在 `User::login()` 和 `User::login2()` 中设置过期时间

### 数据库连接失败

- 检查 `.env` 中的数据库配置
- 确认 SQL Server 服务正在运行
- 检查数据库用户权限

### 语言包更新

- 修改 `brpWordInfo` 表后，运行 `php think Index/LangFile` 重建语言包
- 语言包文件在 `config/lang/` 目录

### 路由不生效

- 确认路由文件在 `route/` 目录
- 确认 `route/app.php` 正确引入了路由文件
- 清除路由缓存: `php think clear`

## 贡献指南

1. 遵循项目编码规范
2. 新增业务模块需要在 `app/controller/modules/` 和 `route/` 创建对应文件
3. 修改配置后需要清除缓存
4. 提交前确保代码通过测试
5. 添加必要的注释说明

## 联系方式

- 项目仓库: https://github.com/yuzhenhai/yudoSZ-git.git
- 问题反馈: 通过 GitHub Issues

## 许可证

Apache-2.0