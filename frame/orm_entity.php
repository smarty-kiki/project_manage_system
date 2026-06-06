<?php

// 关系定义中自动追加的含软删除变体的后缀
define('ENTITY_RELATIONSHIP_DELETED_SUFFIX', '_with_deleted');
// struct 校验器校验失败时的错误码
define('ENTITY_STRUCT_VALIDATOR_ERROR_CODE', 'ENTITY_STRUCT_VALIDATOR_ERROR');
// 实体通用默认错误码
define('ENTITY_DEFAULT_ERROR_CODE', 'ENTITY_DEFAULT_ERROR');

// ActiveRecord 实体基类，内置 id/version/create_time/update_time/delete_time 系统字段，支持乐观锁、软删除、懒加载关系
abstract class entity implements JsonSerializable, Serializable
{
    /*{{{*/
    const INIT_VERSION = 0;

    public $id;                     // 主键，通过 Redis INCR 生成
    public $version;                // 乐观锁版本号
    public $create_time;            // 创建时间
    public $update_time;            // 更新时间
    public $delete_time;            // 软删除时间，null 表示未删除

    private $just_deleted;          // 当前请求内被软删除标记
    private $just_force_deleted;    // 当前请求内被强制删除标记

    public $structs = [];           // 数据库中原始值，用于脏检查
    public $attributes = [];        // 当前内存值，与 structs 比较判断 just_updated()

    public static $null_entity_mock_attributes = []; // null_entity 访问时的模拟属性值

    private $relationships = [];    // 已加载的关系对象缓存
    private $relationship_refs = []; // 关系定义引用（has_one/belongs_to/has_many）

    // 初始化新实体，分配 ID、设置时间戳和 version=0，写入本地缓存
    protected static function init()
    {
        $static = new static();
        $static->attributes = $static->structs;
        $static->id = self::generate_id();
        $static->version = self::INIT_VERSION;
        $static->create_time = $static->update_time = datetime();
        $static->delete_time = null;

        $static->just_deleted = false;
        $static->just_force_deleted = false;

        local_cache_set($static);

        return $static;
    }

    // 生成分布式唯一 ID，通过 Redis INCR 批量取号
    final public static function generate_id()
    {
        return generate_id(get_called_class());
    }

    // 是否为新实体（尚未持久化），version === 0
    final public function just_new()
    {
        return self::INIT_VERSION === $this->version;
    }

    // 检查实体是否有未持久化的变更，attributes 与 structs 不一致时返回 true
    final public function just_updated()
    {
        return $this->attributes != $this->structs;
    }

    // 是否已被软删除，delete_time 不为 null
    final public function is_deleted()
    {
        return ! is_null($this->delete_time);
    }

    // 是否未被软删除
    final public function is_not_deleted()
    {
        return is_null($this->delete_time);
    }

    // 当前请求内是否刚执行了软删除
    final public function just_deleted()
    {
        return $this->just_deleted;
    }

    // 软删除，设置 delete_time 和 just_deleted 标记，子类可重写以添加业务逻辑
    public function delete()
    {
        $this->just_deleted = true;
        $this->delete_time = datetime();
    }

    // 恢复软删除，清除 delete_time 和 just_deleted 标记
    final public function restore()
    {
        $this->just_deleted = false;
        $this->delete_time = null;
    }

    // 当前请求内是否被强制删除（物理删除）
    final public function just_force_deleted()
    {
        return $this->just_force_deleted;
    }

    // 强制删除（物理删除），设置 just_force_deleted 标记
    final public function force_delete()
    {
        $this->just_force_deleted = true;
    }

    // entity 基类的 is_null 始终返回 false，由 null_entity 重写返回 true
    public function is_null()
    {
        return false;
    }

    // 是否不是 null_entity
    public function is_not_null()
    {
        return ! $this->is_null();
    }

    // 获取当前实体对应的 DAO 实例
    final public function get_dao()
    {
        return dao(get_class($this));
    }

    // JSON 序列化，合并系统字段和 attributes
    public function jsonSerialize(): array
    {
        return array_merge([
            'id' => $this->id,
            'version' => $this->version,
            'create_time' => $this->create_time,
            'update_time' => $this->update_time,
            'delete_time' => $this->delete_time,
        ], $this->attributes);
    }

    // Serializable 接口：序列化时排除 relationships 避免循环引用
    public function serialize()
    {
        $serializable = get_object_vars($this);

        unset($serializable['relationships']);

        return serialize($serializable);
    }

    // Serializable 接口：反序列化恢复各属性
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        foreach($unserialized as $property => $value) {

            $this->{$property} = $value;

        }
    }

    // PHP 7.4+ 序列化接口，排除 relationships 避免循环引用
    public function __serialize(): array
    {
        $serializable = get_object_vars($this);

        unset($serializable['relationships']);

        return $serializable;
    }

    // PHP 7.4+ 反序列化接口
    public function __unserialize(array $data): void
    {
        foreach ($data as $property => $value) {

            $this->{$property} = $value;

        }
    }

    // 属性访问器，优先级：get_{property} 方法 → 已加载关系 → 懒加载关系 → attributes
    public function __get($property)
    {
        $method = "get_$property";

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if (isset($this->relationships[$property])) {
            return $this->relationships[$property];
        }

        if (isset($this->relationship_refs[$property])) {
            return $this->load_relationship_from_ref($property);
        }

        return $this->attributes[$property];
    }

    // 属性设置器，先调用 prepare_set_{property} 预处理，再处理关系更新或写入 attributes
    final public function __set($property, $value)
    {
        $method = "prepare_set_$property";

        if (method_exists($this, $method)) {
            $value = $this->$method($value);
        }

        if (isset($this->relationship_refs[$property])) {

            if (isset($this->relationships[$property])) {

                $this->relationship_refs[$property]->update($value, $this, $this->relationships[$property]);
            } else {
                $this->relationship_refs[$property]->update($value, $this);
            }

            return $this->relationships[$property] = $value;
        }

        if (array_key_exists($property, $this->attributes)) {

            return $this->attributes[$property] = $value;
        }
    }

    // 仅允许 unset 已加载的关系对象，不允许 unset 普通属性
    final public function __unset($property)
    {
        otherwise(
            isset($this->relationships[$property]),
            '只能 unset 实体的关联关系',
            'exception',
            ENTITY_DEFAULT_ERROR_CODE
        );

        unset($this->relationships[$property]);
    }

    // 检查属性、关系或 getter 方法是否存在
    final public function __isset($property)
    {
        $method = "get_$property";

        return isset($this->attributes[$property])
            || isset($this->relationships[$property])
            || method_exists($this, $method);
    }

    // 【私有方法，禁止在其他类中调用】通过 relationship_ref 懒加载关系并缓存
    private function load_relationship_from_ref($relationship_name)
    {
        $relationship_ref = $this->relationship_refs[$relationship_name];

        return $this->relationships[$relationship_name] = $relationship_ref->load($this);
    }

    // 定义 has_one 关系：当前实体拥有一个子实体，foreign_key 在子实体表中，默认 {当前实体}_id，同时自动注册 _with_deleted 变体
    protected function has_one($relationship_name, ?string $entity_name = null, ?string $foreign_key = null)
    {
        $self_entity_name = get_class($this);

        if (is_null($entity_name)) {
            $entity_name = $relationship_name;
        }

        if (is_null($foreign_key)) {
            $foreign_key = $self_entity_name.'_id';
        }

        $this->relationship_refs[$relationship_name] = instance('has_one', [$entity_name, $foreign_key]);
        $this->relationship_refs[$relationship_name.ENTITY_RELATIONSHIP_DELETED_SUFFIX] = instance('has_one', [$entity_name, $foreign_key, true]);
    }

    // 定义 belongs_to 关系：当前实体属于一个父实体，foreign_key 在当前实体表中，默认 {关系名}_id，同时自动注册 _with_deleted 变体
    protected function belongs_to($relationship_name, ?string $entity_name = null, ?string $foreign_key = null)
    {
        if (is_null($entity_name)) {
            $entity_name = $relationship_name;
        }

        if (is_null($foreign_key)) {
            $foreign_key = $entity_name.'_id';
        }

        $this->relationship_refs[$relationship_name] = instance('belongs_to', [$entity_name, $foreign_key]);
        $this->relationship_refs[$relationship_name.ENTITY_RELATIONSHIP_DELETED_SUFFIX] = instance('belongs_to', [$entity_name, $foreign_key, true]);
    }

    // 定义 has_many 一对多关系：当前实体拥有多个子实体，foreign_key 在子实体表中，默认 {当前实体}_id，同时自动注册 _with_deleted 变体
    protected function has_many($relationship_name, ?string $entity_name = null, ?string $foreign_key = null)
    {
        $self_entity_name = get_class($this);

        if (is_null($entity_name)) {
            $entity_name = $relationship_name;
        }

        if (is_null($foreign_key)) {
            $foreign_key = $self_entity_name.'_id';
        }

        $this->relationship_refs[$relationship_name] = instance('has_many', [$entity_name, $foreign_key]);
        $this->relationship_refs[$relationship_name.ENTITY_RELATIONSHIP_DELETED_SUFFIX] = instance('has_many', [$entity_name, $foreign_key, true]);
    }

    // 批量加载指定关系，防止 N+1 查询，从多个 from_entities 一次查出所有关联实体并注入
    public function relationship_batch_load($relationship_name, array $from_entities)
    {
        otherwise(
            array_key_exists($relationship_name, $this->relationship_refs),
            'entity ['.get_class($this).'] has not a relationship called ['.$relationship_name.']',
            'exception',
            ENTITY_DEFAULT_ERROR_CODE
        );

        $relationship_ref = $this->relationship_refs[$relationship_name];

        return $relationship_ref->batch_load($from_entities, $relationship_name);
    }

}/*}}}*/

// 空实体（Null Object 模式），id=0，访问任何属性返回另一个 null_entity，避免空指针判断
class null_entity extends entity
{
    /*{{{*/
    public $id = 0;

    private $mock_entity_name = null; // 模拟的实体名，用于属性访问时提供模拟值

    // 创建 null_entity，mock_entity_name 用于查找 $null_entity_mock_attributes 中的模拟属性
    public static function create(?string $mock_entity_name = null)
    {
        $null_entity = new static;
        $null_entity->mock_entity_name = $mock_entity_name;

        return $null_entity;
    }

    // null_entity 重写返回 true
    public function is_null()
    {
        return true;
    }

    // 调用任意方法静默忽略，返回 null
    public function __call($method, $args)
    {
        return;
    }

    // 访问任意属性返回以属性名为 mock_entity_name 的新 null_entity，支持链式访问
    public function __get($property)
    {
        $mock_entity_name = $this->mock_entity_name;

        if (! is_null($mock_entity_name)) {

            $null_entity_mock_attribute_list = $mock_entity_name::$null_entity_mock_attributes;

            if (array_key_exists($property, $null_entity_mock_attribute_list)) {

                return $null_entity_mock_attribute_list[$property];
            }
        }

        return self::create($property);
    }

    // 转为字符串时返回中文'空'
    public function __toString()
    {
        return '空';
    }
}/*}}}*/

// 关系引用抽象类，定义懒加载、批量加载、更新关系三个核心操作
abstract class relationship_ref
{
    /*{{{*/
    abstract public function load(entity $from_entity);
    abstract public function batch_load(array $from_entity, $relationship_name);
    abstract public function update($values, entity $from_entity, $old_value);
}/*}}}*/

// has_one 关系：父实体拥有一个子实体，通过子实体表的 foreign_key 关联父实体 id
class has_one extends relationship_ref
{
    /*{{{*/
    private $entity_name;
    private $foreign_key;
    private $with_deleted;       // 是否包含软删除的关联实体

    public function __construct($entity_name, $foreign_key, $with_deleted = false)
    {
        $this->entity_name = $entity_name;
        $this->foreign_key = $foreign_key;
        $this->with_deleted = $with_deleted;
    }

    public function load(entity $from_entity)
    {
        return dao($this->entity_name, $this->with_deleted)->find_by_foreign_key($this->foreign_key, $from_entity->id);
    }

    public function batch_load(array $from_entities, $relationship_name)
    {
        $ids = array_keys($from_entities);

        $entities = dao($this->entity_name, $this->with_deleted)->find_all_by_foreign_keys($this->foreign_key, $ids);

        foreach ($entities as $entity) {

            $from_entity = $from_entities[$entity->{$this->foreign_key}];

            $from_entity->{$relationship_name} = $entity;
        }

        return $entities;
    }

    public function update($entity, entity $from_entity, $old_entity = null)
    {
        if ($old_entity instanceof entity) {
            $old_entity->{$this->foreign_key} = 0;
        }

        if ($entity instanceof entity) {
            $entity->{$this->foreign_key} = $from_entity->id;
        }
    }
}/*}}}*/

// belongs_to 关系：子实体属于一个父实体，通过当前实体表的 foreign_key 关联父实体 id
class belongs_to extends relationship_ref
{
    /*{{{*/
    private $entity_name;
    private $foreign_key;
    private $with_deleted;       // 是否包含软删除的关联实体

    public function __construct($entity_name, $foreign_key, $with_deleted = false)
    {
        $this->entity_name = $entity_name;
        $this->foreign_key = $foreign_key;
        $this->with_deleted = $with_deleted;
    }

    public function load(entity $from_entity)
    {
        return dao($this->entity_name, $this->with_deleted)->find($from_entity->{$this->foreign_key});
    }

    public function batch_load(array $from_entities, $relationship_name)
    {
        $ids_keys = [];

        foreach ($from_entities as $from_entity) {

            if ($from_entity instanceof entity && $from_entity->is_not_null()) {

                $ids_keys[$from_entity->{$this->foreign_key}] = null;
            }
        }

        $ids = array_keys($ids_keys);

        $entities = dao($this->entity_name, $this->with_deleted)->find($ids);

        foreach ($from_entities as $from_entity) {

            if (array_key_exists($from_entity->{$this->foreign_key}, $entities)) {

                $from_entity->{$relationship_name} = $entities[$from_entity->{$this->foreign_key}];
            }
        }

        return $entities;
    }

    public function update($entity, entity $from_entity, $old_entity = null)
    {
        if ($entity instanceof entity) {
            $from_entity->{$this->foreign_key} = $entity->id;
        } else {
            $from_entity->{$this->foreign_key} = 0;
        }
    }
}/*}}}*/

// has_many 一对多关系：父实体拥有多个子实体，通过子实体表的 foreign_key 关联父实体 id
class has_many extends relationship_ref
{
    /*{{{*/
    private $entity_name;
    private $foreign_key;
    private $with_deleted;       // 是否包含软删除的关联实体

    public function __construct($entity_name, $foreign_key, $with_deleted = false)
    {
        $this->entity_name = $entity_name;
        $this->foreign_key = $foreign_key;
        $this->with_deleted = $with_deleted;
    }

    public function load(entity $from_entity)
    {
        return dao($this->entity_name, $this->with_deleted)->find_all_by_foreign_key($this->foreign_key, $from_entity->id);
    }

    public function batch_load(array $from_entities, $relationship_name)
    {
        $ids = array_keys($from_entities);

        $entities = dao($this->entity_name, $this->with_deleted)->find_all_by_foreign_keys($this->foreign_key, $ids);

        $entities_indexed_by_foreign_key = [];

        foreach ($entities as $entity) {

            $foreign_id = $entity->{$this->foreign_key};

            if (! array_key_exists($foreign_id,  $entities_indexed_by_foreign_key)) {

                $entities_indexed_by_foreign_key[$foreign_id] = [];
            }

            $entities_indexed_by_foreign_key[$foreign_id][$entity->id] = $entity;
        }

        foreach ($from_entities as $from_entity) {

            $from_entity->{$relationship_name} = $entities_indexed_by_foreign_key[$from_entity->id] ?? [];
        }

        return $entities;
    }

    public function update($entities, entity $from_entity, $old_entities = [])
    {
        foreach ($old_entities as $old_entity) {
            $old_entity->{$this->foreign_key} = $from_entity->id;
        }

        foreach ($entities as $entity) {
            $entity->{$this->foreign_key} = $from_entity->id;
        }
    }
}/*}}}*/

// DAO 基类，封装 ActiveRecord 的 CRUD 操作，内置本地缓存和软删除过滤
class dao
{
    /*{{{*/
    protected $class_name;       // 对应的实体类名（去掉 _dao 后缀）
    protected $table_name;       // 数据库表名
    protected $db_config_key;    // 数据库配置 key
    protected $with_deleted;     // 是否包含软删除记录

    public function __construct()
    {/*{{{*/
        $this->class_name = substr(get_class($this), 0, -4);
    }/*}}}*/

    // 设置是否包含软删除记录
    public function set_with_deleted($with_deleted)
    {/*{{{*/
        $this->with_deleted = $with_deleted;
    }/*}}}*/

    // 生成过滤软删除的 SQL 片段，alias 为表别名，with_deleted 为 true 时返回空字符串
    final protected function with_deleted_sql(?string $alias = null)
    {/*{{{*/
        $alias = $alias ? $alias.'.': '';

        if ($this->with_deleted) {
            return '';
        } else {
            return 'and '.$alias.'delete_time is null';
        }
    }/*}}}*/

    // 查找实体，传 id 查单个，传 id 数组批量查询，返回 key 为 id 的实体数组
    public function find($id_or_ids)
    {/*{{{*/
        if (is_array($ids = $id_or_ids)) {
            return $this->find_all_by_ids($ids);
        } else {
            return $this->find_by_id($id = $id_or_ids);
        }
    }/*}}}*/

    // 【私有方法，禁止在其他类中调用】按 id 查找单个实体，先查本地缓存再查库，未找到返回 null_entity
    private function find_by_id($id)
    {/*{{{*/
        if (empty($id)) {
            return null_entity::create($this->class_name);
        }

        $entity = local_cache_get($this->class_name, $id);

        if (is_null($entity)) {

            $with_deleted_sql = '';
            if (! $this->with_deleted) {
                $with_deleted_sql = 'and delete_time is null';
            }

            $row = db_query_first('select * from `'.$this->table_name.'` where id = :id '.$with_deleted_sql, [':id' => $id], $this->db_config_key);
            if ($row) {
                $entity = $this->row_to_entity($row);
                local_cache_set($entity);
            } else {
                $entity = null_entity::create($this->class_name);
            }
        }

        return $entity;
    }/*}}}*/

    // 按列值查找单个实体，columns 为 [列名 => 值] 关联数组
    public function find_by_column(array $columns)
    {/*{{{*/
        if (! $this->with_deleted) {
            $columns['delete_time'] = null;
        } else {
            unset($columns['delete_time']);
        }

        list($where, $binds) = db_simple_where_sql($columns);

        return $this->find_by_sql('select * from `'.$this->table_name."` where $where order by id", $binds);
    }/*}}}*/

    // 按外键查找单个实体
    public function find_by_foreign_key(string $foreign_key, $value)
    {/*{{{*/
        $with_deleted_sql = '';
        if (! $this->with_deleted) {
            $with_deleted_sql = 'and delete_time is null';
        }

        $sql_template = "select * from `$this->table_name` where $foreign_key = :foreign_key $with_deleted_sql";

        return $this->find_by_sql($sql_template, [
            ':foreign_key' => $value,
        ]);
    }/*}}}*/

    // 按外键值批量查找实体，IN 查询防止 N+1
    public function find_all_by_foreign_keys(string $foreign_key, array $values)
    {/*{{{*/
        $with_deleted_sql = '';
        if (! $this->with_deleted) {
            $with_deleted_sql = 'and delete_time is null';
        }

        $sql_template = "select * from `$this->table_name` where $foreign_key in :foreign_keys $with_deleted_sql";

        return $this->find_all_by_sql($sql_template, [
            ':foreign_keys' => $values,
        ]);
    }/*}}}*/

    // 按自定义 WHERE 条件查找单个实体
    protected function find_by_condition($condition, array $binds = [])
    {/*{{{*/
        return $this->find_by_sql('select * from `'.$this->table_name.'` where '.$condition, $binds);
    }/*}}}*/

    // 按完整 SQL 查找单个实体，先查本地缓存再查库
    protected function find_by_sql($sql_template, array $binds = [])
    {/*{{{*/
        $row = db_query_first($sql_template, $binds, $this->db_config_key);

        if (empty($row)) {
            return null_entity::create($this->class_name);
        }

        $entity = local_cache_get($this->class_name, $row['id']);
        if (!is_null($entity)) {
            return $entity;
        }

        $entity = $this->row_to_entity($row);
        local_cache_set($entity);

        return $entity;
    }/*}}}*/

    // 【私有方法，禁止在其他类中调用】按 id 数组批量查找，使用 find_in_set 保持返回顺序
    private function find_all_by_ids(array $ids)
    {/*{{{*/
        if (empty($ids)) {
            return [];
        }

        $with_deleted_sql = '';
        if (! $this->with_deleted) {
            $with_deleted_sql = 'and delete_time is null';
        }

        $sql = [
            'sql_template' => 'select * from `'.$this->table_name.'` where id in :ids '.$with_deleted_sql.' order by find_in_set(id, :set)',
            'binds' => [
                ':ids' => $ids,
                ':set' => implode(',', $ids),
            ],
        ];

        $rows = db_query($sql['sql_template'], $sql['binds'], $this->db_config_key);

        $entities = [];

        foreach ($rows as $row) {
            $entity = local_cache_get($this->class_name, $row['id']);
            if (is_null($entity)) {
                $entity = $this->row_to_entity($row);
                local_cache_set($entity);
            }
            $entities[$entity->id] = $entity;
        }

        return $entities;
    }/*}}}*/

    // 查询所有记录，按 id 升序，返回 key 为 id 的实体数组
    public function find_all()
    {/*{{{*/
        $with_deleted_sql = '';
        if (! $this->with_deleted) {
            $with_deleted_sql = 'where delete_time is null';
        }

        return $this->find_all_by_sql('select * from `'.$this->table_name.'` '.$with_deleted_sql.' order by id', []);
    }/*}}}*/

    // 查询所有记录，按 id 降序
    public function find_all_order_by_id_desc()
    {/*{{{*/
        $with_deleted_sql = '';
        if (! $this->with_deleted) {
            $with_deleted_sql = 'where delete_time is null';
        }

        return $this->find_all_by_sql('select * from `'.$this->table_name.'` '.$with_deleted_sql.' order by id desc', []);
    }/*}}}*/

    // 按列值查询所有匹配记录，columns 为空时返回全部
    public function find_all_by_column(array $columns)
    {/*{{{*/
        if ($columns) {

            if (! $this->with_deleted) {
                $columns['delete_time'] = null;
            } else {
                unset($columns['delete_time']);
            }

            list($where, $binds) = db_simple_where_sql($columns);

            return $this->find_all_by_sql('select * from `'.$this->table_name."` where $where order by id", $binds);
        } else {
            return $this->find_all();
        }
    }/*}}}*/

    // 按外键值查询所有匹配记录
    public function find_all_by_foreign_key(string $foreign_key, $value)
    {/*{{{*/
        $with_deleted_sql = '';
        if (! $this->with_deleted) {
            $with_deleted_sql = 'and delete_time is null';
        }

        $sql_template = "select * from `$this->table_name` where $foreign_key = :foreign_key $with_deleted_sql";

        return $this->find_all_by_sql($sql_template, [
            ':foreign_key' => $value,
        ]);
    }/*}}}*/

    // 按自定义 WHERE 条件查询所有匹配记录
    protected function find_all_by_condition($condition, array $binds = [])
    {/*{{{*/
        return $this->find_all_by_sql('select * from `'.$this->table_name.'` where '.  $condition, $binds);
    }/*}}}*/

    // 按完整 SQL 查询所有记录，返回 key 为 id 的实体数组，优先从本地缓存取
    protected function find_all_by_sql($sql_template, array $binds = [])
    {/*{{{*/
        $entities = [];

        $rows = db_query($sql_template, $binds, $this->db_config_key);

        foreach ($rows as $row) {
            $entity = local_cache_get($this->class_name, $row['id']);
            if (is_null($entity)) {
                $entity = $this->row_to_entity($row);
                local_cache_set($entity);
            }
            $entities[$entity->id] = $entity;
        }

        return $entities;
    }/*}}}*/

    // 按 SQL 查询并按 group_key 分组返回，格式为 [group_value => [id => entity]]
    protected function find_all_grouped_entities_by_sql($group_key, $sql_template, array $binds = [])
    {/*{{{*/
        $entities = [];

        $rows = db_query($sql_template, $binds, $this->db_config_key);

        foreach ($rows as $row) {
            $entity = local_cache_get($this->class_name, $row['id']);
            if (is_null($entity)) {
                $entity = $this->row_to_entity($row);
                local_cache_set($entity);
            }

            $group_value = $entity->{$group_key};

            if (! isset($entities[$group_value])) {
                $entities[$group_value] = [];
            }
            $entities[$group_value][$entity->id] = $entity;
        }

        return $entities;
    }/*}}}*/

    // 按列值条件分页查询，返回 ['list' => 实体数组, 'pagination' => 分页信息]
    public function find_all_paginated_by_current_page_and_column($current_page, $page_size, array $columns)
    {/*{{{*/
        $res = [
            'list' => [],
            'pagination' => [
                'page_size' => $page_size,
                'current_page' => $current_page,
                'count' => 0,
                'pages' => 0,
            ],
        ];

        list($condition, $binds) = db_simple_where_sql($columns);
        $count = $this->count_by_condition($condition, $binds);
        if (! $count) {
            return $res;
        } else {
            $res['pagination']['count'] = $count;
            $res['pagination']['pages'] = ceil($count / $page_size);
        }

        $offset = $page_size * ($current_page - 1);

        $res['list'] = $this->find_all_by_condition($condition." limit $offset, $page_size", $binds);

        return $res;
    }/*}}}*/

    // 按自定义条件分页查询，condition 为 SQL WHERE 片段
    public function find_all_paginated_by_current_page_and_condition($current_page, $page_size, $condition, array $binds = [])
    {/*{{{*/
        $res = [
            'list' => [],
            'pagination' => [
                'page_size' => $page_size,
                'current_page' => $current_page,
                'count' => 0,
                'pages' => 0,
            ],
        ];

        $count = $this->count_by_condition($condition, $binds);
        if (! $count) {
            return $res;
        } else {
            $res['pagination']['count'] = $count;
            $res['pagination']['pages'] = ceil($count / $page_size);
        }

        $offset = $page_size * ($current_page - 1);

        $res['list'] = $this->find_all_by_condition($condition." limit $offset, $page_size", $binds);

        return $res;
    }/*}}}*/

    // 统计记录总数，自动过滤软删除
    public function count()
    {/*{{{*/
        $with_deleted_sql = '';
        if (! $this->with_deleted) {
            $with_deleted_sql = 'where delete_time is null';
        }

        $sql = 'select count(*) as count from `'.$this->table_name.'` '.$with_deleted_sql;

        return db_query_value('count', $sql, [], $this->db_config_key);
    }/*}}}*/

    // 按条件统计记录数，自动过滤软删除
    protected function count_by_condition($condition, array $binds = [])
    {/*{{{*/
        $with_deleted_sql = 'where';
        if (! $this->with_deleted) {
            $with_deleted_sql = 'where delete_time is null and';
        }

        $sql = 'select count(*) as count from `'.$this->table_name.'` '.$with_deleted_sql.' '.$condition;

        return db_query_value('count', $sql, $binds, $this->db_config_key);
    }/*}}}*/

    // 【私有方法，禁止在其他类中调用】获取实体的脏数据（attributes 与 structs 的差异），含自动更新 version 和 update_time
    private function get_dirty($entity)
    {/*{{{*/
        $rows = [];

        foreach ($entity->attributes as $column => $value) {
            if ($entity->structs[$column] !== $value) {
                $rows[$column] = $value;
            }
        }

        $rows['version'] = $entity->version + 1;
        $rows['update_time'] = datetime();
        $rows['delete_time'] = $entity->delete_time;

        return $rows;
    }/*}}}*/

    // 【私有方法，禁止在其他类中调用】将数据库行转换为实体对象，剥离系统字段到对象属性，剩余字段存入 structs 和 attributes
    private function row_to_entity($rows)
    {/*{{{*/
        $entity = new $this->class_name();

        $entity->id = $rows['id'];
        $entity->version = $rows['version'];
        $entity->create_time = $rows['create_time'];
        $entity->update_time = $rows['update_time'];
        $entity->delete_time = $rows['delete_time'];

        unset($rows['id']);
        unset($rows['version']);
        unset($rows['create_time']);
        unset($rows['update_time']);
        unset($rows['delete_time']);

        $entity->attributes = $entity->structs = $rows;

        return $entity;
    }/*}}}*/

    // 获取 DAO 对应的数据库配置 key
    final public function get_db_config_key()
    {/*{{{*/
        return $this->db_config_key;
    }/*}}}*/

    // 为实体生成 INSERT SQL 模板和 binds，供 UnitOfWork 使用
    final public function dump_insert_sql($entity)
    {/*{{{*/
        $columns = $values = $binds = [];

        $insert = $entity->attributes + [
            'id' => $entity->id,
            'version' => $entity->version + 1,
            'create_time' => $entity->create_time,
            'update_time' => $entity->update_time,
        ];

        foreach ($insert as $column => $value) {
            $columns[] = $column;
            $values[] = ":$column";
            $binds[":$column"] = $value;
        }

        return [
            'sql_template' => 'insert into `'.$this->table_name.'` (`'.implode('`, `', $columns).'`) values ('.implode(', ', $values).')',
            'binds' => $binds,
        ];
    }/*}}}*/

    // 为实体生成 UPDATE SQL 模板和 binds，含乐观锁（WHERE version = :old_version），供 UnitOfWork 使用
    final public function dump_update_sql($entity)
    {/*{{{*/
        $binds = $update = [];

        $binds[':id'] = $entity->id;
        $binds[':old_version'] = $entity->version;

        foreach ($this->get_dirty($entity) as $column => $value) {
            $update[] = "`$column` = :$column";
            $binds[":$column"] = $value;
        }

        return [
            'sql_template' => 'update `'.$this->table_name.'` set '.implode(', ', $update).' where id = :id and version = :old_version',
            'binds' => $binds,
        ];
    }/*}}}*/

    // 为实体生成 DELETE SQL 模板和 binds（物理删除），供 UnitOfWork 使用
    final public function dump_delete_sql($entity)
    {/*{{{*/
        return [
            'sql_template' => 'delete from `'.$this->table_name.'` where id = :id',
            'binds' => [
                ':id' => $entity->id,
            ],
        ];
    }/*}}}*/
}/*}}}*/

// 获取 DAO 实例，class_name 为实体名（如 'user' → user_dao），with_deleted 为 true 时包含软删除记录
function dao($class_name, $with_deleted = false)
{
    $dao = instance($class_name.'_dao');
    $dao->set_with_deleted($with_deleted);

    return $dao;
}

// 【私有函数，禁止在其他文件中调用】生成本地缓存的 key
function _local_cache_key($entity_type, $id)
{
    return $entity_type.'_'.$id;
}

// 【私有函数，禁止在其他文件中调用】获取或设置本地缓存容器，传 null 获取，传数组设置
function _local_cache(?array $cached = null)
{
    static $container = [];

    if (is_null($cached)) {
        return $container;
    }

    return $container = $cached;
}

// 从本地缓存获取实体，未命中返回 null
function local_cache_get($entity_type, $id)
{
    $cached = _local_cache();

    $key = _local_cache_key($entity_type, $id);

    if (isset($cached[$key])) {
        return $cached[$key];
    }

    return;
}

// 检查本地缓存中是否存在指定实体
function local_cache_has($entity_type, $id)
{
    $cached = _local_cache();

    $key = _local_cache_key($entity_type, $id);

    return isset($cached[$key]);
}

// 获取所有本地缓存的实体
function local_cache_get_all()
{
    return _local_cache();
}

// 将实体写入本地缓存
function local_cache_set(entity $entity)
{
    $cached = _local_cache();

    $key = _local_cache_key(get_class($entity), $entity->id);

    $cached[$key] = $entity;

    _local_cache($cached);
}

// 从本地缓存中删除指定实体
function local_cache_delete($entity_type, $id)
{
    $cached = _local_cache();

    $key = _local_cache_key($entity_type, $id);

    unset($cached[$key]);

    _local_cache($cached);
}

// 清空所有本地缓存
function local_cache_delete_all()
{
    _local_cache([]);
}

// 清空本地缓存并返回清空前的所有缓存数据，供 UnitOfWork 使用
function local_cache_flush_all()
{
    $cached = _local_cache();

    local_cache_delete_all();

    return $cached;
}

// 从请求中获取实体，默认取参数 {entity_name}_id，$require 为 true 时找不到则报错，否则返回 null_entity
function input_entity($entity_name, $name = null, $require = false)
{
    if (is_null($name)) {
        $name = $entity_name.'_id';
    }

    if ($id = input($name)) {

        $entity = dao($entity_name)->find($id);

        otherwise_error_code(strtoupper($entity_name).'_NOT_FOUND', $entity->is_not_null());

        return $entity;
    }

    if ($require) {

        otherwise_error_code(strtoupper($entity_name).'_NOT_FOUND', false);
    } else {
        return null_entity::create($entity_name);
    }
}

// 链式批量加载关联关系，relationship_chain 用点号分隔（如 'creator.orders'），防止 N+1 查询，$entities 可为单个实体或实体数组
function relationship_batch_load($entities, $relationship_chain)
{
    if (empty($entities)) {
        return [];
    }

    if ($entities instanceof entity) {

        $entities = [$entities->id => $entities];
    }

    $relationships = explode('.', $relationship_chain);

    foreach ($relationships as $relationship) {

        if ($entity = reset($entities)) {

            $entities = $entity->relationship_batch_load($relationship, $entities);
        }
    }

    return $entities;
}       
