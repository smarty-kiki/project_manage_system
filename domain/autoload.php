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
        'system_dao' => 'dao/system.php',
        'system' => 'entity/system.php',
        'module_dao' => 'dao/module.php',
        'module' => 'entity/module.php',
        'business_process_dao' => 'dao/business_process.php',
        'business_process' => 'entity/business_process.php',
        'process_node_dao' => 'dao/process_node.php',
        'process_node' => 'entity/process_node.php',
        'requirement_dao' => 'dao/requirement.php',
        'requirement' => 'entity/requirement.php',
        'bug_dao' => 'dao/bug.php',
        'bug' => 'entity/bug.php',
    ];

    if (isset($class_maps[$class_name])) {
        include __DIR__.'/'.$class_maps[$class_name];
    }
});
