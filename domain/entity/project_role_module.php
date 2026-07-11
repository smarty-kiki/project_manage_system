<?php

class project_role_module extends entity
{
    public $structs = [
        'project_role_id' => 0,
        'module_id' => 0,
    ];

    public static function create($project_role_id, $module_id): project_role_module
    {
        $link = parent::init();

        $link->project_role_id = $project_role_id;
        $link->module_id = $module_id;

        return $link;
    }
}
