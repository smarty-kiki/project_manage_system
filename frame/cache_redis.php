<?php

// 【私有方法，禁止在其他文件中调用】获取或关闭 Redis 连接，传入空数组时关闭所有连接，传入配置时基于 host/port/timeout 或 sock 做连接池复用
function _redis_connection(array $config)
{/*{{{*/
    static $container = [];

    if (empty($config)) {

        foreach ($container as $connection) {
            $connection->close();
        }

        return $container = [];
    } else {

        $is_sock = isset($config['sock']);

        $sign = $is_sock ?
            $config['sock'] . $config['timeout']:
            $config['host'] . $config['port'] . $config['timeout'];

        if (empty($container[$sign])) {

            $redis = new Redis();

            if ($is_sock) {
                $redis->connect($config['sock'], $config['timeout']);
            } else {
                $redis->connect($config['host'], $config['port'], $config['timeout']);
            }

            if (isset($config['auth'])) {
                $redis->auth($config['auth']);
            }

            $container[$sign] = $redis;
        } else {
            $redis = $container[$sign];
        }

        if (isset($config['database'])) {
            $redis->select($config['database']);
        }

        if (isset($config['options'])) {
            foreach ($config['options'] as $key => $value) {
                $redis->setOption($key, $value);
            }
        }

        return $redis;
    }
}/*}}}*/

// 【私有方法，禁止在其他文件中调用】解析 config_key 对应的 Redis 配置，获取连接并传入闭包执行
function _redis_cache_closure($config_key, closure $closure)
{/*{{{*/
    $config = config_midware('redis', $config_key);

    $redis = _redis_connection($config);

    return call_user_func($closure, $redis);
}/*}}}*/

// 获取缓存值
function cache_get($key, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key) {

        return $redis->get($key);

    });
}/*}}}*/

// 批量获取缓存值，返回 key => value 关联数组，不存在的 key 对应值为 false
function cache_multi_get(array $keys, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($keys) {

        $values = $redis->mGet($keys);

        return array_combine($keys, $values);
    });
}/*}}}*/

// 设置缓存，expires 为过期秒数，0 表示永不过期
function cache_set($key, $value, $expires = 0, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key, $value, $expires) {

        if ($expires) {
            return $redis->set($key, $value, $expires);
        } else {
            return $redis->set($key, $value);
        }
    });
}/*}}}*/

// 仅当 key 不存在时设置缓存（NX），成功返回 true，失败返回 false
function cache_add($key, $value, $expires = 0, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key, $value, $expires) {

        if ($expires) {
            return $redis->set($key, $value, ['nx', 'ex' => $expires]);
        } else {
            return $redis->setNx($key, $value);
        }
    });
}/*}}}*/

// 仅当 key 存在时替换缓存（XX），成功返回 true，失败返回 false
function cache_replace($key, $value, $expires = 0, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key, $value, $expires) {

        if ($expires) {
            return $redis->set($key, $value, ['xx', 'ex' => $expires]);
        } else {
            return $redis->setNx($key, $value);
        }
    });
}/*}}}*/

// 删除缓存 key，返回删除的 key 数量
function cache_delete($key, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key) {

        return $redis->del($key);
    });
}/*}}}*/

// 批量删除缓存 key，返回删除的 key 数量
function cache_multi_delete(array $keys, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($keys) {

        return $redis->del($keys);
    });
}/*}}}*/

// 对整数缓存值自增，可指定步长和过期时间
function cache_increment($key, $number = 1, $expires = 0, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key, $number, $expires) {

        $res = $redis->incr($key, $number);

        if ($expires) {
            $redis->expire($key, $expires);
        }

        return $res;
    });
}/*}}}*/

// 对整数缓存值自减，可指定步长和过期时间
function cache_decrement($key, $number = 1, $expires = 0, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key, $number, $expires) {

        $res = $redis->decr($key, $number);

        if ($expires) {
            $redis->expire($key, $expires);
        }

        return $res;
    });
}/*}}}*/

// 按模式查找 key，默认 * 匹配所有 key，生产环境慎用
function cache_keys($pattern = '*', $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($pattern) {

        return $redis->keys($pattern);
    });
}/*}}}*/

// Hash 批量设置字段，array 为 field => value 关联数组
function cache_hmset($key, array $array, $expires = 0, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key, $array, $expires) {

        $res = $redis->hmset($key, $array);

        if ($expires) {
            $redis->expire($key, $expires);
        }

        return $res;
    });
}/*}}}*/

// Hash 批量获取指定字段的值
function cache_hmget($key, array $fields, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key, $fields) {

        return $redis->hmget($key, $fields);

    });
}/*}}}*/

// List 左推入，values 可为单个值或数组，支持设置过期时间
function cache_lpush($key, $values, $expires = 0, $config_key = 'default')
{/*{{{*/
    $values = (array) $values;

    return _redis_cache_closure($config_key, function ($redis) use ($key, $values, $expires) {

        $res = $redis->lpush($key, ...$values);

        if ($expires) {
            $redis->expire($key, $expires);
        }

        return $res;
    });
}/*}}}*/

// List 阻塞弹出，keys 可为单个 key 或 key 数组，wait_time 为阻塞超时秒数（0 表示永久阻塞）
function cache_blpop($keys, $wait_time = 0, $config_key = 'default')
{/*{{{*/
    $is_array = is_array($keys);

    $params = (array) $keys;

    $params[] = $wait_time;

    $res = _redis_cache_closure($config_key, function ($redis) use ($params) {

        return $redis->blpop(...$params);
    });

    if ($res) {
        return $is_array? [$res[0] => $res[1]] : $res[1];
    } else {
        return $is_array? []: null;
    }
}/*}}}*/

// Bitmap 设置指定偏移位的值，value 为 0 或 1
function cache_setbit($key, $offset, $value, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key, $offset, $value) {

        return $redis->setbit($key, $offset, $value);
    });
}/*}}}*/

// Bitmap 获取指定偏移位的值
function cache_getbit($key, $offset, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key, $offset) {

        return $redis->getbit($key, $offset);
    });
}/*}}}*/

// Bitmap 统计指定范围内值为 1 的位数，start/end 为字节偏移（非位偏移），-1 表示末尾
function cache_bitcount($key, $start = 0, $end = -1, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key, $start, $end) {

        return $redis->bitcount($key, $start, $end);
    });
}/*}}}*/

// Bitmap 位运算，operation 为 AND/OR/XOR/NOT，keys 为参与运算的 key 数组，结果存入 destkey
function cache_bitop($destkey, $operation, $keys, $config_key = 'default')
{/*{{{*/
    $keys = (array) $keys;

    return _redis_cache_closure($config_key, function ($redis) use ($destkey, $operation, $keys) {

        return $redis->bitop($operation, $destkey, ...$keys);
    });
}/*}}}*/

// Bitmap 查找指定 bit 值（0 或 1）首次出现的位偏移，start/end 为字节偏移
function cache_bitpos($key, $bit, $start = 0, $end = -1, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($key, $bit, $start, $end) {

        return $redis->bitpos($key, $bit, $start, $end);
    });
}/*}}}*/

// 重命名 key，若 new_key 已存在则先删除再改名
function cache_rename($old_key, $new_key, $config_key = 'default')
{/*{{{*/
    return _redis_cache_closure($config_key, function ($redis) use ($old_key, $new_key) {

        return $redis->rename($old_key, $new_key);
    });
}/*}}}*/

// 关闭并清空所有 Redis 连接
function cache_close()
{/*{{{*/
    return _redis_connection([]);
}/*}}}*/
