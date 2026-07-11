<?php

// API: Send verification code
if_post('/api/account/send_code', function () {
    $email = trim(input('email', ''));

    if (all_empty($email)) {
        otherwise_error_code('EMAIL_REQUIRED', false);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        otherwise_error_code('EMAIL_INVALID', false);
    }

    $existing = dao('team_account')->find_by_column(['email' => $email]);
    $type = input('type', 'login');

    if ($type === 'register' && $existing->is_not_null()) {
        otherwise_error_code('EMAIL_ALREADY_REGISTERED', false);
    }

    if ($type === 'login' && $existing->is_null()) {
        otherwise_error_code('EMAIL_NOT_REGISTERED', false);
    }

    $recent_code = dao('verification_code')
        ->find_all_by_column(['email' => $email, 'type' => $type, 'used' => 0]);

    foreach ($recent_code as $code) {
        if (!$code->is_expired()) {
            $remaining = datetime_diff(datetime(), $code->expire_time);
            $remaining_seconds = max(0, 60 - (int)$remaining);
            otherwise_error_code('CODE_SEND_TOO_FREQUENT', false, ['{seconds}' => (string)$remaining_seconds]);
        }
    }

    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $vc = verification_code::create($email, $code, $type);

    $subject = $type === 'register' ? '注册验证码' : '登录验证码';
    $body = "您的验证码是：{$code}\n验证码10分钟内有效，请勿转发他人。\n\n如果您未请求此验证码，请忽略此邮件。";

    $sent = send_email($email, $subject, $body);

    if (!$sent) {
        log_notice('verification_code_email_failed', 'email send failed for: ' . $email . ', code: ' . $code);
    }

    return [
        'sent' => $sent,
        'message' => '验证码已发送至 ' . $email,
    ];
});

// API: Verify code (login or register)
if_post('/api/account/verify_code', function () {
    $email = trim(input('email', ''));
    $code = trim(input('code', ''));
    $type = input('type', 'login');
    $nickname = trim(input('nickname', ''));

    if (all_empty($email, $code)) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'email and code']);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        otherwise_error_code('EMAIL_INVALID', false);
    }

    $vc = dao('verification_code')
        ->find_by_column(['email' => $email, 'type' => $type, 'used' => 0]);

    if ($vc->is_null() || $vc->is_expired()) {
        otherwise_error_code('VERIFICATION_CODE_INVALID', false);
    }

    if ($vc->code !== $code) {
        log_notice('verification_code_mismatch', 'code mismatch for: ' . $email);
        otherwise_error_code('VERIFICATION_CODE_MISMATCH', false);
    }

    $vc->usage_time = datetime();
    $vc->used = 1;

    $user = dao('team_account')->find_by_column(['email' => $email]);

    if ($type === 'register') {
        if ($user->is_null()) {
            $user = team_account::create($email, $nickname);
        }
    }

    if ($user->is_null()) {
        otherwise_error_code('EMAIL_NOT_REGISTERED', false);
    }

    if ($user->status != 1) {
        otherwise_error_code('PERMISSION_DENIED', false);
    }

    setcookie('user_id', (string)$user->id, time() + 86400 * 30, '/');

    return redirect('/account/team');
});

// API: Create team
if_post('/api/team/create', function () {
    $user_id = get_current_user_id();
    if (!$user_id) {
        otherwise_error_code('PERMISSION_DENIED', false);
    }

    $name = trim(input('name', ''));
    $description = trim(input('description', ''));

    if (all_empty($name)) {
        otherwise_error_code('TEAM_NAME_REQUIRED', false);
    }

    $team = team::create($name, $description, $user_id);

    $member = team_member::create($team->id, $user_id, 'creator');

    return [
        'team_id' => $team->id,
        'redirect' => '/account/team/' . $team->id,
    ];
});

// API: List user teams
if_get('/api/team/list', function () {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return [];
    }

    $teams = get_user_teams($user_id);

    $result = [];
    foreach ($teams as $team) {
        $member = dao('team_member')->find_by_column(['team_id' => $team->id, 'user_id' => $user_id]);
        $result[] = [
            'id' => $team->id,
            'name' => $team->name,
            'description' => $team->description,
            'role' => $member->is_not_null() ? $member->role : 'member',
            'create_time' => (string)$team->create_time,
        ];
    }

    return $result;
});

// API: Invite team member
if_post('/api/team/invite', function () {
    $user_id = get_current_user_id();
    if (!$user_id) {
        otherwise_error_code('PERMISSION_DENIED', false);
    }

    $team_id = input('team_id', '');
    $email = trim(input('email', ''));

    if (all_empty($team_id, $email)) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'team_id and email']);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        otherwise_error_code('EMAIL_INVALID', false);
    }

    $team = dao('team')->find_by_id($team_id);
    if ($team->is_null()) {
        otherwise_error_code('TEAM_NOT_FOUND', false);
    }

    if ($team->creator_id != $user_id) {
        otherwise_error_code('NOT_TEAM_CREATOR', false);
    }

    $invite_user = dao('team_account')->find_by_column(['email' => $email]);

    if ($invite_user->is_not_null()) {
        $existing_member = dao('team_member')->find_by_column([
            'team_id' => $team_id,
            'user_id' => $invite_user->id,
        ]);
        if ($existing_member->is_not_null() && !$existing_member->is_deleted()) {
            otherwise_error_code('TEAM_MEMBER_ALREADY_EXISTS', false);
        }

        if ($existing_member->is_not_null() && $existing_member->is_deleted()) {
            $existing_member->restore();
            $existing_member->role = 'member';
        } else {
            $member = team_member::create($team_id, $invite_user->id, 'member');
        }
    } else {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $vc = verification_code::create($email, $code, 'team_invite_' . $team_id, 72 * 60);

        $team = dao('team')->find_by_id($team_id);
        $subject = '邀请您加入团队：' . $team->name;
        $body = "您被邀请加入团队「{$team->name}」。\n\n";
        $body .= "请访问以下链接加入团队：\n";
        $body .= "http://" . server('HTTP_HOST') . "/account/register\n";
        $body .= "注册时使用验证码：{$code}\n\n";
        $body .= "如果您未收到邀请，请忽略此邮件。";

        send_email($email, $subject, $body);
    }

    return [
        'message' => '邀请已发送至 ' . $email,
    ];
});

// API: Remove team member
if_post('/api/team/member/remove', function () {
    $user_id = get_current_user_id();
    if (!$user_id) {
        otherwise_error_code('PERMISSION_DENIED', false);
    }

    $team_id = input('team_id', '');
    $member_user_id = input('user_id', '');

    if (all_empty($team_id, $member_user_id)) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'team_id and user_id']);
    }

    $team = dao('team')->find_by_id($team_id);
    if ($team->is_null()) {
        otherwise_error_code('TEAM_NOT_FOUND', false);
    }

    if ($team->creator_id != $user_id) {
        otherwise_error_code('NOT_TEAM_CREATOR', false);
    }

    if ((int)$member_user_id === (int)$user_id) {
        otherwise_error_code('INVALID_REQUEST', false);
    }

    $member = dao('team_member')->find_by_column([
        'team_id' => $team_id,
        'user_id' => $member_user_id,
    ]);

    if ($member->is_not_null() && $member->is_not_deleted()) {
        $member->delete();
    }

    return ['message' => '成员已移除'];
});

// API: Get team members
if_get('/api/team/*/member', function ($team_id) {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return [];
    }

    $members = get_team_members($team_id);

    $result = [];
    foreach ($members as $member) {
        $user = dao('team_account')->find_by_id($member->user_id);
        $result[] = [
            'id' => $user->id,
            'email' => $user->email,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'role' => $member->role,
            'joined_time' => (string)$member->joined_time,
        ];
    }

    return $result;
});
