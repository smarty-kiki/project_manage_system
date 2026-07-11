<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Login page
if_get('/account/login', function () {
    if (get_current_user_id()) {
        return redirect('/account/team');
    }
    return render('account/login', [
        'title' => '登录',
    ]);
});

// Register page
if_get('/account/register', function () {
    if (get_current_user_id()) {
        return redirect('/account/team');
    }
    return render('account/register', [
        'title' => '注册',
    ]);
});

// Team list page
if_get('/account/team', function () {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return redirect('/account/login');
    }

    $user = dao('team_account')->find_by_id($user_id);
    $teams = get_user_teams($user_id);

    return render('account/team_list', [
        'title' => '我的团队',
        'user' => $user,
        'teams' => $teams,
    ]);
});

// Team create page
if_get('/account/team/create', function () {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return redirect('/account/login');
    }
    return render('account/team_create', [
        'title' => '创建团队',
    ]);
});

// Team detail page
if_get('/account/team/*', function ($team_id) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return redirect('/account/login');
    }

    $team = dao('team')->find_by_id($team_id);
    if ($team->is_null()) {
        return render('error/404');
    }

    $members = get_team_members($team_id);
    $current_user_role = get_user_team_role($team_id, $user_id);

    return render('account/team_detail', [
        'title' => $team->name,
        'team' => $team,
        'members' => $members,
        'current_user_role' => $current_user_role,
    ]);
});

// Team member management page
if_get('/account/team/*/member', function ($team_id) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return redirect('/account/login');
    }

    $team = dao('team')->find_by_id($team_id);
    if ($team->is_null()) {
        return render('error/404');
    }

    $is_creator = $team->creator_id == $user_id;
    if (!$is_creator) {
        return render('account/team_detail', [
            'title' => $team->name,
            'team' => $team,
            'members' => get_team_members($team_id),
            'current_user_role' => get_user_team_role($team_id, $user_id),
            'error' => '只有团队创建者可以管理成员',
        ]);
    }

    $members = get_team_members($team_id);

    return render('account/team_member', [
        'title' => '成员管理 - ' . $team->name,
        'team' => $team,
        'members' => $members,
        'is_creator' => true,
    ]);
});

// Logout
if_get('/account/logout', function () {
    $_SESSION['user_id'] = null;
    session_destroy();
    return redirect('/account/login');
});
