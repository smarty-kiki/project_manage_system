<?php

// 【私有函数，禁止在其他文件中调用】生成日志时间戳前缀，精度到微秒
function _log_prefix()
{/*{{{*/
    return '['.date('Y-m-d H:i:s '.substr((string) microtime(), 2, 8)).']';
}/*}}}*/

// 记录异常到 exception 日志文件，路径由 config('log')['exception_path'] 指定
function log_exception(throwable $ex)
{/*{{{*/

    $log = config('log');

    error_log(_log_prefix().$ex."\n", 3, $log['exception_path']);
}/*}}}*/

// 记录通知消息到 notice 日志文件，路径由 config('log')['notice_path'] 指定
function log_notice($message)
{/*{{{*/

    $log = config('log');

    error_log(_log_prefix().$message."\n", 3, $log['notice_path']);
}/*}}}*/

// 记录模块日志，带 module 标识便于检索，路径由 config('log')['module_path'] 指定
function log_module($module, $message)
{/*{{{*/
    $log = config('log');

    error_log(_log_prefix().'['.$module.'] '.$message."\n", 3, $log['module_path']);
}/*}}}*/
