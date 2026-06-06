<?php

// ID 生成器使用的 Redis 配置中间件 key
const IDGENTER_CACHE_MIDWARE_KEY = 'idgenter';
// ID 生成器 Redis key 后缀，完整 key 为 {mark}_last_id
const IDGENTER_CACHE_KEY_SUFFIX = '_last_id';
// 工作单元乐观锁冲突时的默认错误码
const UNITOFWORK_DEFAULT_ERROR_CODE = 'UNITOFWORK_DEFAULT_ERROR';

// 设置或获取工作单元使用的数据库配置 key，用于跨库场景指定目标数据库，默认 'default'
function unit_of_work_db_config_key(?string $config_key = null)
{
    static $container = 'default';

    if (!is_null($config_key)) {
        $container = $config_key;
    }

    return $container;
}

// 【私有函数，禁止在其他文件中调用】执行单条写入 SQL 并校验影响行数为 1，行数不为 1 时抛乐观锁冲突异常
function _unit_of_work_write($sql_template, array $binds = [], $config_key = 'default')
{
    $row_count = db_write($sql_template, $binds, $config_key);

    otherwise(
        $row_count === 1,
        'data in unit of work is expired',
        'exception',
        UNITOFWORK_DEFAULT_ERROR_CODE
    );
}

// 注册工作单元成功提交后的回调，传入闭包时注册，传入 null 时获取，传入空数组时重置
function if_unit_of_work_executed($action = null)
{
    static $container = null;

    if (is_array($action)) {
        return $container = null;
    } else if (! empty($action)) {
        return $container = $action;
    }

    return $container;
}

// 注册工作单元异常后的回调，闭包接收 Exception 参数，传入 null 时获取，传入空数组时重置
function if_unit_of_work_disturbed($action = null)
{
    static $container = null;

    if (is_array($action)) {
        return $container = null;
    } else if (! empty($action)) {
        return $container = $action;
    }

    return $container;
}

// 清空工作单元的执行后和异常回调，通常在回调执行完毕后调用避免重复触发
function clean_unit_of_work_delegate()
{
    if_unit_of_work_executed([]);
    if_unit_of_work_disturbed([]);
}

// 工作单元：执行闭包 → 收集本地缓存中所有实体变更 → 生成 SQL → 多 SQL 自动事务包裹 → 乐观锁校验 → 提交，失败时触发 disturbed 回调
function unit_of_work(Closure $action)
{
    local_cache_delete_all();
    clean_unit_of_work_delegate();

    try {
        try {
            $res = $action();

            $entities = local_cache_get_all();
        } finally {
            local_cache_delete_all();
        }

        $sqls = [];

        $db_config_key = unit_of_work_db_config_key();

        foreach ($entities as $entity) {

            $dao = $entity->get_dao();

            if ($dao->get_db_config_key() !== $db_config_key) {
                continue;
            }

            if ($entity->just_force_deleted()) {
                if (!$entity->just_new()) {
                    $sqls[] = $dao->dump_delete_sql($entity);
                }
            } elseif ($entity->just_new()) {
                $sqls[] = $dao->dump_insert_sql($entity);
            } elseif ($entity->just_updated() || $entity->just_deleted()) {
                $sqls[] = $dao->dump_update_sql($entity);
            }
        }

        if ($sqls) {
            if (count($sqls) > 1) {
                db_transaction(function () use ($sqls, $db_config_key) {
                    foreach ($sqls as $sql) {
                        _unit_of_work_write($sql['sql_template'], $sql['binds'], $db_config_key);
                    }
                }, $db_config_key);
            } else {
                $sql = reset($sqls);
                _unit_of_work_write($sql['sql_template'], $sql['binds'], $db_config_key);
            }
        }

        $action = if_unit_of_work_executed();
        if ($action instanceof closure) {
            call_user_func($action);
            clean_unit_of_work_delegate();
        }

    } catch (exception $ex) {

        $action = if_unit_of_work_disturbed();
        if ($action instanceof closure) {
            call_user_func($action, $ex);
            clean_unit_of_work_delegate();
        }

        throw $ex;
    }

    return $res;
}

// 分布式 ID 生成器，基于 Redis INCR 批量取号减少网络开销，mark 用于区分不同实体或业务的 ID 序列
function generate_id($mark = 'idgenter')
{
    static $step = 1;

    static $generators = [];

    if (! array_key_exists($mark, $generators)) {

        $generator = $generators[$mark] = function () use ($step, $mark) {

            static $now_id;
            static $step_last_id;

            if ($now_id === $step_last_id) {
                $step_last_id = cache_increment($mark.IDGENTER_CACHE_KEY_SUFFIX, $step, 0, IDGENTER_CACHE_MIDWARE_KEY);
                $now_id = $step_last_id - $step;
            }

            return $now_id += 1;
        };
    } else {

        $generator = $generators[$mark];
    }

    return call_user_func($generator);
}
