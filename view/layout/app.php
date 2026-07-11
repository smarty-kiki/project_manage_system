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

        /* Layout */
        .app-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 200px; background: #001529; color: #fff; flex-shrink: 0; }
        .sidebar-header { height: 56px; display: flex; align-items: center; padding: 0 20px; font-size: 16px; font-weight: 600; border-bottom: 1px solid rgba(255,255,255,.1); }
        .sidebar-nav { padding: 8px 0; }
        .sidebar-item { display: flex; align-items: center; padding: 10px 20px; color: rgba(255,255,255,.65); cursor: pointer; }
        .sidebar-item:hover, .sidebar-item.active { color: #fff; background: #1890ff; }
        .sidebar-item .icon { margin-right: 10px; font-size: 16px; }
        .main-content { flex: 1; padding: 20px; overflow: auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; padding: 0 0 16px; }
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

        /* Auth pages */
        .auth-page { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .auth-card { background: #fff; border-radius: 8px; box-shadow: 0 8px 32px rgba(0,0,0,.15); padding: 40px; width: 100%; max-width: 420px; }
        .auth-card h2 { text-align: center; margin-bottom: 24px; color: #1890ff; }
        .auth-footer { text-align: center; margin-top: 20px; color: #999; }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar { width: 0; overflow: hidden; }
            .sidebar.open { width: 200px; }
            .team-grid { grid-template-columns: 1fr; }
            .auth-card { margin: 20px; padding: 24px; }
        }
    </style>
</head>
<body>
