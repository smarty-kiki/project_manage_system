<?php

class business_process extends entity
{
    public $structs = [
        'project_id' => 0,
        'name' => '',
        'description' => '',
    ];

    public static function create($project_id, $name, $description): business_process
    {
        $bp = parent::init();

        $bp->project_id = $project_id;
        $bp->name = $name;
        $bp->description = $description;

        return $bp;
    }
}
