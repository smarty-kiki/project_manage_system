<?php

function get_current_user_id(): ?int
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user_id = $_SESSION['user_id'] ?? null;

    if ($user_id && !is_numeric($user_id)) {
        return null;
    }

    return $user_id ? (int)$user_id : null;
}

function get_user_teams($user_id): array
{
    $members = dao('team_member')->find_all_by_column(['user_id' => $user_id]);

    $teams = [];
    foreach ($members as $member) {
        if ($member->is_deleted()) {
            continue;
        }
        $team = dao('team')->find_by_id($member->team_id);
        if ($team->is_not_null() && $team->is_not_deleted()) {
            $teams[] = $team;
        }
    }

    return $teams;
}

function get_team_members($team_id): array
{
    $members = dao('team_member')->find_all_by_column(['team_id' => $team_id]);

    $result = [];
    foreach ($members as $member) {
        if ($member->is_deleted()) {
            continue;
        }
        $result[] = $member;
    }

    return $result;
}

function get_user_team_role($team_id, $user_id): ?string
{
    $member = dao('team_member')->find_by_column([
        'team_id' => $team_id,
        'user_id' => $user_id,
    ]);

    if ($member->is_null() || $member->is_deleted()) {
        return null;
    }

    return $member->role;
}
