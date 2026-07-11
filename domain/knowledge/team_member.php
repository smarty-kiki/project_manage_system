<?php

function add_team_member($team_id, $user_id, $role = 'member'): team_member
{
    $existing = dao('team_member')->find_by_column([
        'team_id' => $team_id,
        'user_id' => $user_id,
    ]);

    if ($existing->is_not_null()) {
        if ($existing->is_deleted()) {
            $existing->restore();
            $existing->role = $role;
            $existing->save();
            return $existing;
        }
        return $existing;
    }

    $member = team_member::create($team_id, $user_id, $role);
    $member->save();

    return $member;
}

function remove_team_member($team_id, $user_id): bool
{
    $member = dao('team_member')->find_by_column([
        'team_id' => $team_id,
        'user_id' => $user_id,
    ]);

    if ($member->is_null() || $member->is_deleted()) {
        return false;
    }

    $member->delete();
    return true;
}

function is_team_creator($team_id, $user_id): bool
{
    $team = dao('team')->find_by_id($team_id);

    if ($team->is_null()) {
        return false;
    }

    return $team->creator_id == $user_id;
}
