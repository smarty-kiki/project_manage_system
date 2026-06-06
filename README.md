# php-vibe-coding-frame
单层 MVC 框架, 供快速开发使用

## 目录结构及文件说明
```
.
├── bootstrap.php (框架通用加载文件)
├── command (命令行命令文件目录)
│   ├── migration (数据库迁移文件目录)
│   │   ├── migrate.php (migrate 命令)
│   │   └── sql (迁移 SQL 文件目录)
│   │       ├── merged (已合并 SQL 文件归档)
│   │       └── tmp (待合并 SQL 文件)
│   ├── queue (队列相关目录)
│   │   ├── queue_job (队列 job 文件目录)
│   │   └── queue.php (queue 命令)
│   └── entity.php (entity 命令)
├── config (配置文件目录)
│   ├── development (开发环境配置覆盖目录)
│   ├── production (线上环境配置覆盖目录)
│   ├── beanstalk.php (队列 beanstalk 配置文件)
│   ├── blade.php (模板引擎配置文件)
│   ├── error_code.php (错误码定义)
│   ├── log.php (日志配置文件)
│   ├── mysql.php (数据库 mysql 配置文件)
│   └── redis.php (存储 redis 配置文件)
├── controller (控制器文件目录)
│   └── base.php (helloworld 控制器)
├── domain (领域层目录)
│   ├── dao (DAO 层文件目录)
│   ├── entity (实体层文件目录)
│   ├── knowledge (知识层文件目录)
│   ├── autoload.php (领域层自动加载)
│   └── load.php (领域层加载文件)
├── frame (框架目录)
├── interceptor (拦截器目录)
├── project (项目相关文件目录)
│   ├── config (配置文件目录)
│   │   ├── development (开发环境)
│   │   │   ├── bash (bash 补全脚本)
│   │   │   ├── nginx (nginx 配置)
│   │   │   │   └── php-vibe-coding-frame.conf
│   │   │   └── supervisor (supervisor 配置)
│   │   │       ├── php-vibe-coding-frame_queue_worker.conf
│   │   │       └── queue_job_watch.conf
│   │   └── production (线上环境)
│   │       ├── nginx
│   │       │   └── php-vibe-coding-frame.conf
│   │       └── supervisor
│   │           └── php-vibe-coding-frame_queue_worker.conf
│   └── tool (工具脚本目录)
│       ├── classmap.sh (生成 ORM 类映射文件)
│       ├── naming_project.sh (基于当前项目创建新项目，批量替换项目名称)
│       ├── start_development_server.sh (基于 docker 快速启动开发环境)
│       ├── development (开发环境辅助脚本)
│       │   └── after_env_start.sh (容器启动后执行的初始化脚本)
│       └── production (线上环境脚本)
│           ├── after_push.sh (部署后执行的脚本)
│           └── check_update.sh (检查更新的脚本)
├── public (入口文件目录)
│   ├── assets (静态资源目录)
│   │   ├── css
│   │   ├── img
│   │   └── js
│   ├── cli.php (命令行入口文件)
│   └── index.php (web 请求入口文件)
├── util (工具类文件目录)
│   ├── autoload.php (工具类自动加载)
│   └── load.php (工具类加载文件)
├── view (模板文件目录)
│   ├── blade (blade 编译缓存目录)
│   └── index (首页模板)
│       └── index.php
├── CLAUDE.md
├── LICENSE
└── README.md
```

## 10 秒看到 helloworld

1. 先将代码 clone 或者下载到本地
2. 确保机器上有 docker 环境
3. 执行代码中的脚本快速启动环境 `sh project/tool/start_development_server.sh`
4. 输入当前用户密码。此处是为了开发方便映射了 80 和 3306 端口，若不允许使用 80 可以手动修改第三条提到的脚本更换端口
