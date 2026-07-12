<?php

// Project create page
if_get('/team/*/project/create', function ($team_id) {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $team = dao('team')->find_by_id($team_id);
    if ($team->is_null() || $team->is_deleted()) {
        return redirect('/account/team');
    }

    $user_id = get_current_user_id();
    $role = get_user_team_role($team_id, $user_id);
    if ($role === null) {
        return redirect('/account/team');
    }

    $user_teams = get_user_teams($user_id);
    $projects = dao('project')->find_all_by_column(['team_id' => $team_id]);
    $user = dao('team_account')->find_by_id($user_id);

    return render('project/create', [
        'title' => '新建项目',
        'team' => $team,
        'current_team' => $team,
        'user_teams' => $user_teams,
        'projects' => $projects,
        'user' => $user,
    ]);
});

// Project detail page
if_get('/team/*/project/*', function ($team_id, $project_id) {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $team = dao('team')->find_by_id($team_id);
    if ($team->is_null() || $team->is_deleted()) {
        return redirect('/account/team');
    }

    set_current_team_id($team_id);

    $user_id = get_current_user_id();
    $role = get_user_team_role($team_id, $user_id);
    if ($role === null) {
        return redirect('/account/team');
    }

    $project = dao('project')->find_by_id($project_id);
    if ($project->is_null() || $project->team_id != $team_id) {
        return redirect('/team/' . $team_id . '/dashboard');
    }

    $systems = dao('system')->find_all_by_column(['project_id' => (int)$project_id]);
    $modules = dao('module')->find_all_by_column(['project_id' => (int)$project_id]);
    $business_processes = dao('business_process')->find_all_by_column(['project_id' => (int)$project_id]);
    $process_nodes = dao('process_node')->find_all_by_column(['project_id' => (int)$project_id]);
    $requirements = dao('requirement')->find_all_by_column(['project_id' => (int)$project_id]);
    $bugs = dao('bug')->find_all_by_column(['project_id' => (int)$project_id]);
    $project_roles = dao('project_role')->find_all_by_column(['project_id' => (int)$project_id]);

    $role_modules = [];
    foreach ($project_roles as $role) {
        $links = dao('project_role_module')->find_all_by_column(['project_role_id' => $role->id]);
        foreach ($links as $link) {
            $role_modules[$role->id][] = $link->module_id;
        }
    }

    $projects = dao('project')->find_all_by_column(['team_id' => $team_id]);
    $user = dao('team_account')->find_by_id($user_id);
    $user_teams = get_user_teams($user_id);

    return render('project/detail', [
        'title' => $project->name . ' - 项目详情',
        'team' => $team,
        'project' => $project,
        'projects' => $projects,
        'current_team' => $team,
        'user_teams' => $user_teams,
        'user' => $user,
        'current_user_role' => $role,
        'current_project_id' => (int)$project_id,
        'current_project_name' => $project->name,
        'systems' => $systems,
        'modules' => $modules,
        'business_processes' => $business_processes,
        'process_nodes' => $process_nodes,
        'requirements' => $requirements,
        'bugs' => $bugs,
        'project_roles' => $project_roles,
        'role_modules' => $role_modules,
    ]);
});

// API: List projects
if_get('/api/project/list', function () {
    $team_id = get_current_team_id();
    if (!$team_id) {
        return [];
    }

    $projects = dao('project')->find_all_by_column(['team_id' => $team_id]);

    $result = [];
    foreach ($projects as $p) {
        $result[] = [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'create_time' => (string)$p->create_time,
        ];
    }

    return $result;
});

// API: Create project
if_post('/api/project/create', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user_id = get_current_user_id();

    $team_id = input('team_id', '');
    $name = trim(input('name', ''));
    $description = trim(input('description', ''));

    if (all_empty($team_id, $name)) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'team_id and name']);
    }

    $team = dao('team')->find_by_id($team_id);
    if ($team->is_null() || $team->is_deleted()) {
        otherwise_error_code('TEAM_NOT_FOUND', false);
    }

    $role = get_user_team_role($team_id, $user_id);
    if ($role === null) {
        otherwise_error_code('PERMISSION_DENIED', false);
    }

    $project = project::create((int)$team_id, $name, $description, $user_id);

    return [
        'id' => $project->id,
        'name' => $project->name,
        'description' => $project->description,
    ];
});

// API: Get project detail
if_get('/api/project/detail', function () {
    $project_id = input('project_id', '');
    if (!$project_id) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'project_id']);
    }

    $project = dao('project')->find_by_id($project_id);
    if ($project->is_null()) {
        otherwise_error_code('PROJECT_NOT_FOUND', false);
    }

    return [
        'id' => $project->id,
        'name' => $project->name,
        'description' => $project->description,
        'team_id' => $project->team_id,
        'creator_id' => $project->creator_id,
        'create_time' => (string)$project->create_time,
        'update_time' => (string)$project->update_time,
    ];
});

// API: Create system
if_post('/api/system/create', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user_id = get_current_user_id();
    $project_id = (int)input('project_id', 0);
    $name = trim(input('name', ''));
    $git_url = trim(input('git_url', ''));
    $description = trim(input('description', ''));

    if (!$project_id || !$name) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'project_id and name']);
    }

    $project = dao('project')->find_by_id($project_id);
    if ($project->is_null()) {
        otherwise_error_code('PROJECT_NOT_FOUND', false);
    }

    $system = system::create($project_id, $name, $description, $git_url);

    return [
        'id' => $system->id,
        'name' => $system->name,
        'git_url' => $system->git_url,
        'description' => $system->description,
    ];
});

// API: Create module
if_post('/api/module/create', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user_id = get_current_user_id();
    $system_id = (int)input('system_id', 0);
    $project_id = (int)input('project_id', 0);
    $name = trim(input('name', ''));
    $description = trim(input('description', ''));

    if (!$name) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'name']);
    }

    $system = dao('system')->find_by_id($system_id);
    if ($system->is_null()) {
        otherwise_error_code('SYSTEM_NOT_FOUND', false);
    }

    $module = module::create($project_id, $system_id, $name, $description);

    return [
        'id' => $module->id,
        'name' => $module->name,
        'description' => $module->description,
    ];
});

// API: Create business process
if_post('/api/business_process/create', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user_id = get_current_user_id();
    $project_id = (int)input('project_id', 0);
    $name = trim(input('name', ''));
    $description = trim(input('description', ''));
    $initiator_role_id = (int)input('initiator_role_id', 0);

    if (!$project_id || !$name) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'project_id and name']);
    }

    $project = dao('project')->find_by_id($project_id);
    if ($project->is_null()) {
        otherwise_error_code('PROJECT_NOT_FOUND', false);
    }

    $bp = business_process::create($project_id, $name, $description, $initiator_role_id);

    return [
        'id' => $bp->id,
        'name' => $bp->name,
        'description' => $bp->description,
        'initiator_role_id' => $bp->initiator_role_id,
    ];
});

// API: Create process node
if_post('/api/process_node/create', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user_id = get_current_user_id();
    $business_process_id = (int)input('business_process_id', 0);
    $project_id = (int)input('project_id', 0);
    $project_role_id = (int)input('project_role_id', 0);
    $name = trim(input('name', ''));
    $description = trim(input('description', ''));
    $sort_order = (int)input('sort_order', 0);

    if (!$business_process_id || !$name) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'business_process_id and name']);
    }

    $bp = dao('business_process')->find_by_id($business_process_id);
    if ($bp->is_null()) {
        otherwise_error_code('BUSINESS_PROCESS_NOT_FOUND', false);
    }

    $node = process_node::create($business_process_id, $name, $description, $sort_order, $project_id, $project_role_id);

    return [
        'id' => $node->id,
        'name' => $node->name,
        'description' => $node->description,
        'sort_order' => $node->sort_order,
        'project_role_id' => $node->project_role_id,
    ];
});

// API: Create requirement
if_post('/api/requirement/create', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user_id = get_current_user_id();
    $project_id = (int)input('project_id', 0);
    $system_id = (int)input('system_id', 0);
    $module_id = (int)input('module_id', 0);
    $role_id = (int)input('role_id', 0);
    $name = trim(input('name', ''));
    $description = trim(input('description', ''));

    if (!$project_id || !$name) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'project_id and name']);
    }

    $project = dao('project')->find_by_id($project_id);
    if ($project->is_null()) {
        otherwise_error_code('PROJECT_NOT_FOUND', false);
    }

    $req = requirement::create($project_id, $system_id, $module_id, $name, $description);
    $req->role_id = $role_id;

    return [
        'id' => $req->id,
        'name' => $req->name,
        'system_id' => $req->system_id,
        'module_id' => $req->module_id,
        'role_id' => $req->role_id,
        'description' => $req->description,
    ];
});

// API: Create bug
if_post('/api/bug/create', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user_id = get_current_user_id();
    $project_id = (int)input('project_id', 0);
    $requirement_id = (int)input('requirement_id', 0);
    $role_id = (int)input('role_id', 0);
    $name = trim(input('name', ''));
    $description = trim(input('description', ''));

    if (!$project_id || !$name) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'project_id and name']);
    }

    $project = dao('project')->find_by_id($project_id);
    if ($project->is_null()) {
        otherwise_error_code('PROJECT_NOT_FOUND', false);
    }

    $bug = bug::create($project_id, $requirement_id, $name, $description);
    $bug->role_id = $role_id;

    return [
        'id' => $bug->id,
        'name' => $bug->name,
        'requirement_id' => $bug->requirement_id,
        'role_id' => $bug->role_id,
        'description' => $bug->description,
    ];
});

// API: Create project role
if_post('/api/project_role/create', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user_id = get_current_user_id();
    $project_id = (int)input('project_id', 0);
    $name = trim(input('name', ''));
    $description = trim(input('description', ''));
    $process_node_id = (int)input('process_node_id', 0);

    if (!$project_id || !$name) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'project_id and name']);
    }

    $project = dao('project')->find_by_id($project_id);
    if ($project->is_null()) {
        otherwise_error_code('PROJECT_NOT_FOUND', false);
    }

    $role = project_role::create($project_id, $name, $description, $process_node_id);

    return [
        'id' => $role->id,
        'name' => $role->name,
        'description' => $role->description,
        'process_node_id' => $role->process_node_id,
    ];
});

// API: List project roles
if_get('/api/project_role/list', function () {
    $project_id = (int)input('project_id', 0);
    if (!$project_id) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'project_id']);
    }

    $roles = dao('project_role')->find_all_by_column(['project_id' => $project_id]);

    $result = [];
    foreach ($roles as $r) {
        $result[] = [
            'id' => $r->id,
            'name' => $r->name,
            'description' => $r->description,
            'process_node_id' => $r->process_node_id,
        ];
    }

    return $result;
});

// API: Update role process node
if_post('/api/project_role/update_node', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $role_id = (int)input('role_id', 0);
    $process_node_id = (int)input('process_node_id', 0);

    if (!$role_id) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'role_id']);
    }

    $role = dao('project_role')->find_by_id($role_id);
    if ($role->is_null()) {
        otherwise_error_code('ROLE_NOT_FOUND', false);
    }

    $role->process_node_id = $process_node_id;

    return ['id' => $role->id, 'process_node_id' => $role->process_node_id];
});

// API: Link role to module
if_post('/api/project_role/link_module', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $role_id = (int)input('role_id', 0);
    $module_id = (int)input('module_id', 0);

    if (!$role_id || !$module_id) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'role_id and module_id']);
    }

    $role = dao('project_role')->find_by_id($role_id);
    if ($role->is_null()) {
        otherwise_error_code('ROLE_NOT_FOUND', false);
    }

    $module = dao('module')->find_by_id($module_id);
    if ($module->is_null()) {
        otherwise_error_code('MODULE_NOT_FOUND', false);
    }

    $link = project_role_module::create($role_id, $module_id);

    return ['id' => $link->id];
});

// API: Unlink role from module
if_post('/api/project_role/unlink_module', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $role_id = (int)input('role_id', 0);
    $module_id = (int)input('module_id', 0);

    if (!$role_id || !$module_id) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'role_id and module_id']);
    }

    $link = dao('project_role_module')->find_by_column(['project_role_id' => $role_id, 'module_id' => $module_id]);
    if ($link->is_not_null()) {
        $link->force_delete();
    }

    return ['success' => true];
});

