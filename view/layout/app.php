<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title or '项目管理' }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: #333; background: #f0f2f5; }
        a { color: #1890ff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .btn { display: inline-block; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-align: center; }
        .btn-primary { background: #1890ff; color: #fff; }
        .btn-primary:hover { background: #40a9ff; }
        .btn-primary:disabled { background: #bae7ff; cursor: not-allowed; }
        .btn-danger { background: #ff4d4f; color: #fff; }
        .btn-danger:hover { background: #ff7875; }
        .btn-default { background: #fff; color: #333; border: 1px solid #d9d9d9; }
        .btn-default:hover { color: #1890ff; border-color: #1890ff; }
        .btn-sm { padding: 4px 12px; font-size: 12px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; color: #666; }
        .form-control { width: 100%; padding: 8px 12px; border: 1px solid #d9d9d9; border-radius: 4px; font-size: 14px; outline: none; }
        .form-control:focus { border-color: #1890ff; box-shadow: 0 0 0 2px rgba(24,144,255,.2); }
        textarea.form-control { min-height: 80px; resize: vertical; }
        .alert { padding: 10px 16px; border-radius: 4px; margin-bottom: 16px; }
        .alert-error { background: #fff2f0; color: #ff4d4f; border: 1px solid #ffccc7; }
        .alert-success { background: #f6ffed; color: #52c41a; border: 1px solid #b7eb8f; }
        .alert-info { background: #e6f7ff; color: #1890ff; border: 1px solid #91d5ff; }
        .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .card-header { padding: 16px 20px; border-bottom: 1px solid #f0f0f0; font-weight: 600; font-size: 16px; }
        .card-body { padding: 20px; }
        .empty-state { text-align: center; padding: 48px 20px; color: #999; }
        .empty-state p { margin-top: 12px; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .mt-16 { margin-top: 16px; }
        .mb-16 { margin-bottom: 16px; }
        .flex { display: flex; }
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .flex-1 { flex: 1; }
        .gap-8 { gap: 8px; }
        .gap-12 { gap: 12px; }
        .gap-16 { gap: 16px; }

        /* Top navbar */
        .top-navbar { height: 48px; background: #001529; display: flex; align-items: center; padding: 0 20px; position: sticky; top: 0; z-index: 100; }
        .navbar-logo { font-size: 16px; font-weight: 700; color: #fff; margin-right: 32px; }
        .navbar-primary { display: flex; gap: 4px; flex: 1; }
        .navbar-primary a { color: rgba(255,255,255,.65); padding: 8px 16px; border-radius: 4px; font-size: 14px; }
        .navbar-primary a:hover, .navbar-primary a.active { color: #fff; background: #1890ff; }
        .navbar-user { display: flex; align-items: center; gap: 16px; color: rgba(255,255,255,.65); font-size: 14px; }
        .navbar-user .user-name { color: #fff; }
        .navbar-user a { color: rgba(255,255,255,.65); }
        .navbar-user a:hover { color: #fff; }

        /* Layout */
        .app-layout { display: flex; min-height: calc(100vh - 48px); }
        .sidebar { width: 180px; background: #fff; border-right: 1px solid #f0f0f0; flex-shrink: 0; padding: 12px 0; }
        .sidebar-item { display: flex; align-items: center; padding: 10px 20px; color: #666; font-size: 14px; }
        .sidebar-item:hover { color: #1890ff; background: #f5f7fa; }
        .sidebar-item.active { color: #1890ff; background: #e6f7ff; font-weight: 500; }
        .sidebar-item .icon { margin-right: 10px; font-size: 15px; width: 18px; text-align: center; }
        .main-content { flex: 1; padding: 20px; overflow: auto; }
        .page-top-bar { display: flex; justify-content: space-between; align-items: center; padding: 0 0 16px; }
        .user-info { display: flex; align-items: center; gap: 12px; }

        /* Team cards */
        .team-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
        .team-card { background: #fff; border-radius: 8px; padding: 20px; cursor: pointer; border: 1px solid #f0f0f0; transition: box-shadow .2s; }
        .team-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.1); }
        .team-card h3 { margin-bottom: 8px; color: #1890ff; }
        .team-card p { color: #999; font-size: 13px; }
        .team-card .meta { margin-top: 12px; padding-top: 12px; border-top: 1px solid #f5f5f5; font-size: 12px; color: #999; }

        /* Member list */
        .member-table { width: 100%; border-collapse: collapse; }
        .member-table th, .member-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f5f5f5; }
        .member-table th { background: #fafafa; font-weight: 600; color: #666; }
        .role-badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 12px; }
        .role-creator { background: #fff7e6; color: #fa8c16; }
        .role-member { background: #f6ffed; color: #52c41a; }

        /* Team switcher */
        .navbar-team-switcher { position: relative; margin-right: 8px; }
        .current-team-name { color: #fff; cursor: pointer; padding: 4px 10px; border-radius: 4px; font-size: 13px; background: rgba(255,255,255,.1); display: inline-block; user-select: none; }
        .current-team-name:hover { background: rgba(255,255,255,.2); }
        .team-dropdown { position: absolute; top: 34px; left: 50%; transform: translateX(-50%); background: #fff; border-radius: 6px; box-shadow: 0 4px 16px rgba(0,0,0,.15); min-width: 160px; z-index: 200; padding: 4px 0; }
        .team-dropdown a { display: block; padding: 8px 16px; color: #333; font-size: 13px; white-space: nowrap; }
        .team-dropdown a:hover { background: #f5f7fa; color: #1890ff; text-decoration: none; }
        .team-dropdown-divider { height: 1px; background: #f0f0f0; margin: 4px 0; }

        /* Auth pages */
        .auth-page { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .auth-card { background: #fff; border-radius: 8px; box-shadow: 0 8px 32px rgba(0,0,0,.15); padding: 40px; width: 100%; max-width: 420px; }
        .auth-card h2 { text-align: center; margin-bottom: 24px; color: #1890ff; }
        .auth-footer { text-align: center; margin-top: 20px; color: #999; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { width: 0; overflow: hidden; }
            .sidebar.open { width: 180px; }
            .team-grid { grid-template-columns: 1fr; }
            .auth-card { margin: 20px; padding: 24px; }
            .navbar-primary { display: none; }
        }
    </style>
</head>
@if (isset($is_auth) && $is_auth)
<body class="auth-page">
@else
<body>
<nav class="top-navbar">
    <a href="/" class="navbar-logo">PMS</a>
    <div class="navbar-primary">
        <a href="/account/team" class="{{ strpos(server('REQUEST_URI'), '/account/team') === 0 ? 'active' : '' }}">团队</a>
        @if (isset($current_team) && isset($current_team->id))
        <a href="/team/{{ $current_team->id }}/project" class="{{ strpos(server('REQUEST_URI'), '/project') === 0 ? 'active' : '' }}">项目</a>
        @else
        <a href="/project" class="{{ strpos(server('REQUEST_URI'), '/project') === 0 ? 'active' : '' }}">项目</a>
        @endif
    </div>
    <div class="navbar-user" style="margin-left:auto;">
        @if (isset($current_team) && isset($current_team->id))
        <div class="navbar-team-switcher" id="teamSwitcher">
            <span class="current-team-name" onclick="toggleTeamDropdown()">{{ $current_team->name }} &#9662;</span>
            @if (!empty($switchable_teams) && count($switchable_teams) > 0)
            <div class="team-dropdown" id="teamDropdown" style="display:none;">
                @foreach ($switchable_teams as $t)
                <a href="#" onclick="switchTeam({{ $t->id }}); return false;">{{ $t->name }}</a>
                @endforeach
                <div class="team-dropdown-divider"></div>
                <a href="/account/team">查看所有团队</a>
            </div>
            @endif
        </div>
        @endif
        <span class="user-name">{{ $user->name or '用户' }}</span>
        <a href="/account/logout">退出</a>
    </div>
</nav>

<div class="app-layout">
    <aside class="sidebar">
        @if ($secondary_items or false)
            @foreach ($secondary_items as $item)
                <a href="{{ $item['href'] }}" class="sidebar-item {{ (isset($item['active']) and $item['active']) ? 'active' : '' }}">
                    <span class="icon">{{ $item['icon'] or '&#9679;' }}</span>
                    {{ $item['label'] }}
                </a>
            @endforeach
        @else
            <div class="sidebar-item" style="color:#999;cursor:default">暂无菜单</div>
        @endif
    </aside>

    <main class="main-content">
@endif

