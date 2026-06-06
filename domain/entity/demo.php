<?php

class demo extends entity
{
    public $structs = [
        'name' => '',
        'note' => '',
    ];

    public static function create($name): demo
    {
        $demo = parent::init();

        $demo->name = $name;

        return $demo;
    }
}
