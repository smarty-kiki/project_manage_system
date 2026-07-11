<?php

function cleanup_expired_codes($hours = 24): int
{
    $threshold = datetime('-' . $hours . ' hours');
    $all_codes = dao('verification_code')->find_all();

    $count = 0;
    foreach ($all_codes as $code) {
        if ($code->expire_time !== null && $code->expire_time < $threshold) {
            $code->force_delete();
            $count++;
        }
    }

    return $count;
}

function invalidate_user_codes($email, $type = ''): int
{
    $conditions = ['email' => $email, 'used' => 0];

    if ($type) {
        $conditions['type'] = $type;
    }

    $codes = dao('verification_code')->find_all_by_column($conditions);

    $count = 0;
    foreach ($codes as $code) {
        $code->delete();
        $count++;
    }

    return $count;
}
