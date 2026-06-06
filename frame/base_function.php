<?php

// 从数组中获取值，key 支持点号分隔多层访问（如 'data.name'），支持 * 通配符遍历子数组，default 可为闭包延迟求值
function array_get($array, $key, $default = null)
{/*{{{*/
    $delimiter = '.';

    if (is_null($key)) {

        return $default;
    }

    $exploded_keys = explode($delimiter, $key);

    foreach ($exploded_keys as $i => $segment) {

        if ('*' === $segment) {

            $res_array = [];

            $new_key = implode($delimiter, array_slice($exploded_keys, $i + 1));

            foreach ($array as $k => $v) {

                $res_array[$k] = array_get($v, $new_key, $default);
            }

            return $res_array;

        } else {

            if (!is_array($array) || !array_key_exists($segment, $array)) {

                return value($default);
            }

            $array = $array[$segment];
        }
    }

    return $array;
}/*}}}*/

// 在数组中设置值，key 支持点号分隔（如 'data.name'），中间层不存在时自动创建为空数组
function array_set($array, $key, $value)
{/*{{{*/
    $res_array = &$array;

    if (is_null($key)) {
        return $array = $value;
    }

    $keys = explode('.', $key);

    while (count($keys) > 1) {
        $key = array_shift($keys);

        if (!isset($array[$key]) || !is_array($array[$key])) {
            $array[$key] = [];
        }

        $array = &$array[$key];
    }

    $array[array_shift($keys)] = $value;

    return $res_array;
}/*}}}*/

// 检查数组中是否存在某个 key，key 支持点号分隔多层检查（如 'data.name'）
function array_exists($array, $key)
{/*{{{*/
    if (is_null($key)) {
        return false;
    }

    foreach (explode('.', $key) as $segment) {
        if (!is_array($array) || !array_key_exists($segment, $array)) {
            return false;
        }

        $array = $array[$segment];
    }

    return true;
}/*}}}*/

// 从数组中删除元素，keys 支持点号分隔，可传单个字符串或字符串数组批量删除
function array_forget(&$array, $keys)
{/*{{{*/
    $original = &$array;

    foreach ((array) $keys as $key) {
        $parts = explode('.', $key);

        while (count($parts) > 1) {
            $part = array_shift($parts);

            if (isset($array[$part]) && is_array($array[$part])) {
                $array = &$array[$part];
            }
        }

        unset($array[array_shift($parts)]);

        $array = &$original;
    }
}/*}}}*/

// 将数组分割为 [键数组, 值数组]，通常配合 list($keys, $values) 使用
function array_divide($array)
{/*{{{*/
    return array(array_keys($array), array_values($array));
}/*}}}*/

// 遍历数组用回调构建新数组，回调接收 $key 和 $value 返回 [新key, 新value]，新 key 为 null 时追加为索引数组
function array_build($array, $callback)
{/*{{{*/
    $results = [];

    foreach ($array as $key => $value) {

        list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

        if (is_null($innerKey)) {

            $results[] = $innerValue;
        } else {
            $results[$innerKey] = $innerValue;
        }
    }

    return $results;
}/*}}}*/

// 对数组进行两层分组，回调接收 $key 和 $value 返回 [索引, 新key, 新value]，构建 $result[索引][新key] = 新value 结构
function array_indexed(array $array, closure $callback)
{/*{{{*/
    $results = [];

    foreach ($array as $key => $value) {

        list($index, $innerKey, $innerValue) = call_user_func($callback, $key, $value);

        if (! isset($results[$index])) {
            $results[$index] = [];
        }

        if (is_null($innerKey)) {

            $results[$index][] = $innerValue;
        } else {
            if (! isset($results[$index][$innerKey])) {

                $results[$index][$innerKey] = [];
            }

            $results[$index][$innerKey] = $innerValue;
        }
    }

    return $results;
}/*}}}*/

// array_get 的批量版，按 keys 列表提取值，keys 中每项支持点号分隔，通常配合 list($a, $b) 解构
function array_list(array $array, array $keys)
{/*{{{*/
    if (empty($keys)) {
        return [];
    }

    $values = [];

    foreach ($keys as $key) {
        $values[] = array_get($array, $key);
    }

    return $values;
}/*}}}*/

// 按映射规则转换数组结构，rules 为 [来源key => 目标key] 键值对，来源和目标 key 均支持点号分隔
function array_transfer(array $array, array $rules)
{/*{{{*/
    if (empty($rules)) {
        return [];
    }

    $values = [];

    foreach ($rules as $from => $to) {
        $values = array_set($values, $to, array_get($array, $from));
    }

    return $values;
}/*}}}*/

// 字符串尾部省略，超出 len 时截断并以 suffix 结尾，len 为最终字符串总长
function str_tail_cut($string, $len, $suffix = '...')
{/*{{{*/
    $strlen = mb_strlen($string);
    $suffixlen = mb_strlen($suffix);

    if ($strlen > $len) {
        return mb_substr($string, 0, $len - $suffixlen) . $suffix;
    }

    return $string;
}/*}}}*/

// 字符串头部省略，超出 len 时截断并以 prefix 开头，len 为最终字符串总长
function str_head_cut($string, $len, $prefix = '...')
{/*{{{*/
    $strlen = mb_strlen($string);
    $prefixlen = mb_strlen($prefix);

    if ($strlen > $len) {
        return $prefix . mb_substr($string, $prefixlen - $len, $len - $prefixlen);
    }

    return $string;
}/*}}}*/

// 字符串中部省略，超出 len 时在中间插入 middle，前后各保留约一半长度
function str_middle_cut($string, $len, $middle = '...')
{/*{{{*/
    $strlen = mb_strlen($string);
    $middlelen = mb_strlen($middle);

    $res_strlen = $len - $middlelen;

    if ($res_strlen % 2) {
        $first_len  = floor($res_strlen / 2);
        $second_len = ceil($res_strlen / 2);
    } else {
        $first_len = $second_len = $res_strlen / 2;
    }

    if ($strlen > $len) {
        return mb_substr($string, 0, $first_len) . $middle . mb_substr($string, 0 - $second_len, $second_len);
    }

    return $string;
}/*}}}*/

// var_dump 所有传入参数后终止脚本执行
function dd(...$args)
{/*{{{*/
    var_dump(...$args);
    die;
}/*}}}*/

// 抛出异常并立即捕获，将调用栈输出到异常日志，message 用于检索区分
function trace($message = 'exception for trace')
{/*{{{*/
    try {
        throw new Exception($message);
    } catch (Exception $ex) {
        log_exception($ex);
    }
}/*}}}*/

// 获取变量的值，若为闭包则执行并返回其结果，否则直接返回该值
function value($value)
{/*{{{*/
    return $value instanceof Closure ? $value() : $value;
}/*}}}*/

// 获取闭包的唯一标识，基于反射生成 MD5，含文件路径和行号信息，文件变更会影响标识
function closure_id($closure)
{/*{{{*/
    $closure_ref = new ReflectionFunction($closure);

    return md5((string) $closure_ref);
}/*}}}*/

// 检查字符串是否以指定前缀开头，needles 可传字符串或字符串数组，匹配任一即返回 true
function starts_with($haystack, $needles)
{/*{{{*/
    foreach ((array) $needles as $needle) {
        if ($needle != '' && mb_strpos($haystack, $needle) === 0) {
            return true;
        }
    }

    return false;
}/*}}}*/

// 检查字符串是否以指定后缀结尾，needles 可传字符串或字符串数组，匹配任一即返回 true
function ends_with($haystack, $needles)
{/*{{{*/
    foreach ((array) $needles as $needle)
    {
        if ((string) $needle === mb_substr($haystack, - mb_strlen($needle))) return true;
    }

    return false;
}/*}}}*/

// 确保字符串以 cap 结尾，先去除末尾重复的 cap 再追加，已是该结尾则不变
function str_finish($value, $cap)
{/*{{{*/
    $quoted = preg_quote($cap, '/');

    return preg_replace('/(?:'.$quoted.')+$/', '', $value).$cap;
}/*}}}*/

// 确保字符串以 cap 开头，先去除开头重复的 cap 再前置，已是该开头则不变
function str_begin($value, $cap)
{/*{{{*/
    $quoted = preg_quote($cap, '/');

    return $cap.preg_replace('/^(?:'.$quoted.')+/', '', $value);
}/*}}}*/

// 判断字符串是否为 URL，同时匹配 #、//、mailto:、tel: 等特殊协议前缀
function is_url($path)
{/*{{{*/
    if (! is_string($path)) {
        return false;
    }

    if (starts_with($path, ['#', '//', 'mailto:', 'tel:'])) {
        return true;
    }

    return filter_var($path, FILTER_VALIDATE_URL) !== false;
}/*}}}*/

// parse_url 的逆操作，将解析后的各组成部分拼合回完整 URL 字符串
function unparse_url(array $parsed)
{/*{{{*/
    $get = function ($key) use ($parsed) {
        return isset($parsed[$key]) ? $parsed[$key] : null;
    };

    $pass      = $get('pass');
    $user      = $get('user');
    $userinfo  = $pass !== null ? "$user:$pass" : $user;
    $port      = $get('port');
    $scheme    = $get('scheme');
    $query     = $get('query');
    $fragment  = $get('fragment');
    $authority =
        ($userinfo !== null ? "$userinfo@" : '') .
        $get('host') .
        ($port ? ":$port" : '');

    return
        (strlen($scheme) ? "$scheme:" : '') .
        (strlen($authority) ? "//$authority" : '') .
        $get('path') .
        (strlen($query) ? "?$query" : '') .
        (strlen($fragment) ? "#$fragment" : '');
}/*}}}*/

// 修改并返回新 URL，回调接收 parse_url 结果（其中 query 已 parse_str 为数组方便直接修改）
function url_transfer($url, closure $transfer_action)
{/*{{{*/
    $url_info = parse_url($url);

    if (isset($url_info['query'])) {
        parse_str($url_info['query'], $query_info);
        $url_info['query'] = $query_info;
    } else {
        $url_info['query'] = [];
    }
    $url_info = call_user_func($transfer_action, $url_info);
    $url_info['query'] = http_build_query($url_info['query']);

    return unparse_url($url_info);
}/*}}}*/

// 注册配置文件根目录或获取已注册的目录列表，传入路径时注册，传入 null 时返回列表
function config_dir(?string $dir = null)
{/*{{{*/
    static $container = [];

    if (! is_null($dir)) {
        $container[] = $dir;
    }

    return $container;
}/*}}}*/

// 加载配置文件，先加载基础配置再按当前环境覆盖（config/{file}.php → config/{ENV}/{file}.php），结果缓存
function config($file_name)
{/*{{{*/
    static $configs = [];

    if (! isset($configs[$file_name])) {

        $directories = config_dir();

        $configs[$file_name] = [];

        foreach ($directories as $dir) {

            if (is_file($config_file = $dir.'/'.$file_name.'.php')) {

                $configs[$file_name] = array_replace_recursive($configs[$file_name], include $config_file);
            }

            if (is_file($env_config_file = $dir.'/'.env().'/'.$file_name.'.php')) {

                $configs[$file_name] = array_replace_recursive($configs[$file_name], include $env_config_file);
            }
        }
    }

    return $configs[$file_name];
}/*}}}*/

// 解析 midwares → resources 间接配置，midware_name 查找 midwares 映射拿到 resource 名，再取 resources 中对应配置
function config_midware($file_name, $midware_name)
{/*{{{*/
    static $configs = [];

    $identifier = $midware_name.'_'.$file_name;

    if (! isset($configs[$identifier])) {

        $midware_config = config($file_name);

        $resource_key = $midware_config['midwares'][$midware_name];

        $configs[$identifier] = $midware_config['resources'][$resource_key];
    }

    return $configs[$identifier];
}/*}}}*/

// 立即遍历所有配置目录并加载全部配置文件，避免首次 config() 调用时的 IO 开销
function config_preload()
{/*{{{*/
    $dirs = config_dir();

    $config_names = [];

    foreach ($dirs as $dir) {

        $files = scandir($dir);

        $env_dir = $dir.'/'.env();

        if (is_dir($env_dir)) {

            $files = array_merge($files, scandir($env_dir));
        }

        foreach ($files as $file) {

            if ($file[0] !== '.') {

                if ((! is_dir($dir.'/'.$file)) && ! is_dir($env_dir.'/'.$file)) {

                    $config_name = pathinfo($file, PATHINFO_FILENAME);

                    if (! isset($config_names[$config_name])) {

                        $config_names[$config_name] = true;

                        config($config_name);
                    }
                }
            }
        }
    }

    return $config_names;
}/*}}}*/

// 获取当前运行环境名，从 $_SERVER['ENV'] 取值，未设置时默认 production
function env()
{/*{{{*/
    return $_SERVER['ENV'] ?? 'production';
}/*}}}*/

// 判断当前环境是否为指定环境，等价于 env() === $env
function is_env($env)
{/*{{{*/
    return env() === $env;
}/*}}}*/

// 检查变量是否不为空，即 !empty() 的语义反转，便于链式条件判断
function not_empty($mixed)
{/*{{{*/
    return !empty($mixed);
}/*}}}*/

// 检查变量是否不为 null，即 !is_null() 的语义反转，便于链式条件判断
function not_null($mixed)
{/*{{{*/
    return !is_null($mixed);
}/*}}}*/

// 检查传入的所有变量是否都为空，可变参数，所有参数 empty() 为 true 时返回 true
function all_empty(...$args)
{/*{{{*/
    foreach ($args as $arg) {

        if (not_empty($arg)) return false;
    }

    return true;
}/*}}}*/

// 检查传入的所有变量是否都为 null，可变参数，所有参数 is_null() 为 true 时返回 true
function all_null(...$args)
{/*{{{*/
    foreach ($args as $arg) {

        if (not_null($arg)) return false;
    }

    return true;
}/*}}}*/

// 检查传入的所有变量是否都不为空，可变参数，等价于 !has_empty(...$args)
function all_not_empty(...$args)
{/*{{{*/
    return ! has_empty(...$args);
}/*}}}*/

// 检查传入的所有变量是否都不为 null，可变参数，等价于 !has_null(...$args)
function all_not_null(...$args)
{/*{{{*/
    return ! has_null(...$args);
}/*}}}*/

// 检查传入的变量中是否有空值，可变参数，任一参数 empty() 为 true 时返回 true
function has_empty(...$args)
{/*{{{*/
    foreach ($args as $arg) {

        if (empty($arg)) return true;
    }

    return false;
}/*}}}*/

// 检查传入的变量中是否有 null，可变参数，任一参数 is_null() 为 true 时返回 true
function has_null(...$args)
{/*{{{*/
    foreach ($args as $arg) {

        if (is_null($arg)) return true;
    }

    return false;
}/*}}}*/

// 格式化时间，expression 可为 null（当前时间）、时间戳、strtotime 相对描述（如 '+1 days'、'last friday'）
function datetime($expression = null, $format = 'Y-m-d H:i:s')
{/*{{{*/
    if (is_null($expression)) {
        $time = time();
    } elseif (is_numeric($expression)) {
        $time = $expression;
    } else {
        $time = strtotime($expression);
    }

    return date($format, $time);
}/*}}}*/

// 计算两个时间的差异，format 除 DateInterval 标准格式外额外支持 %td/%th/%tm/%ts 总差异占位符
function datetime_diff($datetime1, $datetime2, $format = '%ts')
{/*{{{*/
    $interval = date_diff(
        date_create($datetime1),
        date_create($datetime2)
    );

    $res = $interval->format($format);

    $total_hours = $interval->days * 24 + $interval->h;

    $total_minutes = $total_hours * 60 + $interval->i;

    $total_seconds = $total_minutes * 60 + $interval->s;

    foreach ([
        '%td' => $interval->days,
        '%th' => $total_hours,
        '%tm' => $total_minutes,
        '%ts' => $total_seconds,
    ] as $k => $v) {
        $res = str_replace($k, $v, $res);
    }

    return $res;
}/*}}}*/

// 发起 HTTP 请求，可传 URL 字符串（GET）或请求信息数组，支持 retry 重试、timeouted 超时回调、HTTP 状态码回调、自定义 method/header/cookie
function http($args)
{/*{{{*/
    $request_info = [
        'url' => 'http://127.0.0.1/',
        //'method' => 'GET',
        'data' => [],
        'header' => [],
        'cookie' => [],
        'timeout' => 3,
        'retry' => 3,
        'option' => [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36',
        ],
    ];

    if (is_url($args)) {
        $request_info['url'] = $args;
    } elseif (is_array($args)) {
        $request_info = array_replace($request_info, $args);
    }

    $ch = curl_init();

    if (
        isset($request_info['method'])
        && $request_info['method'] === 'GET'
        && ! empty($request_info['data'])
    ) {
        $request_info['url'] = url_transfer($request_info['url'], function ($url_info) use ($request_info) {
            $url_info['query'] = array_replace($url_info['query'], $request_info['data']);
            return $url_info;
        });
        $request_info['data'] = [];
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $request_info['url'],
        CURLOPT_TIMEOUT => $request_info['timeout'],
        CURLOPT_CONNECTTIMEOUT => $request_info['timeout'],
    ]);

    curl_setopt_array($ch, $request_info['option']);

    if (
        ! empty($request_info['data'])
        || (
            ! empty($request_info['method'])
            && $request_info['method'] === 'POST'
        )
    ) {

        $request_info_data =
            is_array($request_info['data']) ?
            http_build_query($request_info['data']) :
            $request_info['data'];

        curl_setopt_array($ch, [
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $request_info_data,
        ]);
    }

    if (! empty($request_info['header'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_info['header']);
    }

    if (! empty($request_info['cookie'])) {

        $request_info_cookie = '';

        if (is_array($request_info['cookie'])) {
            $request_info_cookie = http_build_query($request_info['cookie'], '', ';').';';
        }

        curl_setopt($ch, CURLOPT_COOKIE, $request_info_cookie);
    }

    $retry = $request_info['retry'];

    while ($retry-- > 0) {

        $res = curl_exec($ch);
        $errno = curl_errno($ch);

        if (CURLE_OK === $errno) {
            break;
        }
    }

    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (0 === $code) {
        if (CURLE_OPERATION_TIMEDOUT === $errno) {
            if (! empty($request_info['timeouted']) && $request_info['timeouted'] instanceof closure) {
                return call_user_func($request_info['timeouted']);
            }
        }

        throw new Exception('http url '.$request_info['url'].' '.curl_strerror($errno));
    }

    curl_close($ch);

    if (! empty($request_info[$code]) && $request_info[$code] instanceof closure) {
        return call_user_func($request_info[$code], $res, $code);
    }

    if (! empty($request_info[0]) && $request_info[0] instanceof closure) {
        return call_user_func($request_info[0], $res, $code, $errno);
    }

    return $res;
}/*}}}*/

// http() 的封装，将响应 JSON 解码为数组返回
function http_json($args)
{/*{{{*/
    return json_decode(http($args), true);
}/*}}}*/

// http() 的封装，通过 SimpleXML 解析响应 XML 并转为数组返回
function http_xml($args)
{/*{{{*/
    $raw_res = http($args);

    $raw_xml = simplexml_load_string($raw_res, 'SimpleXMLElement', LIBXML_NOCDATA);

    return json_decode(json_encode($raw_xml), true);
}/*}}}*/

// 获取类的单例，相同类名且相同构造参数返回同一实例，不同参数则创建新实例
function instance($class_name, array $args = [])
{/*{{{*/
    static $container = [];

    if (empty($args)) {
        $instance_identifier = $class_name;
    } else {
        $instance_identifier = $class_name.serialize($args);
    }

    if (!isset($container[$instance_identifier])) {

        $container[$instance_identifier] = new $class_name(...$args);
    }

    return $container[$instance_identifier];
}/*}}}*/

// 将数据编码为 JSON 字符串，默认 JSON_UNESCAPED_UNICODE 不转义中文
function json($data = [])
{/*{{{*/
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}/*}}}*/

// 按传入顺序依次定义位运算常量，值为 2^0, 2^1, 2^2...，用于位运算组合选项
function option_define(...$options)
{/*{{{*/
    foreach ($options as $i => $option)
    {
        define($option, pow(2, $i));
    }
}/*}}}*/

// 判断位运算组合选项中是否包含指定选项，$options 可为多个选项的位或结果（如 OPTION_A | OPTION_B）
function has_option($options, $define)
{/*{{{*/
    return $options === ($options | $define);
}/*}}}*/
