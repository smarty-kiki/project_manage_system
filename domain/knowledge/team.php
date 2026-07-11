<?php

function create_team_with_creator($name, $description, $creator_id): team
{
    $team = team::create($name, $description, $creator_id);
    $team->save();

    $member = team_member::create($team->id, $creator_id, 'creator');
    $member->save();

    return $team;
}

function get_team_info($team_id): ?array
{
    $team = dao('team')->find_by_id($team_id);

    if ($team->is_null() || $team->is_deleted()) {
        return null;
    }

    $creator = dao('team_account')->find_by_id($team->creator_id);

    return [
        'team' => $team,
        'creator' => $creator->is_not_null() ? $creator : null,
        'member_count' => count(get_team_members($team_id)),
    ];
}
