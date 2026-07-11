<?php

// Team dashboard
if_get('/team/*/dashboard', function ($team_id) {
    $user_id = get_current_user_id();

    $role = get_user_team_role($team_id, $user_id);
    if ($role === null) {
        return redirect('/account/team');
    }

    set_current_team_id($team_id);

    $team = dao('team')->find_by_id($team_id);
    if ($team->is_null() || $team->is_deleted()) {
        setcookie('current_team_id', '', time() - 3600, '/');
        return redirect('/account/team');
    }

    $user = dao('team_account')->find_by_id($user_id);
    $members = get_team_members($team_id);
    $switchable_teams = get_switchable_teams($user_id, (int)$team_id);
    $user_teams = get_user_teams($user_id);
    $creator = dao('team_account')->find_by_id($team->creator_id);

    return render('team/dashboard', [
        'title' => $team->name . ' - 工作台',
        'team' => $team,
        'user' => $user,
        'members' => $members,
        'current_user_role' => $role,
        'current_team' => $team,
        'switchable_teams' => $switchable_teams,
        'user_teams' => $user_teams,
        'creator' => $creator,
    ]);
});

// Switch current team
if_post('/api/team/switch', function () {
    $user_id = get_current_user_id();
    if (!$user_id) {
        otherwise_error_code('PERMISSION_DENIED', false);
    }

    $team_id = input('team_id', '');
    if (all_empty($team_id)) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'team_id']);
    }

    $role = get_user_team_role((int)$team_id, $user_id);
    if ($role === null) {
        otherwise_error_code('PERMISSION_DENIED', false);
    }

    set_current_team_id((int)$team_id);

    return ['redirect' => '/team/' . $team_id . '/dashboard'];
});
