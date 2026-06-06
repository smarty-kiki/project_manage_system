<?php

// 【私有函数，禁止在其他文件中调用】解析命令行参数，-x 格式为布尔 true，--key=value 格式为字符串值，返回 [文件名, 命令, 参数数组]
function _command_prepare_arguments()
{/*{{{*/
    static $file_name = '';
    static $command = '';
    static $arguments = [];

    if (! $file_name) {
        global $argv;
        $file_name = array_shift($argv);
        $command = array_shift($argv);

        foreach ($argv as $num => $argument) {

            switch (true) {

            case preg_match('/^-([a-zA-Z_]+)$/', $argument, $res):
                $arguments[$res[1]] = true;
                break;

            case preg_match('/^--([a-zA-Z_]+)=(.*)$/', $argument, $res):
                $arguments[$res[1]] = $res[2];
                break;
            }
        }
    }

    return [$file_name, $command, $arguments];
}/*}}}*/

// 获取命令行参数值，$default 为 null 时参数不存在则报错退出，否则返回 default
function command_paramater($key, $default = null)
{/*{{{*/
    list($file_name, $command, $arguments) = _command_prepare_arguments();

    if (! isset($arguments[$key])) {
        if (is_null($default)) {
            echo "\033[31m需要加 --$key=xxx 或者 -$key\033[0m\n";
            exit(1);
        } else {
            return $default;
        }
    }

    return $arguments[$key];
}/*}}}*/

// 注册命令并与当前命令匹配，匹配成功时执行闭包并 exit，否则将规则和描述收集到 command_not_found
function command($rule, $description, closure $action)
{/*{{{*/
    list($file_name, $command, $arguments) = _command_prepare_arguments();

    if ($command === $rule) {
        exit($action());
    } else {
        command_not_found($rule, $description);
    }
}/*}}}*/

// 注册命令未匹配时的回调，传入闭包时注册，传入 null 时返回已注册的回调
function if_command_not_found(?closure $action = null)
{/*{{{*/
    static $container = null;

    if (!empty($action)) {
        return $container = $action;
    }

    return $container;
}/*}}}*/

// 内部收集所有注册命令的规则和描述，当无匹配命令时触发 if_command_not_found 回调并 exit
function command_not_found(?string $rule = null, ?string $description = null)
{/*{{{*/
    static $rules = [];
    static $descriptions = [];

    if (is_null($rule) && is_null($description)) {
        call_user_func(if_command_not_found(), $rules, $descriptions);
        exit;
    } else {
        $rules[] = $rule;
        $descriptions[] = $description;
    }
}/*}}}*/

// 注册 Tab 补全回调，闭包接收 readline_info 数组，返回候选补全字符串数组
function command_read_completions(?closure $closure = null)
{/*{{{*/
    static $container = null;

    if (! is_null($closure)) {

        $container = $closure;
    }

    return $container;
}/*}}}*/

// 【私有函数，禁止在其他文件中调用】带 Tab 补全的 readline 交互输入，prompt 中 \n 前的部分会直接输出
function _command_readline($prompt)
{/*{{{*/
    readline_completion_function(function ($block_buffer, $block_start, $point) {

        $buffer_info = readline_info();
        $buffer_info['block_buffer'] = $block_buffer;
        $buffer_info['block_start'] = $block_start;

        $closure = command_read_completions();

        if (is_null($closure)) {

            return [];
        }

        $result = call_user_func($closure, $buffer_info);

        if ($block_buffer === '') {
            return $result;
        }

        return array_filter($result, function ($val) use ($block_buffer) {
            return starts_with($val, $block_buffer);
        });
    });

    $prompting = true;
    $result = '';

    $prompt_infos = explode("\n", $prompt);
    $last_prompt_line = array_pop($prompt_infos);

    if (! empty($prompt_infos)) {

        echo implode("\n", $prompt_infos)."\n";
    }

    readline_callback_handler_install($last_prompt_line, function ($line) use (&$prompting, &$result) {
        readline_add_history($line);
        $result = $line;
        $prompting = false;
        readline_callback_handler_remove();
    });

    while ($prompting) {
        $w = NULL;
        $e = NULL;
        $r = [STDIN];
        $n = stream_select($r, $w, $e, null);
        if ($n && in_array(STDIN, $r)) {
            readline_callback_read_char();
        }
    }

    return $result;
}/*}}}*/

// 交互式读取用户输入，传 options 时显示编号选项菜单并要求选择，否则自由输入，空输入返回 default
function command_read($prompt, $default = true, array $options = [])
{/*{{{*/
    if ($options) {
        $prompt = "$prompt (Default: $default)\n\n";
        foreach ($options as $key => $option) {
            $prompt .= "  $key) $option\n";
        }

        $prompt .= "\n> ";

        do {
            $result = _command_readline($prompt);
            $result = trim($result);
            $result = ($result === '')? $default: $result;
        } while (! array_key_exists($result, $options));

        return $options[$result];
    } else {
        $prompt = "$prompt (Default: $default)\n> ";
        $result = _command_readline($prompt);
        $result = trim($result);
        return ($result === '')? $default: $result;
    }
}/*}}}*/

// y/n 确认输入，返回布尔值，default 为默认选择（'y' 或 'n'）
function command_read_bool($prompt, $default = 'n')
{/*{{{*/
    $map = [
        'y' => true,
        'n' => false,
    ];

    do {

        $res = command_read("$prompt [y/n]?", $default);

    } while (! array_key_exists($res, $map));

    return $map[$res];
}/*}}}*/
