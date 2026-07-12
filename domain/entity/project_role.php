<?php

class project_role extends entity
{
    public $structs = [
        'project_id' => 0,
        'name' => '',
        'description' => '',
    ];

    public static function create($project_id, $name, $description): project_role
    {
        $role = parent::init();

        $role->project_id = $project_id;
        $role->name = $name;
        $role->description = $description;

        return $role;
    }
}
