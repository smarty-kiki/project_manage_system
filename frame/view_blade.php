<?php 

// Stream wrapper 协议名称，用于 blade:// 虚拟文件系统
define('BLADE_STREAM_SCHEMA', 'blade');

// Blade 模板 stream wrapper，实现 PHP 流协议接口，将编译后的模板字符串模拟为可 include 的文件
class blade_stream
{/*{{{*/
    public $context;                    // 流上下文
    private $string;                    // 模板编译后的 PHP 字符串内容
    private $position;                  // 当前流读取位置（字节偏移）
    private $options = [];              // 流选项配置

    private static $streams = [];       // 已注册的模板流，key 为路径，value 为编译后的 PHP 代码

    // 写入模板编译内容到流，供后续 stream_open 读取
    public static function stream_write($path, $template)
    {/*{{{*/
        return self::$streams[$path] = $template;
    }/*}}}*/

    // 检查指定路径的流是否已存在
    public static function has_stream($path)
    {/*{{{*/
        return isset(self::$streams[$path]);
    }/*}}}*/

    // 生成 blade:// 协议的虚拟流路径
    public static function generate_stream_path($view)
    {/*{{{*/
        return BLADE_STREAM_SCHEMA.'://'.$view;
    }/*}}}*/

    // 打开流，从 URL 解析路径并加载对应的模板内容
    public function stream_open($path, $mode, $options, &$opened_path)
    {/*{{{*/
        $url_info = parse_url($path);
        $path = $url_info["host"].($url_info['path'] ?? '');

        $this->string = self::$streams[$path];
        $this->position = 0;
        return true;
    }/*}}}*/

    // 从流中读取 $count 字节内容，更新读取位置
    public function stream_read($count)
    {/*{{{*/
        $ret = substr($this->string, $this->position, $count);

        $this->position += strlen($ret);

        return $ret;
    }/*}}}*/

    // 流结束判断（空实现，满足 stream wrapper 接口要求）
    public function stream_eof()
    {/*{{{*/
    }/*}}}*/

    // 流状态查询（空实现，满足 stream wrapper 接口要求）
    public function stream_stat()
    {/*{{{*/
    }/*}}}*/

    // 设置流选项，记录到 options 数组中
    public function stream_set_option($option, $arg1, $arg2)
    {/*{{{*/
        $this->options[$option] = [
            'arg1' => $arg1,
            'arg2' => $arg2,
        ];

        return true;
    }/*}}}*/
}/*}}}*/

// 注册 blade:// stream wrapper，使编译后的模板字符串可通过 include 加载
stream_wrapper_register(BLADE_STREAM_SCHEMA, "blade_stream");

// 【私有函数，禁止在其他文件中调用】生成带括号指令的正则（如 @unless(expr)、@if(expr)），用于匹配条件块
function _blade_regular($compiler)
{/*{{{*/
    return '/(?<!\w)(\s*)@'.$compiler.'(\s*\(.*\))/';
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】生成双参数指令的正则（如 @include('view')），用于匹配带参数调用
function _blade_brace_regular($compiler)
{/*{{{*/
    return '/(?<!\w)(\s*)@'.$compiler.'\((\s*.*)\)/';
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】生成无参数指令的正则（如 @else、@endunless），仅匹配指令名本身
function _blade_plain_regular($compiler)
{/*{{{*/
    return '/(?<!\w)(\s*)@'.$compiler.'(\s*)/';
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】编译 @include('view') 指令，替换为 include 编译后的视图文件路径
function _blade_compile_includes($value)
{/*{{{*/
    $pattern = _blade_brace_regular('include');

    $res = preg_match_all($pattern, $value, $matches);

    if ($res > 0) {

        foreach ($matches[2] as $i => $template_path) {

            $template_path = trim($template_path, " \t\n\r\0\x0B'\"");
            $view_compiled_path = blade_view_compiler($template_path);

            $value = str_replace($matches[0][$i], "<?php include '$view_compiled_path'; ?>", $value);
        }
    }

    return $value;
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】编译 {{-- 注释 --}} 为 PHP 注释块
function _blade_compile_comments($value)
{/*{{{*/
    return preg_replace('/{{--((.|\s)*?)--}}/', '<?php /*$1*/ ?>', $value);
}/*}}}*/

/* 【私有函数，禁止在其他文件中调用】编译 @php ... @endphp 块为原生 <?php ... ?> */
function _blade_compile_php_code($value)
{/*{{{*/
    return preg_replace('/@php((.|\s)*?)@endphp/', '<?php $1 ?>', $value);
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】编译 {{{ $var }}} 转义输出（htmlentities），支持 or 默认值语法
function _blade_compile_escaped_echos($value)
{/*{{{*/
    $pattern = '/{{{\s*(.+?)\s*}}}/s';

    $callback = function($matches)
    {
        return '<?php echo htmlentities('.preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $matches[1]).', ENT_QUOTES, "UTF-8", false); ?>';
    };

    return preg_replace_callback($pattern, $callback, $value);
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】编译 {{ $var }} 普通输出，@{{ }} 前缀可转义为原始文本，支持 or 默认值语法
function _blade_compile_echos($value)
{/*{{{*/
    $pattern = '/(@)?{{\s*(.+?)\s*}}/s';

    $callback = function($matches)
    {
        return $matches[1] ? substr($matches[0], 1) : '<?php echo '.preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $matches[2]).'; ?>';
    };

    return preg_replace_callback($pattern, $callback, $value);
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】编译 @if/@elseif/@foreach/@for/@while 开标签为 PHP 冒号语法（如 if(...): ）
function _blade_compile_openings($value)
{/*{{{*/
    $pattern = '/(?(R)\((?:[^\(\)]|(?R))*\)|(?<!\w)(\s*)@(if|elseif|foreach|for|while)(\s*(?R)+))/';

    return preg_replace($pattern, '$1<?php $2$3: ?>', $value);
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】编译 @endif/@endforeach/@endfor/@endwhile 闭标签为 PHP 分号语法
function _blade_compile_closings($value)
{/*{{{*/
    $pattern = '/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/';

    return preg_replace($pattern, '$1<?php $2; ?>$3', $value);
}/*}}}*/

/* 【私有函数，禁止在其他文件中调用】编译 @else 指令为 <?php else: ?> */
function _blade_compile_else($value)
{/*{{{*/
    $pattern = _blade_plain_regular('else');

    return preg_replace($pattern, '$1<?php else: ?>$2', $value);
}/*}}}*/

/* 【私有函数，禁止在其他文件中调用】编译 @unless(expr) 为 <?php if ( !expr ): ?>，实现条件取反 */
function _blade_compile_unless($value)
{/*{{{*/
    $pattern = _blade_regular('unless');

    return preg_replace($pattern, '$1<?php if ( !$2): ?>', $value);
}/*}}}*/

/* 【私有函数，禁止在其他文件中调用】编译 @endunless 为 <?php endif; ?> */
function _blade_compile_endunless($value)
{/*{{{*/
    $pattern = _blade_plain_regular('endunless');

    return preg_replace($pattern, '$1<?php endif; ?>$2', $value);
}/*}}}*/

// 编译 Blade 模板字符串为 PHP，按顺序执行 includes→comments→escaped_echos→echos→openings→closings→else→unless→endunless→php_code 编译步骤
function blade($template)
{/*{{{*/
    static $compilers = array(
        'includes',
        'comments',
        'escaped_echos',
        'echos',
        'openings',
        'closings',
        'else',
        'unless',
        'endunless',
        'php_code',
    );

    foreach ($compilers as $compiler)
    {
        $template = call_user_func("_blade_compile_".$compiler, $template);
    }

    return $template;
}/*}}}*/

// 编译并求值模板字符串，通过 stream wrapper include 执行后返回渲染结果，$args 为模板变量数组
function blade_eval($template, $args = [])
{/*{{{*/
    $path = uniqid('template_', true);

    blade_stream::stream_write($path, blade($template));

    extract($args);

    ob_start();

    try {
        include(blade_stream::generate_stream_path($path));
    } catch (exception $ex) {
        throw $ex;
    } finally {
        $echo = ob_get_contents();
        ob_end_clean();
    }

    return $echo;
}/*}}}*/

// 编译视图文件为 PHP，缓存开启时写入 compiled_path 返回编译文件路径，缓存关闭时使用 stream wrapper，返回可 include 的路径
function blade_view_compiler($view)
{/*{{{*/
    $config = config('blade');

    $view_path = view_path();

    $cache_opened = array_get($config, 'compiled_cache', true);
    $view_compiled_path = array_get($config, 'compiled_path', $view_path);

    $view_file = $view_path.$view.'.php';

    if ($cache_opened) {

        $compiled_file = $view_compiled_path.str_replace('/', '-', $view).'.blade.php';

        if (! is_file($compiled_file)) {

            $template = blade(file_get_contents($view_file));

            file_put_contents($compiled_file, $template);
        }

        return $compiled_file;
    } else {

        if (! blade_stream::has_stream($view)) {

            blade_stream::stream_write($view, blade(file_get_contents($view_file)));
        }

        return blade_stream::generate_stream_path($view);
    }
}/*}}}*/

// 返回 blade_view_compiler 的闭包包装器，用作 view_compiler() 的注册参数
function blade_view_compiler_generate()
{/*{{{*/
    return function ($view) {

        return blade_view_compiler($view);
    };
}/*}}}*/
