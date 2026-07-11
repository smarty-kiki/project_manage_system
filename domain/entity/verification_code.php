<?php

class verification_code extends entity
{
    public $structs = [
        'email' => '',
        'code' => '',
        'type' => '',
        'expire_time' => null,
        'usage_time' => null,
        'used' => 0,
    ];

    public static function create($email, $code, $type, $expire_minutes = 10): verification_code
    {
        $vc = parent::init();

        $vc->email = $email;
        $vc->code = $code;
        $vc->type = $type;
        $vc->expire_time = datetime('+' . $expire_minutes . ' minutes');
        $vc->used = 0;

        return $vc;
    }

    public function is_expired(): bool
    {
        return $this->expire_time !== null && datetime() > $this->expire_time;
    }
}
