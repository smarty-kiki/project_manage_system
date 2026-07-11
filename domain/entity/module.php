<?php

class module extends entity
{
    public $structs = [
        'project_id' => 0,
        'system_id' => 0,
        'name' => '',
        'description' => '',
    ];

    public static function create($project_id, $system_id, $name, $description): module
    {
        $mod = parent::init();

        $mod->project_id = $project_id;
        $mod->system_id = $system_id;
        $mod->name = $name;
        $mod->description = $description;

        return $mod;
    }
}
