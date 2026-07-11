<?php

class team extends entity
{
    public $structs = [
        'name' => '',
        'description' => '',
        'creator_id' => 0,
        'status' => 1,
    ];

    public static function create($name, $description, $creator_id): team
    {
        $team = parent::init();

        $team->name = $name;
        $team->description = $description;
        $team->creator_id = $creator_id;
        $team->status = 1;

        return $team;
    }
}
