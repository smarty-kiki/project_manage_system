# view/ 模板目录约定

## 目录组织

每个模块一个子文件夹，按业务模块命名（小写英文）：

```
view/
├── index/          # 首页模块
├── user/           # 用户模块
├── order/          # 订单模块
├── layout/         # 公共布局（header/footer 等复用部件）
├── error/          # 错误页面（404、500 等）
└── blade/          # Blade 编译缓存（自动生成，勿手动修改）
```

新建模块时在 `view/` 下创建对应文件夹。

## 错误页面

`view/error/` 用于放置 HTTP 错误页模板。文件名按 HTTP 状态码命名（带 `.php` 扩展名）：

| 状态码 | 文件名 | 示例 |
|--------|--------|------|
| 404 Not Found | `404.php` | `view/error/404.php` |
| 500 Server Error | `500.php` | `view/error/500.php` |

如项目重新定义了交互风格和样式，可以更新一下错误页面的实现。

## 文件命名

CRUD 页面推荐用以下命名：

| 用途 | 文件名 | 示例 |
|------|--------|------|
| 列表页 | `list.php` | `view/user/list.php` |
| 详情页 | `detail.php` | `view/user/detail.php` |
| 新增页 | `add.php` | `view/user/add.php` |
| 编辑页 | `edit.php` | `view/user/edit.php` |

非 CRUD 页面使用小写字母 + 下划线命名，如 `order_confirm.php`。

## render 调用方式

路由闭包中通过 `render()` 渲染模板，第二个参数传关联数组：

```php
render('user/list', [
    'users' => $users,
    'title' => '用户列表',
]);
```

视图路径相对于 `view/`，省略 `.php`，路径分隔用 `/`。模板中通过变量名直接访问传入的键值。

## 公共布局封装

### 组织方式

将 header、footer、sidebar 等复用部件放在 `view/layout/` 目录下：

```
view/layout/
├── header.php      # 全局头部
├── footer.php      # 全局页脚
├── sidebar.php     # 侧边栏
└── pagination.php  # 分页组件
```

### 引用方式

其他页面通过 `@include` 引用公共部件（路径相对于 `view/`，不带 `.php` 扩展名）：

```blade
@include('layout/header')

    <h1>用户列表</h1>
    <!-- 页面主体内容 -->

@include('layout/footer')
```

注意：`@include` 必须带括号和引号，写作 `@include('路径')`。

### include 传参

`@include` 编译为 PHP 原生 `include`，被引入的模板**自动继承父模板的所有变量**（包括 `render()` 传入的参数和父模板中已赋值的变量）。因此无需额外语法即可传参。

例如分页组件 `view/layout/pagination.php`：

```php
<div class="pagination">
    @foreach ($pages as $page)
        <a href="?page={{ $page }}">{{ $page }}</a>
    @endforeach
</div>
```

使用时只需确保 `$pages` 已存在于作用域：

```blade
@include('layout/header')

    <table>...</table>
    @include('layout/pagination')  <!-- 直接使用父模板已有的 $pages -->

@include('layout/footer')
```

如果需要在引入前覆盖或追加变量，用 `@php` 块赋值：

```blade
@php
    $page_title = '用户列表';
@endphp
@include('layout/header')  <!-- header.php 中可直接使用 $page_title -->
```

## 项目 Blade 语法参考

本项目 Blade 是自实现的轻量引擎（`frame/view_blade.php`），**仅支持以下语法**，不要使用 Laravel 文档中才有的功能（如 `@extends`、`@section`、`@yield`、`@csrf` 等均不支持）。

### 输出

```
{{ $var }}
```

输出变量值，支持 `or` 语法设置默认值：

```
{{ $var or '默认值' }}
```

编译为 `isset($var) ? $var : '默认值'`。

如果需要在页面中显示 `{{ }}` 字面量而非解析，在前面加 `@`：

```
@{{ 这段不会被解析 }}
```

### 转义输出

```
{{{ $var }}}
```

输出经 `htmlentities()` 转义的内容，防止 XSS。

### 条件判断

```
@if ($condition)
    ...
@elseif ($other)
    ...
@else
    ...
@endif
```

`@unless` / `@endunless`：

```
@unless ($condition)
    ...
@endunless
```

### 循环

```
@foreach ($items as $item)
    <li>{{ $item }}</li>
@endforeach

@for ($i = 0; $i < 10; $i++)
    <span>{{ $i }}</span>
@endfor

@while ($condition)
    ...
@endwhile
```

### 引入子模板

```
@include('layout/header')
```

路径相对于 `view/`，不加 `.php`。编译阶段即展开为 PHP `include`，被引入模板自动继承当前作用域所有变量，无需显式传参。如果需向引入模板传递专属变量，在 `@include` 前用 `@php` 块赋值即可生效。

### 原生 PHP 块

```
@php
    $result = some_calculation();
@endphp
```

### 注释

```
{{-- 这是注释，不会出现在渲染输出中 --}}
```

## 新建模块步骤

1. 在 `view/` 下创建模块文件夹，如 `view/user/`
2. 按 CRUD 命名创建模板：`list.php`、`add.php`、`edit.php`、`detail.php`
3. 优先引用 `view/layout/` 下的公共 header/footer，避免重复封装
4. 如模块有独立布局部件，在模块文件夹内创建并用 `@include('user/xxx')` 引用
