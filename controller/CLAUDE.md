# controller/ CLAUDE.md

路由定义目录。根 CLAUDE.md 中已包含路由编写通用规则，本文件仅补充本目录特有的信息。

## 当前文件

```
base.php          # 基础路由：首页、健康检查、错误码映射
```

## 新增路由文件

按模块拆分，命名 `模块名.php`（如 `user.php`），在 `public/index.php` 中对应的 `include CONTROLLER_DIR.'/base.php'` 后面追加一行 `include`。

## 路由闭包返回值约定

路由闭包的返回值决定 HTTP 响应体内容和 `Content-Type` 头：

- **返回字符串**：框架将字符串直接作为响应体，并自动设置 `Content-Type: text/html`，请求方收到 HTML 文本。
- **返回其他**：框架将其他结果拼装到数组结构中一起 JSON 编码后作为响应体，并自动设置 `Content-Type: application/json`，请求方收到 JSON 数据。

```php
// 返回 JSON
if_get('/api/user/*', function ($user_id) {
    return ['id' => $user_id, 'name' => 'foo'];
});

// 返回 HTML
if_get('/', function () {
    return render('index/index', ['title' => 'hello world']);
});
```

`render('模板名', $data)` 读取 `view/` 下的 Blade 模板并返回 HTML 字符串，同样适用于上述字符串返回约定。

模板名取 `view/` 目录之后的相对路径，**去掉末尾的 `.php` 扩展名**。例如模板文件为 `view/index/index.php`，模板名为 `'index/index'`。

## 注意事项

路由文件中**不要封装函数**，所有逻辑直接写在路由闭包内：

- 复杂的数据操作和业务对象操作，封装到 `domain/knowledge` 中。
- 拦截类逻辑（如登录状态获取与判断），放到 `interceptor` 中。

## 变量

路由通配符 `*` 匹配单个路径段，按位置传递给闭包参数：

```php
if_get('/user/*/post/*', function ($user_id, $post_id) { ... });
```
