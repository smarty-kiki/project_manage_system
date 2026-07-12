<?php

class process_node extends entity
{
    public $structs = [
        'project_id' => 0,
        'business_process_id' => 0,
        'project_role_id' => 0,
        'name' => '',
        'description' => '',
        'sort_order' => 0,
    ];

    public static function create($business_process_id, $name, $description, $sort_order = 0, $project_id = 0, $project_role_id = 0): process_node
    {
        $node = parent::init();

        $node->project_id = $project_id;
        $node->business_process_id = $business_process_id;
        $node->project_role_id = $project_role_id;
        $node->name = $name;
        $node->description = $description;
        $node->sort_order = $sort_order;

        return $node;
    }
}
