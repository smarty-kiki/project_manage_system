<?php

class process_node extends entity
{
    public $structs = [
        'business_process_id' => 0,
        'name' => '',
        'description' => '',
        'sort_order' => 0,
    ];

    public static function create($business_process_id, $name, $description, $sort_order = 0): process_node
    {
        $node = parent::init();

        $node->business_process_id = $business_process_id;
        $node->name = $name;
        $node->description = $description;
        $node->sort_order = $sort_order;

        return $node;
    }
}
