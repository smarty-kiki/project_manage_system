<?php

class team_account extends entity
{
    public $structs = [
        'email' => '',
        'password_hash' => '',
        'name' => '',
        'avatar' => '',
        'phone' => '',
        'status' => 1,
        'role' => 'user',
    ];

    public static function create($email, $name = ''): team_account
    {
        $account = parent::init();

        $account->email = $email;
        $account->name = $name;
        $account->status = 1;
        $account->role = 'user';

        return $account;
    }
}
