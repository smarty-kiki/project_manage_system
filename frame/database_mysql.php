<?php

// 【私有函数，禁止在其他文件中调用】获取或清空 MySQL PDO 连接，传入空数组清空所有连接，基于 DSN+用户名+密码 做连接池复用，支持 TCP 端口和 Unix Socket
function _mysql_connection(array $config)
{/*{{{*/
    static $container = [];

    if (empty($config)) {

        return $container = [];
    } else {

        $host = $config['host'];
        $port_or_sock = $config['port'];
        $database = $config['database'];
        $username = $config['username'];
        $password = $config['password'];
        $charset = $config['charset'];
        $collation = $config['collation'];
        $options = $config['options'];

        if (is_numeric($port_or_sock)) {
            $dsn = "mysql:host={$host};port={$port_or_sock};dbname={$database}";
        } else {
            $dsn = "mysql:unix_socket={$port_or_sock};dbname={$database}";
        }

        $identifier = $dsn.'|'.$username.'|'.$password;

        if (!isset($container[$identifier])) {

            $connection = new PDO($dsn, $username, $password, $options);

            $connection->prepare("set names '{$charset}' collate '{$collation}'")->execute();

            $container[$identifier] = $connection;
        }

        return $container[$identifier];
    }
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】解析 MySQL 配置，按读写类型从连接池获取连接并传入闭包执行，type 为 read/write/schema，事务期间自动强制走写库
function _mysql_database_closure($config_key, $type, closure $closure)
{/*{{{*/
    $config = config_midware('mysql', $config_key);

    $type = db_force_type_write()? 'write': $type;

    $connection = _mysql_connection([
        'host' => $host = array_rand($config[$type]),
        'port' => $config[$type][$host],
        'database' => $config['database'],
        'username' => $config['username'],
        'password' => $config['password'],
        'charset' => $config['charset'],
        'collation' => $config['collation'],
        'options' => $config['options'],
    ]);

    return call_user_func($closure, $connection);
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】处理 SQL 绑定，binds 中数组值自动展开为 IN 子句占位符，防止手动拼接
function _mysql_sql_binds($sql_template, array $binds)
{/*{{{*/
    $res_binds = [];

    foreach ($binds as $key => $value) {
        if (is_array($value)) {
            $subbind_keys = [];
            foreach ($value as $i => $sub_value) {
                $subbind_keys[] = $subbind_key = $key.$i.'p';
                $res_binds[$subbind_key] = $sub_value;
            }

            $sql_template = str_replace($key, '('.implode(',', $subbind_keys).')', $sql_template);
        } else {
            $res_binds[$key] = $value;
        }
    }

    return [$sql_template, $res_binds];
}/*}}}*/

// 强制后续数据库操作走写库，传入 null 时返回当前状态，事务内部自动调用
function db_force_type_write(?bool $bool = null)
{/*{{{*/
    static $container = false;

    if (! is_null($bool)) {
        $container = $bool;
    }

    return $container;
}/*}}}*/

// 执行查询并返回所有行，binds 中数组值自动展开为 IN 子句，走读库
function db_query($sql_template, array $binds = [], $config_key = 'default')
{/*{{{*/
    list($sql_template, $binds) = _mysql_sql_binds($sql_template, $binds);

    return _mysql_database_closure($config_key, 'read', function ($connection) use ($sql_template, $binds) {

        $st = $connection->prepare($sql_template);

        $st->execute($binds);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    });
}/*}}}*/

// 查询单行数据，自动追加 limit 1，未找到返回 false，走读库
function db_query_first($sql_template, array $binds = [], $config_key = 'default')
{/*{{{*/
    $sql_template = str_finish($sql_template, ' limit 1');

    list($sql_template, $binds) = _mysql_sql_binds($sql_template, $binds);

    return _mysql_database_closure($config_key, 'read', function ($connection) use ($sql_template, $binds) {

        $st = $connection->prepare($sql_template);

        $st->execute($binds);

        return $st->fetch(PDO::FETCH_ASSOC);
    });
}/*}}}*/

// 查询指定列的所有值，返回一维数组
function db_query_column($column, $sql_template, array $binds = [], $config_key = 'default')
{/*{{{*/
    $rows = db_query($sql_template, $binds, $config_key);

    $res = [];

    foreach ($rows as $row) {
        $res[] = $row[$column];
    }

    return $res;
}/*}}}*/

// 查询单个标量值，基于 db_query_first 取指定列，未找到返回 null
function db_query_value($value, $sql_template, array $binds = [], $config_key = 'default')
{/*{{{*/
    $row = db_query_first($sql_template, $binds, $config_key);

    return $row[$value] ?? null;
}/*}}}*/

// 执行 UPDATE 语句，返回影响行数，走写库
function db_update($sql_template, array $binds = [], $config_key = 'default')
{/*{{{*/
    list($sql_template, $binds) = _mysql_sql_binds($sql_template, $binds);

    return _mysql_database_closure($config_key, 'write', function ($connection) use ($sql_template, $binds) {

        $st = $connection->prepare($sql_template);

        $st->execute($binds);

        return $st->rowCount();
    });
}/*}}}*/

// 执行 DELETE 语句，返回影响行数，走写库
function db_delete($sql_template, array $binds = [], $config_key = 'default')
{/*{{{*/
    list($sql_template, $binds) = _mysql_sql_binds($sql_template, $binds);

    return _mysql_database_closure($config_key, 'write', function ($connection) use ($sql_template, $binds) {

        $st = $connection->prepare($sql_template);

        $st->execute($binds);

        return $st->rowCount();
    });
}/*}}}*/

// 执行 INSERT 语句，返回 lastInsertId，走写库
function db_insert($sql_template, array $binds = [], $config_key = 'default')
{/*{{{*/
    list($sql_template, $binds) = _mysql_sql_binds($sql_template, $binds);

    return _mysql_database_closure($config_key, 'write', function ($connection) use ($sql_template, $binds) {

        $st = $connection->prepare($sql_template);

        $st->execute($binds);

        return $connection->lastInsertId();
    });
}/*}}}*/

// 执行任意写操作 SQL，返回影响行数，走写库
function db_write($sql_template, array $binds = [], $config_key = 'default')
{/*{{{*/
    list($sql_template, $binds) = _mysql_sql_binds($sql_template, $binds);

    return _mysql_database_closure($config_key, 'write', function ($connection) use ($sql_template, $binds) {

        $st = $connection->prepare($sql_template);

        $st->execute($binds);

        return $st->rowCount();
    });
}/*}}}*/

// 执行 DDL 语句（建表/改表等），使用 schema 连接，不走读写分离
function db_structure($sql, $config_key = 'default')
{/*{{{*/
    return _mysql_database_closure($config_key, 'schema', function ($connection) use ($sql) {

        $st = $connection->prepare($sql);

        $st->execute();

        return $st->rowCount();
    });
}/*}}}*/

// 事务封装，闭包内所有数据库操作自动走写库，异常时回滚，正常时提交，finally 中恢复读写分离
function db_transaction(closure $action, $config_key = 'default')
{/*{{{*/
    db_force_type_write(true);

    return _mysql_database_closure($config_key, 'write', function ($connection) use ($action) {

        $began = $connection->beginTransaction();

        if (!$began) {
            throw new Exception('can not start transaction');
        }

        try {
            $res = $action();

            $connection->commit();

            return $res;
        } catch (Exception $ex) {
            $connection->rollBack();

            throw $ex;
        } finally {
            db_force_type_write(false);
        }
    });
}/*}}}*/

// 关闭并清空所有 MySQL 连接
function db_close()
{/*{{{*/
    return _mysql_connection([]);
}/*}}}*/

// 从关联数组生成 WHERE 子句和 binds，value 为数组 → IN，null → IS NULL，字符串 → =，列名后空格加 not 可反转逻辑
function db_simple_where_sql(array $wheres)
{/*{{{*/
    if (empty($wheres)) {
        return ['1 = 1', []];
    }

    $where_sqls = $binds = [];

    $n = 0;

    foreach ($wheres as $column => $value) {
        if (is_array($value)) {
            $column_info = explode(' ', $column);
            $column = $column_info[0];
            $bind_key = ':w'.$n.$column;
            $symbol = (isset($column_info[1]) && $column_info[1] === 'not')? 'not in': 'in';

            $where_sqls[] = "`$column` $symbol $bind_key";
            $binds[$bind_key] = $value;
        } elseif (is_null($value)) {
            $column_info = explode(' ', $column);
            $column = $column_info[0];
            $symbol = (isset($column_info[1]) && $column_info[1] === 'not')? 'is not': 'is';

            $where_sqls[] = "`$column` $symbol null";
        } else {
            $column_info = explode(' ', $column);
            $column = $column_info[0];
            $bind_key = ':w'.$n.$column;
            $symbol = $column_info[1] ?? '=';

            $where_sqls[] = "`$column` $symbol $bind_key";
            $binds[$bind_key] = $value;
        }

        $n ++;
    }

    return [implode(' and ', $where_sqls), $binds];
}/*}}}*/

// 从关联数组生成 INSERT 语句并执行，返回 lastInsertId
function db_simple_insert($table, array $data, $config_key = 'default')
{/*{{{*/
    $columns = $values = $binds = [];

    foreach ($data as $column => $value) {
        $columns[] = $column;
        $values[] = ":$column";
        $binds[":$column"] = $value;
    }

    $sql_template = 'insert into `'.$table.'` (`'.implode('`, `', $columns).'`) values ('.implode(', ', $values).')';

    return db_insert($sql_template, $binds, $config_key);
}/*}}}*/

// 批量插入，datas 为二维数组，自动合并所有列名生成单条 INSERT 语句
function db_simple_multi_insert($table, array $datas, $config_key = 'default')
{/*{{{*/
    $data_sql_templates = $columns = $binds = [];

    foreach ($datas as $k => $data) {

        $values = [];

        foreach ($data as $column => $value) {
            $columns[$column] = true;
            $values[] = ":i_$column$k";
            $binds[":i_$column$k"] = $value;
        }

        $data_sql_templates[] = '('.implode(', ', $values).')';
    }

    $sql_template = 'insert into `'.$table.'` (`'.implode('`, `', array_keys($columns)).'`) values '.implode(', ', $data_sql_templates);

    return db_insert($sql_template, $binds, $config_key);
}/*}}}*/

// 从关联数组生成 UPDATE 语句并执行，wheres 规则同 db_simple_where_sql
function db_simple_update($table, array $wheres, array $data, $config_key = 'default')
{/*{{{*/
    list($where, $binds) = db_simple_where_sql($wheres);

    $update = [];

    foreach ($data as $column => $value) {
        $update[] = "`$column` = :u_$column";
        $binds[":u_$column"] = $value;
    }

    $sql_template = 'update `'.$table.'` set '.implode(', ', $update).' where '.$where;

    return db_update($sql_template, $binds, $config_key);
}/*}}}*/

// 批量更新，使用 CASE WHEN 在一条 SQL 中更新多行不同值，where_column 指定条件列（默认 id）
function db_simple_multi_update($table, array $datas, $where_column = 'id', $config_key = 'default')
{/*{{{*/
    $set_sqls = $binds = $where_values = [];

    $keys = array_keys(current($datas));

    foreach ($keys as $column) {

        if ($column == $where_column) {
            continue;
        }

        $set_sql = sprintf("`$column` = case `$where_column`\n");

        foreach ($datas as $i => $data) {

            $when_bind_key = ':'.$where_column.'_'.$i.'_'.$column;
            $then_bind_key = ':'.$column.'_'.$i;

            $set_sql .= "    when $when_bind_key then $then_bind_key\n";

            $binds[$when_bind_key] = $data[$where_column];
            $binds[$then_bind_key] = $data[$column];
            $where_values[] = $data[$where_column];
        }
        $set_sqls[] = $set_sql."end";
    }

    $set_sql_str = implode(",\n", $set_sqls);

    $binds[':where_values'] = $where_values;

    return db_update(
        "update `$table` set\n$set_sql_str where `$where_column` in :where_values",
        $binds,
        $config_key);
}/*}}}*/

// 从关联数组生成 DELETE 语句并执行，wheres 规则同 db_simple_where_sql
function db_simple_delete($table, array $wheres, $config_key = 'default')
{/*{{{*/
    list($where, $binds) = db_simple_where_sql($wheres);

    $sql_template = 'delete from `'.$table.'` where '.$where;

    return db_delete($sql_template, $binds, $config_key);
}/*}}}*/

// 简化的全表查询，自动拼接 SELECT *，option_sql 可追加 order by/limit 等，wheres 规则同 db_simple_where_sql
function db_simple_query($table, array $wheres = [], $option_sql = 'order by id', $config_key = 'default')
{/*{{{*/
    list($where, $binds) = db_simple_where_sql($wheres);

    return db_query('select * from `'.$table.'` where '.$where.' '.$option_sql, $binds, $config_key);
}/*}}}*/

// 简化的单行查询，基于 db_simple_query 取第一条
function db_simple_query_first($table, array $wheres, $option_sql = '', $config_key = 'default')
{/*{{{*/
    list($where, $binds) = db_simple_where_sql($wheres);

    return db_query_first('select * from `'.$table.'` where '.$where.' '.$option_sql, $binds, $config_key);
}/*}}}*/

// 简化的单列查询，基于 db_simple_query 提取指定列的所有值
function db_simple_query_column($table, $column, array $wheres = [], $option_sql = '', $config_key = 'default')
{/*{{{*/
    $datas = db_simple_query($table, $wheres, $option_sql, $config_key);

    $res = [];

    foreach ($datas as $data) {
        $res[] = $data[$column];
    }

    return $res;
}/*}}}*/

// 简化的索引查询，以 indexed 指定列的值作为返回数组的 key
function db_simple_query_indexed($table, $indexed, array $wheres = [], $option_sql = 'order by id', $config_key = 'default')
{/*{{{*/
    $datas = db_simple_query($table, $wheres, $option_sql, $config_key);

    $res = [];

    foreach ($datas as $data) {
        $res[$data[$indexed]] = $data;
    }

    return $res;
}/*}}}*/

// 简化的单值查询，直接查询指定列并返回标量值，wheres 规则同 db_simple_where_sql
function db_simple_query_value($table, $value, array $wheres, $option_sql = '', $config_key = 'default')
{/*{{{*/
    list($where, $binds) = db_simple_where_sql($wheres);

    return db_query_value($value, "select `$value` from `$table` where $where $option_sql", $binds, $config_key);
}/*}}}*/
