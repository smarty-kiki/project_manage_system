<?php

class system extends entity
{
    public $structs = [
        'project_id' => 0,
        'name' => '',
        'description' => '',
        'git_url' => '',
    ];

    public static function create($project_id, $name, $description, $git_url): system
    {
        $system = parent::init();

        $system->project_id = $project_id;
        $system->name = $name;
        $system->description = $description;
        $system->git_url = $git_url;

        return $system;
    }
}
