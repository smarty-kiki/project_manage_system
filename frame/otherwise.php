<?php

define('OTHERWISE_MESSAGE_DELIMITER', '---');

class business_exception extends exception { }

// $assertion 为 true 时正常通过，为 false 时抛出异常中断流程
function otherwise($assertion, $description = 'assertion is not true', $exception_class_name = 'exception', $exception_code = 'OTHERWISE_DEFAULT')
{
    if (! $assertion) {
        throw new $exception_class_name($exception_code.OTHERWISE_MESSAGE_DELIMITER.$description, -1);
    }
}

// 从 {code}---{description} 格式解析为 ['code' => ..., 'message' => ...]
function otherwise_get_error_info(throwable $ex)
{
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
}

function otherwise_get_error_message(throwable $ex)
{
    $error_info = otherwise_get_error_info($ex);

    return $error_info['message'];
}

// $assertion 为 true 时正常通过，为 false 时抛出业务异常；replace_contents 可替换文案中的占位符
function otherwise_error_code($error_code, $assertion, array $replace_contents = [])
{
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
}
