<?php

class team_member extends entity
{
    public $structs = [
        'team_id' => 0,
        'user_id' => 0,
        'role' => 'member',
        'joined_time' => null,
    ];

    public static function create($team_id, $user_id, $role = 'member'): team_member
    {
        $member = parent::init();

        $member->team_id = $team_id;
        $member->user_id = $user_id;
        $member->role = $role;
        $member->joined_time = datetime();

        return $member;
    }
}
