<?php

// 【私有函数，禁止在其他文件中调用】抛 Beanstalkd 协议错误异常
function _beanstalk_error($error)
{/*{{{*/
    throw new Exception($error);
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】Beanstalkd 连接池，传入空数组清空所有连接，基于 host+port 复用 socket
function _beanstalk_container(array $config)
{/*{{{*/
    static $container = [];

    if (empty($config)) {

        foreach ($container as $fp) {
            _beanstalk_disconnect($fp);
        }

        return $container = [];
    } else {

        $host = $config['host'];
        $port = $config['port'];
        $timeout = $config['timeout'];

        $identifier = $host . $port;

        if (!isset($container[$identifier])) {

            $fp = fsockopen($host, $port, $error_number, $error_str, $timeout);

            if ($fp === false) {
                _beanstalk_error('ERROR: '.$error_number.' - '.$error_str);
            }

            stream_set_timeout($fp, -1);

            $container[$identifier] = $fp;
        }

        return $container[$identifier];
    }
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】解析 config_key 对应的 Beanstalkd 配置并获取连接
function _beanstalk_connection($config_key)
{/*{{{*/
    $config = config_midware('beanstalk', $config_key);

    return _beanstalk_container([
        'host' => $config['host'],
        'port' => $config['port'],
        'timeout' => $config['timeout'],
    ]);
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】发送 quit 命令并关闭 socket 连接
function _beanstalk_disconnect($fp)
{/*{{{*/
    _beanstalk_connection_write($fp, 'quit');

    return fclose($fp);
}/*}}}*/

// 关闭并清空所有 Beanstalkd 连接
function beanstalk_close()
{/*{{{*/
    _beanstalk_container([]);
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】从 socket 读取响应，$data_length 指定时读取定长数据块，否则按行读取
function _beanstalk_connection_read($fp, ?int $data_length = null)
{/*{{{*/
    if ($data_length) {

        $data = stream_get_contents($fp, $data_length + 2);
        $meta = stream_get_meta_data($fp);

        if ($meta['timed_out']) {
            throw new RuntimeException('Connection timed out while reading data from socket.');
        }

        return rtrim($data, "\r\n");

    } else {

        return stream_get_line($fp, 16384, "\r\n");
    }
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】向 socket 写入数据，自动追加 CRLF
function _beanstalk_connection_write($fp, $data)
{/*{{{*/
    $data .= "\r\n";
    return fwrite($fp, $data, strlen($data));
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】Beansalkd put 命令：投递任务，返回 job id
function _beanstalk_put($fp, $priority, $delay, $run_time, $data)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf("put %d %d %d %d\r\n%s", $priority, $delay, $run_time, strlen($data), $data));
    $status = strtok(_beanstalk_connection_read($fp), ' ');

    switch ($status) {
        case 'INSERTED':
        case 'BURIED':
            return (integer) strtok(' '); // job id
        case 'EXPECTED_CRLF':
        case 'JOB_TOO_BIG':
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd use 命令：切换生产者当前 tube
function _beanstalk_use_tube($fp, $tube)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('use %s', $tube));
    $status = strtok(_beanstalk_connection_read($fp), ' ');

    switch ($status) {
        case 'USING':
            return strtok(' ');
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd pause-tube 命令：暂停管道的任务派发
function _beanstalk_pause_tube($fp, $tube, $delay)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('pause-tube %s %d', $tube, $delay));
    $status = strtok(_beanstalk_connection_read($fp), ' ');

    switch ($status) {
        case 'PAUSED':
            return true;
        case 'NOT_FOUND':
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd reserve 命令：阻塞获取任务，timeout 为超时秒数
function _beanstalk_reserve($fp, ?int $timeout = null)
{/*{{{*/
    if (is_null($timeout)) {
        _beanstalk_connection_write($fp, 'reserve');
    } else {
        _beanstalk_connection_write($fp, sprintf('reserve-with-timeout %d', $timeout));
    }
    $status = strtok(_beanstalk_connection_read($fp), ' ');

    switch ($status) {
        case 'RESERVED':
            return [
                'id' => (integer) strtok(' '),
                'body' => _beanstalk_connection_read($fp, (integer) strtok(' ')),
            ];
        case 'TIMED_OUT':
            return false;
        case 'DEADLINE_SOON':
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd delete 命令：删除已完成任务
function _beanstalk_delete($fp, $id)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('delete %d', $id));
    $status = _beanstalk_connection_read($fp);

    switch ($status) {
        case 'DELETED':
            return true;
        case 'NOT_FOUND':
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd release 命令：将任务重新放回就绪队列，delay 为延迟秒数
function _beanstalk_release($fp, $id, $priority, $delay)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('release %d %d %d', $id, $priority, $delay));
    $status = _beanstalk_connection_read($fp);

    switch ($status) {
        case 'RELEASED':
        case 'BURIED':
            return true;
        case 'NOT_FOUND':
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd bury 命令：将任务埋入 buried 状态
function _beanstalk_bury($fp, $id, $priority = 10)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('bury %d %d', $id, $priority));
    $status = _beanstalk_connection_read($fp);

    switch ($status) {
        case 'BURIED':
            return true;
        case 'NOT_FOUND':
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd touch 命令：延长任务的 TTR（Time To Run）
function _beanstalk_touch($fp, $id)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('touch %d', $id));
    $status = _beanstalk_connection_read($fp);

    switch ($status) {
        case 'TOUCHED':
            return true;
        case 'NOT_TOUCHED':
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd watch 命令：消费者关注指定 tube
function _beanstalk_watch($fp, $tube)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('watch %s', $tube));
    $status = strtok(_beanstalk_connection_read($fp), ' ');

    switch ($status) {
        case 'WATCHING':
            return (integer) strtok(' ');
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd ignore 命令：消费者取消关注指定 tube
function _beanstalk_ignore($fp, $tube)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('ignore %s', $tube));
    $status = strtok(_beanstalk_connection_read($fp), ' ');

    switch ($status) {
        case 'WATCHING':
            return (integer) strtok(' ');
        case 'NOT_IGNORED':
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】解析 Beanstalkd peek 系列命令的响应，返回含 id 和 body 的数组
function _beanstalk_peek_read($fp)
{/*{{{*/
    $status = strtok(_beanstalk_connection_read($fp), ' ');

    switch ($status) {
        case 'FOUND':
            return [
                'id' => (integer) strtok(' '),
                'body' => _beanstalk_connection_read($fp, (integer) strtok(' ')),
            ];
        case 'NOT_FOUND':
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd peek 命令：查看指定 job 详情
function _beanstalk_peek($fp, $id)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('peek %d', $id));
    return _beanstalk_peek_read($fp);
}/*}}}*/

// 【私有函数】Beanstalkd peek-ready 命令：查看下一个就绪任务
function _beanstalk_peek_ready($fp)
{/*{{{*/
    _beanstalk_connection_write($fp, 'peek-ready');
    return _beanstalk_peek_read($fp);
}/*}}}*/

// 【私有函数】Beanstalkd peek-delayed 命令：查看下一个延迟任务
function _beanstalk_peek_delayed($fp)
{/*{{{*/
    _beanstalk_connection_write($fp, 'peek-delayed');
    return _beanstalk_peek_read($fp);
}/*}}}*/

// 【私有函数】Beanstalkd peek-buried 命令：查看下一个被埋任务
function _beanstalk_peek_buried($fp)
{/*{{{*/
    _beanstalk_connection_write($fp, 'peek-buried');
    return _beanstalk_peek_read($fp);
}/*}}}*/

// 【私有函数】Beanstalkd kick 命令：将 bound 个 buried 任务批量移回就绪队列，返回实际移动数量
function _beanstalk_kick($fp, $bound)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('kick %d', $bound));
    $status = strtok(_beanstalk_connection_read($fp), ' ');

    switch ($status) {
        case 'KICKED':
            return (integer) strtok(' ');
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd kick-job 命令：将指定 buried 任务移回就绪队列
function _beanstalk_kick_job($fp, $id)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('kick-job %d', $id));
    $status = strtok(_beanstalk_connection_read($fp), ' ');

    switch ($status) {
        case 'KICKED':
            return true;
        case 'NOT_FOUND':
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】解析 Beanstalkd stats 系列命令的响应，按 data_length 读取 YAML 正文
function _beanstalk_stats_read($fp)
{/*{{{*/
    $status = strtok(_beanstalk_connection_read($fp), ' ');

    switch ($status) {
        case 'OK':
            return _beanstalk_connection_read($fp, (integer) strtok(' '));
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd stats 命令：获取服务全局统计信息
function _beanstalk_stats($fp)
{/*{{{*/
    _beanstalk_connection_write($fp, 'stats');
    return _beanstalk_stats_read($fp);
}/*}}}*/

// 【私有函数】Beanstalkd stats-job 命令：获取指定任务详情，$array_result 为 true 时返回解析后的关联数组而非 YAML 字符串
function _beanstalk_stats_job($fp, $id, $array_result = false)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('stats-job %d', $id));

    $res = _beanstalk_stats_read($fp);

    if ($array_result) {

        $tmp = strtok($res, "\n");

        $res = [];

        while ($tmp !== false) {
            $key = $tmp = strtok(':');
            $tmp = $value = strtok("\n");
            if ($value !== false) {
                $res[$key] = trim($value);
            }
        }
    }

    return $res;
}/*}}}*/

// 【私有函数】Beanstalkd stats-tube 命令：获取指定 tube 的统计信息
function _beanstalk_stats_tube($fp, $tube)
{/*{{{*/
    _beanstalk_connection_write($fp, sprintf('stats-tube %s', $tube));
    return _beanstalk_stats_read($fp);
}/*}}}*/

// 【私有函数】Beanstalkd list-tubes 命令：列出所有 tube
function _beanstalk_list_tube($fp)
{/*{{{*/
    _beanstalk_connection_write($fp, 'list-tubes');
    return _beanstalk_stats_read($fp);
}/*}}}*/

// 【私有函数】Beanstalkd list-tube-used 命令：查看当前生产者使用的 tube
function _beanstalk_list_tube_used($fp)
{/*{{{*/
    _beanstalk_connection_write($fp, 'list-tube-used');
    $status = strtok(_beanstalk_connection_read($fp), ' ');

    switch ($status) {
        case 'USING':
            return strtok(' ');
        default:
            _beanstalk_error($status);
            return false;
    }
}/*}}}*/

// 【私有函数】Beanstalkd list-tubes-watched 命令：查看当前消费者监听的 tube 列表
function _beanstalk_list_tube_watched($fp)
{/*{{{*/
    _beanstalk_connection_write($fp, 'list-tubes-watched');
    return _beanstalk_stats_read($fp);
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】获取或设置当前 worker 最后 reserve 的 job id，传入 null 时获取，传入 id 时设置
function _queue_last_reserved_job_id(?int $id = null)
{/*{{{*/
    static $container = null;

    if (! is_null($id)) {
        return $container = $id;
    }

    return $container;
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】获取或设置当前 worker 最后监听的配置 key，供 queue_job_touch 等函数使用
function _queue_last_watched_config_key(?string $config_key = null)
{/*{{{*/
    static $container = null;

    if (! is_null($config_key)) {
        return $container = $config_key;
    }

    return $container;
}/*}}}*/

// 注册或获取每次 worker 循环结束时的回调，传入闭包时注册，传入 null 时获取，在 reserve 开始前触发
function queue_finish_action(?closure $action = null)
{/*{{{*/
    static $container = null;

    if (!empty($action)) {
        return $container = $action;
    }

    return $container;
}/*}}}*/

// 触发队列循环结束回调，由 queue_watch 在每次 reserve 前调用
function queue_finish_action_trigger()
{/*{{{*/
    $finished_action = queue_finish_action();

    if ($finished_action instanceof closure) {
        call_user_func($finished_action);
    }
}/*}}}*/

// 根据 job 名称获取已注册的 job 配置（priority、retry、tube、config_key、closure）
function queue_job_pickup($job_name)
{/*{{{*/
    $jobs = queue_jobs();

    return $jobs[$job_name];
}/*}}}*/

// 获取或设置全部已注册的 job 列表，传入数组时注册所有 job，传入 null 时返回当前列表
function queue_jobs(?array $jobs = null)
{/*{{{*/
    static $container = [];

    if (is_null($jobs)) {
        return $container;
    }

    return $container = $jobs;
}/*}}}*/

// 注册队列任务，$retry 为延时重试秒数数组（如 [10, 30, 60]），按 releases 次数匹配对应延迟，超出则 bury
function queue_job($job_name, closure $closure, $priority = 10, $retry = [], $tube = 'default', $config_key = 'default')
{/*{{{*/
    $jobs = queue_jobs();

    $jobs[$job_name] = [
        'closure' => $closure,
        'priority' => $priority,
        'retry' => $retry,
        'tube' => $tube,
        'config_key' => $config_key,
    ];

    queue_jobs($jobs);
}/*}}}*/

// 投递队列任务，序列化 job_name + data 后 put 到对应 tube，返回 job id
function queue_push($job_name, array $data = [], $delay = 0)
{/*{{{*/
    $job = queue_job_pickup($job_name);

    $fp = _beanstalk_connection($job['config_key']);

    _beanstalk_use_tube($fp, $job['tube']);

    $id = _beanstalk_put(
        $fp,
        $job['priority'],
        $delay,
        $run_time = 60,
        serialize([
            'job_name' => $job_name,
            'data' => $data,
        ])
    );

    return $id;
}/*}}}*/

// 暂停指定 tube 的任务派发，$delay 为暂停时长（秒），默认 1 小时
function queue_pause($tube = 'default', $config_key = 'default', $delay = 3600)
{/*{{{*/
    $fp = _beanstalk_connection($config_key);

    _beanstalk_pause_tube($fp, $tube, $delay);
}/*}}}*/

// 启动队列 worker，无限循环 reserve 并执行 job closure，支持 SIGTERM 优雅退出，$memory_limit 为内存上限（字节，默认 1MB）触发异常保护
function queue_watch($tube = 'default', $config_key = 'default', $memory_limit = 1048576)
{/*{{{*/
    $out_of_run_time_deleted_job_ids = [];

    declare(ticks=1);
    $received_signal = false;
    pcntl_signal(SIGTERM, function () use (&$received_signal) {
        $received_signal = true;
    });

    _queue_last_watched_config_key($config_key);

    for (;;) {

        if (memory_get_usage(true) > $memory_limit) {
            throw new Exception('queue_watch out of memory');
        }

        if ($received_signal) {
            break;
        }

        queue_finish_action_trigger();

        $fp = _beanstalk_connection($config_key);

        _beanstalk_watch($fp, $tube);

        if ($tube !== 'default') {

            _beanstalk_ignore($fp, 'default');
        }

        $job_instance = _beanstalk_reserve($fp, $wait_second = 5);
        if ($job_instance === false) {
            continue;
        }

        $id = $job_instance['id'];
        $body = unserialize($job_instance['body']);
        $job_name = $body['job_name'];
        $data = $body['data'];

        $job = queue_job_pickup($job_name);
        _queue_last_reserved_job_id($id);

        try {

            $res = isset($out_of_run_time_deleted_job_ids[$id]);

            if (! $res) {
                $res = call_user_func_array($job['closure'], [$data, $id]);
            }

        } catch (exception $exception) {

            log_exception($exception);
        }

        if ($res) {
            try {
                _beanstalk_delete($fp, $id);
            } catch (exception $exception) {
                if ($exception->getMessage() === 'NOT_FOUND') {
                    $out_of_run_time_deleted_job_ids[$id] = true;
                }
                log_exception($exception);
            }
        } else {

            $stats_info = _beanstalk_stats_job($fp, $id, true);

            $retry = $stats_info['releases'];

            if (isset($job['retry'][$retry])) {

                $retry_delay = $job['retry'][$retry];

                _beanstalk_release($fp, $id, $job['priority'], $retry_delay);

            } else {
                _beanstalk_bury($fp, $id);
            }
        }
    }
}/*}}}*/

// 查看指定 tube 的运行状态，返回 tube 级别的统计信息
function queue_status($tube = 'default', $config_key = 'default')
{/*{{{*/
    $fp = _beanstalk_connection($config_key);

    return _beanstalk_stats_tube($fp, $tube);
}/*}}}*/

// 延长当前 reserve 任务的 TTR（Time To Run），需在 job closure 中调用，依赖 worker 上下文中的 config_key 和 job_id
function queue_job_touch()
{/*{{{*/
    $config_key = _queue_last_watched_config_key();

    if ($config_key) {

        $fp = _beanstalk_connection($config_key);

        $job_id = _queue_last_reserved_job_id();

        if ($job_id) {

            return _beanstalk_touch($fp, $job_id);
        }
    }
}/*}}}*/
