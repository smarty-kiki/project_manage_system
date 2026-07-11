<?php

function get_current_team_id(): ?int
{
    $team_id = cookie('current_team_id');

    if ($team_id && !is_numeric($team_id)) {
        return null;
    }

    return $team_id ? (int)$team_id : null;
}

function get_current_team()
{
    $team_id = get_current_team_id();
    if (!$team_id) {
        return new null_entity();
    }

    $team = dao('team')->find_by_id($team_id);
    if ($team->is_null() || $team->is_deleted()) {
        setcookie('current_team_id', '', time() - 3600, '/');
        return new null_entity();
    }

    $user_id = get_current_user_id();
    $role = get_user_team_role($team_id, $user_id);
    if ($role === null) {
        setcookie('current_team_id', '', time() - 3600, '/');
        return new null_entity();
    }

    return $team;
}

function set_current_team_id(int $team_id): void
{
    setcookie('current_team_id', (string)$team_id, time() + 86400 * 30, '/');
}

function require_team_context()
{
    $redirect = require_user_name();
    if ($redirect) {
        return $redirect;
    }

    $user_id = get_current_user_id();

    if (!user_has_any_team($user_id)) {
        return redirect('/account/team/create');
    }

    $team_id = get_current_team_id();
    if (!$team_id) {
        return redirect('/account/team');
    }

    $role = get_user_team_role($team_id, $user_id);
    if ($role === null) {
        setcookie('current_team_id', '', time() - 3600, '/');
        return redirect('/account/team');
    }

    return null;
}

function get_switchable_teams(int $user_id, int $current_team_id): array
{
    $members = dao('team_member')->find_all_by_column(['user_id' => $user_id]);

    $teams = [];
    foreach ($members as $member) {
        if ($member->is_deleted()) {
            continue;
        }
        if ((int)$member->team_id === $current_team_id) {
            continue;
        }
        $team = dao('team')->find_by_id($member->team_id);
        if ($team->is_not_null() && $team->is_not_deleted()) {
            $teams[] = $team;
        }
    }

    return $teams;
}

function get_default_redirect_after_login(int $user_id): string
{
    $team_id = get_current_team_id();
    if ($team_id) {
        $role = get_user_team_role($team_id, $user_id);
        if ($role !== null) {
            return '/team/' . $team_id . '/dashboard';
        }
    }

    return '/account/team';
}

function user_has_any_team(int $user_id): bool
{
    $members = dao('team_member')->find_all_by_column(['user_id' => $user_id]);
    foreach ($members as $member) {
        if ($member->is_deleted()) {
            continue;
        }
        $team = dao('team')->find_by_id($member->team_id);
        if ($team->is_not_null() && $team->is_not_deleted()) {
            return true;
        }
    }
    return false;
}
