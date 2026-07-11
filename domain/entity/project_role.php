<?php

class project_role extends entity
{
    public $structs = [
        'project_id' => 0,
        'name' => '',
        'description' => '',
        'process_node_id' => 0,
    ];

    public static function create($project_id, $name, $description, $process_node_id = 0): project_role
    {
        $role = parent::init();

        $role->project_id = $project_id;
        $role->name = $name;
        $role->description = $description;
        $role->process_node_id = $process_node_id;

        return $role;
    }
}
