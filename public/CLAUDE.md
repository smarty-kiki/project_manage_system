# CLAUDE.md

## 目录定位

`public/` 是 Web 根目录，包含框架的两个入口文件。nginx/php-fpm 将请求路由到此目录。

## 入口文件

### index.php（HTTP 入口）

请求生命周期：
```
nginx → public/index.php → bootstrap.php → 注册错误/异常处理器
  → 注册 if_verify（unit_of_work 包裹） → 加载 controller/ → not_found()
```

关键行为：
- `if_verify` 将所有路由闭包包裹在 `unit_of_work()` 中，自动提交 Entity 变更并管理事务
- 路由闭包返回值决定响应：数组 → JSON，字符串 → HTML
- 异常处理区分 AJAX 和普通请求，分别返回 JSON 或 HTML
- 业务异常（`business_exception`）记录日志模块为 `business_exception`，其他异常记录完整堆栈
- 当前只加载 `controller/base.php`，新增路由文件需在此手动 `include`

### cli.php（CLI 入口）

命令行入口，用于运行迁移、队列 worker 等后台任务。

```
cli.php → bootstrap.php → 加载 cli_command + view_blade
  → 注册命令（migrate、entity、queue） → command_not_found()
```

已注册的命令：
- `migration/migrate.php` — 数据库迁移
- `entity.php` — Entity 相关操作
- `queue/queue.php` — 队列 worker

## assets/ 目录

静态资源目录，存放项目生成的 CSS、JS 及素材文件，由 nginx 直接返回（不经过 PHP-FPM），缓存 30 天。

```
assets/
├── css/
├── js/
└── img/
```

Blade 模板中通过 `/assets/` 绝对路径引用。
