<?php

class team_account extends entity
{
    public $structs = [
        'email' => '',
        'password_hash' => '',
        'nickname' => '',
        'avatar' => '',
        'phone' => '',
        'status' => 1,
        'role' => 'user',
    ];

    public static function create($email, $nickname = ''): team_account
    {
        $account = parent::init();

        $account->email = $email;
        $account->nickname = $nickname ?: explode('@', $email)[0];
        $account->status = 1;
        $account->role = 'user';

        return $account;
    }
}
