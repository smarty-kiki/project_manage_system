<?php

// Unified entry page (login/register merged)
if_get('/account/enter', function () {
    if ($user_id = get_current_user_id()) {
        $user = dao('team_account')->find_by_id($user_id);
        if ($user->is_not_null() && !empty($user->name)) {
            if (user_has_any_team($user_id)) {
                return redirect(get_default_redirect_after_login($user_id));
            }
            return redirect('/account/team/create');
        }
        return redirect('/account/team');
    }
    return render('account/enter', [
        'title' => '进入系统',
    ]);
});

// Set name page (for users without a name)
if_get('/account/set_name', function () {
    $user_id = get_current_user_id();
    if (!$user_id) {
        return redirect('/account/enter');
    }

    $user = dao('team_account')->find_by_id($user_id);
    if ($user->is_not_null() && !empty($user->name)) {
        return redirect(get_default_redirect_after_login($user_id));
    }

    return render('account/set_name', [
        'title' => '设置姓名',
    ]);
});

// Team list page
if_get('/account/team', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user_id = get_current_user_id();
    $user = dao('team_account')->find_by_id($user_id);

    if (!user_has_any_team($user_id)) {
        return redirect('/account/team/create');
    }

    $teams = get_user_teams($user_id);

    return render('account/team_list', [
        'title' => '我的团队',
        'user' => $user,
        'user_teams' => $teams,
        'teams' => $teams,
    ]);
});

// Team create page
if_get('/account/team/create', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user = dao('team_account')->find_by_id(get_current_user_id());
    $user_teams = get_user_teams($user->id);

    return render('account/team_create', [
        'title' => '创建团队',
        'user' => $user,
        'user_teams' => $user_teams,
    ]);
});

// Team detail page
if_get('/account/team/*', function ($team_id) {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $team = dao('team')->find_by_id($team_id);
    if ($team->is_null()) {
        return render('error/404');
    }

    $members = get_team_members($team_id);
    $user_id = get_current_user_id();
    $current_user_role = get_user_team_role($team_id, $user_id);
    $user = dao('team_account')->find_by_id($user_id);
    $user_teams = get_user_teams($user_id);

    return render('account/team_detail', [
        'title' => $team->name,
        'team' => $team,
        'members' => $members,
        'current_user_role' => $current_user_role,
        'user' => $user,
        'user_teams' => $user_teams,
    ]);
});

// Team member management page
if_get('/account/team/*/member', function ($team_id) {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $team = dao('team')->find_by_id($team_id);
    if ($team->is_null()) {
        return render('error/404');
    }

    $is_creator = $team->creator_id == get_current_user_id();
    if (!$is_creator) {
        return render('account/team_detail', [
            'title' => $team->name,
            'team' => $team,
            'members' => get_team_members($team_id),
            'current_user_role' => get_user_team_role($team_id, get_current_user_id()),
            'error' => '只有团队创建者可以管理成员',
        ]);
    }

    $members = get_team_members($team_id);
    $user = dao('team_account')->find_by_id(get_current_user_id());
    $user_teams = get_user_teams($user->id);

    return render('account/team_member', [
        'title' => '成员管理 - ' . $team->name,
        'team' => $team,
        'members' => $members,
        'is_creator' => true,
        'user' => $user,
        'user_teams' => $user_teams,
    ]);
});

// Logout
if_get('/account/logout', function () {
    setcookie('user_id', '', time() - 3600, '/');
    return redirect('/account/enter');
});

// API: Send verification code (unified, no type)
if_post('/api/account/send_code', function () {
    $email = trim(input('email', ''));

    if (all_empty($email)) {
        otherwise_error_code('EMAIL_REQUIRED', false);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        otherwise_error_code('EMAIL_INVALID', false);
    }

    $recent_code = dao('verification_code')
        ->find_all_by_column(['email' => $email, 'type' => 'enter', 'used' => 0]);

    foreach ($recent_code as $code) {
        if ($code->is_expired()) {
            continue;
        }
        $remaining_seconds = (int)datetime_diff(datetime(), $code->expire_time);
        otherwise_error_code('CODE_SEND_TOO_FREQUENT', false, ['{seconds}' => (string)max(1, $remaining_seconds)]);
    }

    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $vc = verification_code::create($email, $code, 'enter');

    $subject = '登录验证码';
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

// API: Verify code (unified login/register)
if_post('/api/account/verify_code', function () {
    $email = trim(input('email', ''));
    $code = trim(input('code', ''));

    if (all_empty($email, $code)) {
        otherwise_error_code('INVALID_PARAM', false, [], ['param' => 'email and code']);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        otherwise_error_code('EMAIL_INVALID', false);
    }

    $vc = dao('verification_code')
        ->find_by_column(['email' => $email, 'type' => 'enter', 'used' => 0]);

    $invite_team_id = null;

    if ($vc->is_null() || $vc->is_expired() || $vc->code !== $code) {
        $invite_codes = dao('verification_code')
            ->find_all_by_column(['email' => $email, 'used' => 0]);

        foreach ($invite_codes as $invite_vc) {
            if (!$invite_vc->is_expired() && $invite_vc->code === $code && starts_with($invite_vc->type, 'team_invite_')) {
                $vc = $invite_vc;
                $invite_team_id = (int)str_replace('team_invite_', '', $invite_vc->type);
                break;
            }
        }
    }

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

    if ($user->is_null()) {
        $user = team_account::create($email, '');
    }

    if ($user->status != 1) {
        otherwise_error_code('PERMISSION_DENIED', false);
    }

    setcookie('user_id', (string)$user->id, time() + 86400 * 30, '/');

    if ($invite_team_id > 0) {
        $team = dao('team')->find_by_id($invite_team_id);
        if ($team->is_not_null()) {
            $existing = dao('team_member')->find_by_column([
                'team_id' => $invite_team_id,
                'user_id' => $user->id,
            ]);
            if ($existing->is_null() || $existing->is_deleted()) {
                if ($existing->is_not_null()) {
                    $existing->restore();
                } else {
                    team_member::create($invite_team_id, $user->id, 'member');
                }
            }
        }
    }

    if (empty($user->name)) {
        if (is_ajax()) {
            return ['redirect' => '/account/set_name'];
        }
        return redirect('/account/set_name');
    }

    $redirect_uri = get_default_redirect_after_login($user->id);

    if (is_ajax()) {
        return ['redirect' => $redirect_uri];
    }

    return redirect($redirect_uri);
});

// API: Set user name
if_post('/api/account/set_name', function () {
    $user_id = get_current_user_id();
    if (!$user_id) {
        otherwise_error_code('PERMISSION_DENIED', false);
    }

    $name = trim(input('name', ''));

    if (all_empty($name)) {
        otherwise_error_code('NAME_REQUIRED', false);
    }

    $user = dao('team_account')->find_by_id($user_id);
    if ($user->is_null()) {
        otherwise_error_code('USER_NOT_FOUND', false);
    }

    $user->name = $name;

    $redirect_uri = get_default_redirect_after_login($user_id);

    if (is_ajax()) {
        return ['redirect' => $redirect_uri];
    }

    return redirect($redirect_uri);
});

// API: Create team
if_post('/api/team/create', function () {
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user_id = get_current_user_id();

    $name = trim(input('name', ''));
    $description = trim(input('description', ''));

    if (all_empty($name)) {
        otherwise_error_code('TEAM_NAME_REQUIRED', false);
    }

    $team = team::create($name, $description, $user_id);

    $member = team_member::create($team->id, $user_id, 'creator');

    set_current_team_id($team->id);

    return [
        'team_id' => $team->id,
        'redirect' => '/team/' . $team->id . '/dashboard',
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
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user_id = get_current_user_id();

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
        $body .= "http://" . server('HTTP_HOST') . "/account/enter\n";
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
    $redirect = require_user_name();
    if ($redirect) return $redirect;

    $user_id = get_current_user_id();

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

    if ($member->is_not_null() && $member->is_deleted()) {
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
            'name' => $user->name,
            'avatar' => $user->avatar,
            'role' => $member->role,
            'joined_time' => (string)$member->joined_time,
        ];
    }

    return $result;
});
