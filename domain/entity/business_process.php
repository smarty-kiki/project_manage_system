<?php

class business_process extends entity
{
    public $structs = [
        'project_id' => 0,
        'name' => '',
        'description' => '',
        'initiator_role_id' => 0,
    ];

    public static function create($project_id, $name, $description, $initiator_role_id = 0): business_process
    {
        $bp = parent::init();

        $bp->project_id = $project_id;
        $bp->name = $name;
        $bp->description = $description;
        $bp->initiator_role_id = $initiator_role_id;

        return $bp;
    }
}
