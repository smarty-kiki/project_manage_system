<?php

return [

    'resources' => [

        'local' => [
            'database' => 'default_prod',
            'username' => 'prod_user',
            'password' => 'prod_password',

            'read' => [
                '127.0.0.1' => 3306,
            ],
            'write' => [
                '127.0.0.1' => 3306,
            ],
            'schema' => [
                '127.0.0.1' => 3306,
            ],

            'options' => [
                PDO::ATTR_CASE => PDO::CASE_NATURAL,
                PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
            ],
        ],
    ],
];
