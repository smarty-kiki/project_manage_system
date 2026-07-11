<?php

if_get('/', function ()
{
    return render('index/landing', [
        'title' => '项目管理系统 — 让项目管理回归简单',
    ]);
});

if_get('/health_check', function ()
{
    return 'ok';
});

if_get('/error_code_maps', function ()
{
    return config('error_code');
});
