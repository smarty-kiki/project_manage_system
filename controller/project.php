<?php

// Project list page
if_get('/team/*/project', function ($team_id) {
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

    $projects = dao('project')->find_all_by_column(['team_id' => $team_id]);
    $user = dao('team_account')->find_by_id($user_id);
    $user_teams = get_user_teams($user_id);

    return render('project/list', [
        'title' => $team->name . ' - 项目',
        'team' => $team,
        'projects' => $projects,
        'current_team' => $team,
        'user_teams' => $user_teams,
        'user' => $user,
        'current_user_role' => $role,
        'current_project_id' => 0,
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
        return redirect('/team/' . $team_id . '/project');
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
        'status' => $project->status,
        'create_time' => (string)$project->create_time,
        'update_time' => (string)$project->update_time,
    ];
});

