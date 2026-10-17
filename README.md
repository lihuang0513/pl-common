# pl-common

可被多个 PHP 项目直接引入的公共业务库。公共类不继承 Phalcon 类，不创建第二套框架应用。

本次验证链路：`TestController → TestService → TestModel → PDO → 本地 MySQL`，成功返回纯文本 `test`。
查询使用绑定参数和 `DATABASE()`，不建表、不读取或修改业务记录。数据库连接失败会抛出异常，不伪造成功结果。

## 目录

```text
pl-common/
├── bootstrap.php
├── config/
│   ├── defaults.php
│   ├── config.example.php
│   └── local.php               # 本地配置，Git 忽略
└── src/
    ├── modules/
    │   └── TestController.php
    ├── services/
    │   └── TestService.php
    ├── models/
    │   └── TestModel.php
    └── library/
        ├── Autoloader.php
        ├── Config.php
        ├── Database.php
        └── RedisClient.php
```

`modules` 中平铺多个 Controller。Controller 提供公共调用入口，Service 处理业务规则，Model 封装查询。
不设置路由、HTTP 响应、异常处理器、时区或宿主全局常量。公共库的异常由宿主在调用边界处理。

## 首次接入

在宿主仓库添加子模块，放在 Web 根目录之外：

```sh
git submodule add -b main git@github.com:lihuang0513/pl-common.git shared/pl-common
cp shared/pl-common/config/config.example.php shared/pl-common/config/local.php
```

只在本地配置中填写数据库凭据。也可由运行环境设置 `PL_COMMON_CONFIG`，指向 Web 根目录之外的独立 PHP 配置文件。
配置优先级：显式 `new Config($config_path)` > `PL_COMMON_CONFIG` > 公共库自身 `config/local.php`。
未提供配置时仅注册类加载器仍然可用，实际访问未配置资源会报错。

```php
<?php

use CompanyCommon\Modules\TestController;

// 在宿主入口引入一次公共加载器；此时不连接数据库或 Redis
require_once sprintf('%s/shared/pl-common/bootstrap.php', __DIR__);

// 调用公共控制器，经过服务和模型执行真实查询；异常由宿主统一处理
$controller = new TestController();
$message = $controller->indexAction();
```

根据入口实际目录调整 `bootstrap.php` 路径即可。宿主可以直接实例化其他 `CompanyCommon` 下的公共类。
公共 Controller 返回数据，不执行 `echo`、`exit` 或发送响应头；HTTP 输出由原项目负责。

## 配置与惰性连接

- 引入 `bootstrap.php`：仅追加公共命名空间加载器，重复引入不会重复注册。
- 实例化 Controller、Service、Model、Database、RedisClient：只保存显式传入的依赖，不自动加载或实例化下游类。
- 实际调用各层方法时，才创建这一层需要的对象并在当前实例内复用；配置和连接也继续保持惰性。
- 执行模型查询：首次读取配置并建立独立 PDO 连接。
- 调用 `RedisClient::getConnection()`：才检查扩展、连接、认证、选库和设置键前缀。
- 每个工厂实例按连接名称复用连接，不使用全局连接池；批量业务可复用同一工厂实例。
- 不使用宿主的 `db`、`redis`、`config` 服务，不自动参与宿主数据库事务。
- 资源故障会正常报错；“惰性加载”保证未使用资源时不因资源缺失出错，不保证调用失败时永不抛异常。

命名空间映射严格限制为：

| 命名空间 | 目录 |
| --- | --- |
| `CompanyCommon\Modules\` | `src/modules/` |
| `CompanyCommon\Services\` | `src/services/` |
| `CompanyCommon\Models\` | `src/models/` |
| `CompanyCommon\Library\` | `src/library/` |

宿主原有的 `Models`、`Services`、`Library` 不会被这份加载器接管。
同一 PHP 进程只能加载一份 `CompanyCommon` 实现，不应同时引入两个不同提交的公共库。

## 子模块拉取与更新

新克隆宿主仓库后：

```sh
git submodule update --init --recursive
```

拉取公共仓库 `main` 的最新代码：

```sh
git submodule update --remote --checkout shared/pl-common
```

本项目不要求发布语义版本或标签，但 Git submodule 本身会记录提交指针。
普通 `git pull` 或 `git submodule update --init` 不会自动跟随公共库最新提交。
需要保持宿主可复现时，把更新后的 gitlink 提交到宿主仓库。

如果后续需要“公共库发布后各环境自动更新”，由云效 Flow 在公共库发布成功后触发消费环境更新任务。
消费任务统一获取上游指定的同一提交/制品，并完成 FPM、OPcache、常驻进程刷新；仅本次子模块接入不会自动建立流水线。
不要让每个节点各自无约束地拉取随时变化的分支头。

## 运行与兼容范围

第一版使用 PHP 7.1 可解析的语法；已在 PHP 7.1.33、Phalcon 3.4.5 宿主环境验证。
公共库自身不要求 Phalcon。数据库探测需要 PDO 和 pdo_mysql；只有使用 Redis 时才需要 phpredis。
`TestModel` 的 `DATABASE()` 是 MySQL 查询示例，其他数据库需要对应的 Model 实现。
不承诺兼容全部 PHP/扩展版本，部署前应在实际运行版本上验证。

本地直接验证：

```sh
php -r 'require "bootstrap.php"; echo (new CompanyCommon\Modules\TestController())->indexAction(), PHP_EOL;'
```

跨仓验证入口（仅允许本机访问）：

- plapi：`GET /common/test`。
- pl-new：`GET /CommonTest.php`，也可直接使用 PHP CLI 执行该文件。

成功响应为 HTTP 200、`text/plain`、正文 `test`；数据库失败响应为 HTTP 503 和固定错误文本，原始凭据不输出到 HTTP。
