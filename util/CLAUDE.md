# util/ — 纯工具层

## 定位

`util/` 存放对外部系统、外部能力的封装，与业务逻辑和框架基础设施无关。

**与 frame/ 的区别**：`frame/` 是框架自身的核心（ORM、DB、Cache、Queue），`util/` 是"借用外部能力"的胶水代码。

## 哪些属于 util/

| 类别 | 示例 |
|------|------|
| 外部组件/SDK 调用 | 微信支付、支付宝、短信通道、邮件服务、OSS 对象存储 |
| 外部网站/API 调用 | 第三方 HTTP API 封装、Webhook 回调处理 |
| 基础系统能力封装 | 文件系统操作、加解密、验签、条形码/二维码生成 |
| 纯算法/纯工具函数 | 编码转换、数据格式化、无状态的通用计算 |

## 哪些不属于 util/

- 数据库操作 → 属于 `frame/`（ORM）或 `domain/`（DAO/Entity）
- 缓存、队列、日志 → 属于 `frame/`
- 业务逻辑 → 属于 `domain/`
- HTTP 路由、请求处理 → 属于 `controller/` 或 `interceptor/`

## 目录与加载约定

每个工具模块在 `util/` 下新建独立子目录：

```
util/
  load.php          # 加载函数式模块
  autoload.php      # 注册类映射（spl_autoload）
  wechat/           # 示例：微信支付
  aliyun_oss/       # 示例：阿里云 OSS
```

### 函数式模块 → load.php

模块以纯函数实现（无类），直接在 `load.php` 中 include：

```php
// util/load.php
include __DIR__.'/autoload.php';

// 函数式模块
include __DIR__.'/some_module/functions.php';
```

### 类模块 → autoload.php

模块包含类，在 `autoload.php` 的 `$class_maps` 中注册类名到文件路径的映射：

```php
// util/autoload.php
spl_autoload_register(function ($class_name) {
    $class_maps = [
        'wechat_pay'    => 'wechat/pay.php',
        'aliyun_oss'    => 'aliyun_oss/client.php',
    ];
    if (isset($class_maps[$class_name])) {
        include __DIR__.'/'.$class_maps[$class_name];
    }
});
```

## 编码约定

- 一个模块一个子目录，封装单一外部能力
- 纯静态方法，无状态，无副作用
- 所有外部调用的配置（密钥、endpoint、超时等）通过 `config()` 读取，不硬编码
- 外部调用必须设置超时，避免阻塞请求生命周期（PHP-FPM 同步模型）
