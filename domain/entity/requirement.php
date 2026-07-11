<?php

class requirement extends entity
{
    public $structs = [
        'project_id' => 0,
        'system_id' => 0,
        'module_id' => 0,
        'name' => '',
        'description' => '',
    ];

    public static function create($project_id, $system_id, $module_id, $name, $description): requirement
    {
        $req = parent::init();

        $req->project_id = $project_id;
        $req->system_id = $system_id;
        $req->module_id = $module_id;
        $req->name = $name;
        $req->description = $description;

        return $req;
    }
}
