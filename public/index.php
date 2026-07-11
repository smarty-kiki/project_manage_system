<?php

header('Access-Control-Allow-Origin: *');

// init
include __DIR__.'/../bootstrap.php';
include FRAME_DIR.'/php_fpm.php';
include FRAME_DIR.'/view_blade.php';

view_path(VIEW_DIR.'/');
view_compiler(blade_view_compiler_generate());

set_error_handler('http_err_action', E_ALL);
set_exception_handler('http_ex_action');
register_shutdown_function('http_fatal_err_action');

if_has_exception(function ($ex) {

    $error_info = otherwise_get_error_info($ex);

    if ($ex instanceof business_exception) {
        log_module('business_exception', $error_info['message']);
    } else {
        log_exception($ex);
    }

    if (is_ajax()) {

        header('Content-type: application/json');
        return json([
            'code' => $error_info['code'],
            'msg' => $error_info['message'],
            'data' => [],
        ]);
    } else {

        return render('error/500', [
            'code' => $error_info['code'],
            'message' => $error_info['message'],
        ]);
    }
});

function require_user_team_or_redirect($action, $args)
{
    $user_id = get_current_user_id();
    if (!$user_id) {
        return false;
    }

    if (user_has_any_team($user_id)) {
        return false;
    }

    $no_team_routes = [
        '/',
        '/account/enter',
        '/account/set_name',
        '/account/team/create',
        '/api/account/send_code',
        '/api/account/verify_code',
        '/api/account/set_name',
        '/api/team/create',
        '/api/team/list',
        '/account/logout',
        '/team/*/member',
        '/team/*/project',
        '/team/*/project/*',
        '/api/project/list',
        '/api/project/create',
        '/api/project/detail',
        '/api/system/create',
        '/api/module/create',
        '/api/business_process/create',
        '/api/process_node/create',
        '/api/requirement/create',
        '/api/bug/create',
        '/api/project_role/create',
        '/api/project_role/list',
        '/api/project_role/update_node',
        '/api/project_role/link_module',
        '/api/project_role/unlink_module',
    ];

    $current_route = matched_rule();

    foreach ($no_team_routes as $route) {
        if ($current_route === $route || starts_with($current_route, $route . '/')) {
            return false;
        }
    }

    redirect('/account/team/create');
    return true;
}

if_verify(function ($action, $args) {

    if (require_user_team_or_redirect($action, $args)) {
        return;
    }

    return unit_of_work(function () use ($action, $args){

        $data = call_user_func_array($action, $args);

        if (has_redirect()) {
            return ;
        }

        if (is_string($data)) {

            header('Content-type: text/html');

            return $data;

        } else {

            header('Content-type: application/json');

            return json([
                'code' => 0,
                'msg' => '',
                'data' => $data
            ]);
        }
    });
});

// init interceptor

// init 404 handler
if_not_found(function () {

    if (is_ajax()) {

        header('Content-type: application/json');
        return json([
            'code' => 404,
            'msg' => 'Not Found',
            'data' => [],
        ]);
    }

    return render('error/404');
});

// init controller
include CONTROLLER_DIR.'/base.php';
include CONTROLLER_DIR.'/account.php';
include CONTROLLER_DIR.'/team.php';
include CONTROLLER_DIR.'/project.php';

// fix
not_found();
