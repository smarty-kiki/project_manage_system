<?php

// 异常消息中错误码与描述文本的分隔符，格式为 {code}---{description}
define('OTHERWISE_MESSAGE_DELIMITER', '---');

// 业务异常类，由 otherwise_error_code 抛出
class business_exception extends exception { }

// 通用断言，$assertion 为 false 时抛出指定异常，消息格式为 {code}---{description}，exception_class_name 可传类名字符串
function otherwise($assertion, $description = 'assertion is not true', $exception_class_name = 'exception', $exception_code = 'OTHERWISE_DEFAULT')
{/*{{{*/
    if (! $assertion) {
        throw new $exception_class_name($exception_code.OTHERWISE_MESSAGE_DELIMITER.$description, -1);
    }
}/*}}}*/

// 从异常消息中解析错误码和描述，格式为 {code}---{description}，返回 ['code' => ..., 'message' => ...]
function otherwise_get_error_info(throwable $ex)
{/*{{{*/
    $message = $ex->getMessage();

    $info = explode(OTHERWISE_MESSAGE_DELIMITER, $message);

    if (count($info) > 1) {
        return [
            'code' => $info[0],
            'message' => $info[1],
        ];
    } else {
        return [
            'code' => $ex->getCode(),
            'message' => $message,
        ];
    }
}/*}}}*/

// 从异常消息中提取描述文本，otherwise_get_error_info 的便捷版
function otherwise_get_error_message(throwable $ex)
{/*{{{*/
    $error_info = otherwise_get_error_info($ex);

    return $error_info['message'];
}/*}}}*/

// 基于错误码配置的断言，$assertion 为 false 时从 config('error_code') 中查找 $error_code 对应文案并抛出 business_exception，replace_contents 可替换文案中的占位符
function otherwise_error_code($error_code, $assertion, array $replace_contents = [])
{/*{{{*/
    if (! $assertion) {

        $config = config('error_code');

        $description = $config[$error_code];

        if ($replace_contents) {
            foreach ($replace_contents as $replace_key => $content) {
                $description = str_replace($replace_key, $content, $description);
            }
        }
        
        throw new business_exception($error_code.OTHERWISE_MESSAGE_DELIMITER.$description, -1);
    }
}/*}}}*/
