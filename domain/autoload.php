<?php

spl_autoload_register(function ($class_name) {

    $class_maps = [
        'demo_dao' => 'dao/demo.php',
        'demo' => 'entity/demo.php',
        'team_account_dao' => 'dao/team_account.php',
        'team_account' => 'entity/team_account.php',
        'verification_code_dao' => 'dao/verification_code.php',
        'verification_code' => 'entity/verification_code.php',
        'team_dao' => 'dao/team.php',
        'team' => 'entity/team.php',
        'team_member_dao' => 'dao/team_member.php',
        'team_member' => 'entity/team_member.php',
        'project_dao' => 'dao/project.php',
        'project' => 'entity/project.php',
    ];

    if (isset($class_maps[$class_name])) {
        include __DIR__.'/'.$class_maps[$class_name];
    }
});
