# CLAUDE.md — domain/

领域层：entity（ActiveRecord）、DAO、知识库。

## 目录结构

```
domain/
  entity/           # 实体类（ActiveRecord，继承 entity 基类）
  dao/              # 数据访问对象（继承 dao 基类）
  knowledge/        # 知识库文件
  autoload.php      # 领域层类自动加载映射
  load.php          # 入口（被 bootstrap.php include）
```

## entity（ActiveRecord）

每个数据库表对应一个 entity 类，命名约定为蛇形小写（与表名一致）。

### 最小模板

```php
class demo extends entity
{
    public $structs = [
        'name' => '',
    ];

    public static function create($name): demo
    {
        $demo = parent::init();

        $demo->name = $name;

        return $demo;
    }
}
```

### 内置字段（entity 基类管理，无需在 structs 中声明）

`id` — 内存计数自增生成的主键（bigint）
`version` — 乐观锁版本号（从 0 开始，每次更新 +1）
`create_time` — 创建时间（datetime）
`update_time` — 更新时间（datetime）
`delete_time` — 软删除时间（datetime，null 表示未删除）

### 实体状态判断

```php
$entity->just_new()       // 尚未持久化（version === 0）
$entity->just_updated()   // 内存值已变更（attributes != structs）
$entity->is_deleted()     // 已软删除
$entity->is_not_deleted() // 未软删除
$entity->just_deleted()   // 当前请求内被软删除
$entity->is_null()        // 是 null_entity，往往代表没有 find 出来实体
$entity->is_not_null()        // 不是 null_entity，往往代表 find 出来了数据实体
```

### 工厂方法 create()

约定为每个 entity 编写静态 `create()` 工厂方法，必填字段作为参数：

```php
public static function create($name): demo
{
    $demo = parent::init();

    $demo->name = $name;

    return $demo;
}
```

调用 `parent::init()` 自动生成 id、设置 version=0、create_time/update_time 为当前时间。

### 软删除与硬删除

```php
$entity->delete();       // 软删除（设置 delete_time）
$entity->restore();      // 恢复软删除
$entity->force_delete(); // 标记为硬删除（下次 unit_of_work 提交时执行 DELETE FROM）
```

### JSON 序列化

`jsonSerialize()` 返回 `id, version, create_time, update_time, delete_time` + 所有属性值。控制器中直接 `return $entity` 即可输出 JSON。

## dao

每个 entity 对应一个 dao 类，命名约定：`{entity_name}_dao`。

### 最小模板

```php
class demo_dao extends dao
{
    protected $table_name = 'demo';
    protected $db_config_key = 'default';
}
```

`table_name` 与迁移创建的数据库表名一致，框架规范为单数名词而非复数名词。
`db_config_key` 对应 `config/mysql.php` 中的数据库连接 key。
dao 构造函数自动从类名推导 `$class_name`（去掉 `_dao` 后缀）。

### 查询方法

```php
// 单条查询（不存在返回 null_entity）
$entity = dao('demo')->find_by_id($id);

// 按列名查询
$entity = dao('demo')->find_by_column(['name' => 'test']);

// 多条查询，查询返回的是一个装有实体的数组，数组的 key 是对应实体的 id，方便后续通过 id 直接从数组中获得对应的实体
$entities = dao('demo')->find_all();
$entities = dao('demo')->find_all_order_by_id_desc();

// 按列名查询多条
$entities = dao('demo')->find_all_by_column(['user_id' => $user_id]);

// 分页
list($list, $pagination) = dao('demo')->find_all_paginated_by_current_page_and_column($page, $size, ['status' => 1]);

// 计数
$count = dao('demo')->count();

// 含已删除记录，dao 方法第二个参数是 with_deleted 参数
$entities = dao('demo', true)->find_all();
```

## knowledge

对实体操作、复杂业务逻辑的封装位置，以纯函数形式实现，按模块拆分文件。

### 编写方式

```php
// domain/knowledge/demo.php

function do_something_with_demo(demo $demo, $param): array
{
    $demo->status = 1;
    $related = dao('other')->find_all_by_column(['demo_id' => $demo->id]);

    return ['demo' => $demo, 'related' => $related];
}
```

- 函数命名使用蛇形小写，动词在前
- 参数和返回值使用类型声明
- 函数内部可操作 entity、调用 dao、触发 unit_of_work

### 加载

新增 knowledge 文件后，在 `load.php` 中 `include`：

```php
include __DIR__.'/autoload.php';
include __DIR__.'/knowledge/demo.php';
```

> 控制器和路由闭包中可直接调用 knowledge 函数，无需 use 或 import。

## 实体关系

在 entity 构造函数 `public function __construct()` 中定义：

```php
// 一对一（当前实体拥有子实体）
$this->has_one('profile', 'user_profile', 'user_id');

// 反向一对一（当前实体属于父实体）
$this->belongs_to('creator', 'user', 'creator_id');

// 一对多
$this->has_many('orders', 'order', 'user_id');
```

关系是懒加载的，首次通过 `__get` 访问时查询并缓存。
每个关系自动生成 `_with_deleted` 变体（如 `orders_with_deleted`）以包含软删除关联实体。

### 批量加载（防止后续遍历 $entities 时共产生 N+1 条 SQL）

```php
relationship_batch_load($entities, 'relationship.chain');
```

## 自动加载（autoload.php）

新增 entity 或 dao 后，必须在 `autoload.php` 的 `$class_maps` 中注册映射：

```php
$class_maps = [
    'demo_dao' => 'dao/demo.php',
    'demo'     => 'entity/demo.php',
];
```

## Unit of Work 持久化机制

**所有持久化操作通过 `unit_of_work()` 完成。** 控制器的 `if_verify` 拦截器已自动包裹，无需手动调用。

### 工作原理

1. 执行闭包期间，所有实体变更（new/update/delete）记录在本地缓存中
2. 闭包结束后扫描缓存：
   - 新实体 → `INSERT`
   - 已修改实体 → `UPDATE ... WHERE id = :id AND version = :old_version`
   - 已软删除实体 → `UPDATE ... SET delete_time = ...`
   - 已硬删除实体 → `DELETE`
3. 乐观锁：若 UPDATE 影响 0 行（version 已变更），抛出异常
4. 事务提交：多语句时自动包裹事务

### 手动使用

```php
unit_of_work(function () {
    $demo = demo::create('test');
});
// 无需手动 save，闭包结束时自动 commit
```

### 生命周期钩子

```php
if_unit_of_work_executed(function () {
    // unit of work 成功后执行
});

if_unit_of_work_disturbed(function (\Exception $e) {
    // unit of work 异常后执行
});
```
通常是需要在 controller 代码中就对新创建或修改的数据对象要抛队列任务时使用，会在工作单元提交后才执行

## null entity 模式

`dao('demo')->find_by_id($id)` 查询不存在的记录时返回 `null_entity` 实例而非 null，避免空指针：

```php
$entity = dao('demo')->find_by_id($id);
if ($entity->is_null()) {
    // 记录不存在
}
// 访问 null_entity 的任何属性返回另一个 null_entity，不会报错
```

## 与迁移的对应关系

每个 entity 对应一张数据库表，需创建迁移文件（`command/migration/sql/` 目录下）。建表 SQL 约定：

- 必须含 `id`, `version`, `create_time`, `update_time`, `delete_time` 五个系统列
- 引擎使用 InnoDB，字符集 utf8mb4

运行迁移：

```bash
php public/cli.php migrate
```

## 编码约定

- entity 类名与表名一致，蛇形小写
- dao 类名 = `{entity_name}_dao`
- 工厂方法命名为 `create()`，必填参数前置
- 数组使用 `[]` 短语法
- 不在 entity 中编写 SQL —— 复杂查询通过 dao 的 `find_by_sql` / `find_by_condition` 等方法实现
