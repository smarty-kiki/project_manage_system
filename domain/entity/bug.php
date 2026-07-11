<?php

class bug extends entity
{
    public $structs = [
        'project_id' => 0,
        'requirement_id' => 0,
        'role_id' => 0,
        'name' => '',
        'description' => '',
    ];

    public static function create($project_id, $requirement_id, $name, $description): bug
    {
        $b = parent::init();

        $b->project_id = $project_id;
        $b->requirement_id = $requirement_id;
        $b->role_id = 0;
        $b->name = $name;
        $b->description = $description;

        return $b;
    }
}
