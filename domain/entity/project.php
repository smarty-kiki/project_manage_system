<?php

class project extends entity
{
    public $structs = [
        'team_id' => 0,
        'name' => '',
        'description' => '',
        'creator_id' => 0,
        'status' => 1,
    ];

    public static function create($team_id, $name, $description, $creator_id): project
    {
        $project = parent::init();

        $project->team_id = $team_id;
        $project->name = $name;
        $project->description = $description;
        $project->creator_id = $creator_id;
        $project->status = 1;

        return $project;
    }
}
