@php
$secondary_items = [
    ['label' => '总览', 'href' => '/team/' . $team->id . '/dashboard', 'icon' => '&#9633;', 'active' => true],
    ['label' => '团队成员', 'href' => '/team/' . $team->id . '/member', 'icon' => '&#9783;'],
];
@endphp
@include('layout/app')

<div class="page-top-bar">
    <div>
        <h2>{{ $team->name }}</h2>
        <p style="color:#999;margin-top:4px;">{{ $team->description or '暂无描述' }}</p>
    </div>
    @if ($current_user_role === 'admin')
    <a href="/team/{{ $team->id }}/member" class="btn btn-primary">邀请成员</a>
    @endif
</div>

<div class="flex gap-16" style="flex-wrap:wrap;">
    <div class="card flex-1" style="min-width:200px;">
        <div class="card-body text-center">
            <div style="font-size:32px;font-weight:700;color:#1890ff;">{{ count($members) }}</div>
            <div style="color:#999;margin-top:4px;">团队成员</div>
        </div>
    </div>
    <div class="card flex-1" style="min-width:200px;">
        <div class="card-body text-center">
            <div style="font-size:32px;font-weight:700;color:#52c41a;">{{ $admin_count }}</div>
            <div style="color:#999;margin-top:4px;">管理员</div>
        </div>
    </div>
    <div class="card flex-1" style="min-width:200px;">
        <div class="card-body text-center">
            <div style="font-size:32px;font-weight:700;color:#fa8c16;">{{ substr($team->create_time, 0, 10) }}</div>
            <div style="color:#999;margin-top:4px;">创建日期</div>
        </div>
    </div>
</div>

<div class="card mt-16">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span>项目</span>
        @if ($current_user_role === 'admin')
        <a href="/team/{{ $team->id }}/project" class="btn btn-primary btn-sm">+ 新建项目</a>
        @endif
    </div>
    <div class="card-body">
        @if (empty($projects))
        <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="1.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
            <p>暂无项目，敬请期待</p>
        </div>
        @else
        <table class="member-table">
            <tr><th>项目名称</th><th>描述</th><th>创建时间</th></tr>
            @foreach ($projects as $p)
            <tr>
                <td><a href="/team/{{ $team->id }}/project/{{ $p->id }}" style="color:#1890ff;">{{ $p->name }}</a></td>
                <td style="color:#666;">{{ $p->description or '-' }}</td>
                <td style="color:#999;font-size:13px;">{{ $p->create_time }}</td>
            </tr>
            @endforeach
        </table>
        @endif
    </div>
</div>

<div class="card mt-16">
    <div class="card-header flex-between">
        <span>团队成员</span>
        <a href="/team/{{ $team->id }}/member" style="font-size:13px;">查看全部 &rarr;</a>
    </div>
    <div class="card-body" style="padding:0;">
        <table class="member-table">
            <thead>
                <tr>
                    <th>姓名</th>
                    <th>邮箱</th>
                    <th>角色</th>
                    <th>加入时间</th>
                </tr>
            </thead>
            <tbody>
                @if (!empty($members) && count($members) > 0)
                    @foreach ($members as $member)
                        @php $m_user = dao('team_account')->find_by_id($member->user_id); @endphp
                        <tr>
                            <td>
                                <strong>{{ $m_user->name or '--' }}</strong>
                            </td>
                            <td>{{ $m_user->email }}</td>
                            <td>
                                <span class="role-badge {{ $member->role === 'admin' ? 'role-admin' : 'role-member' }}">
                                    {{ $member->role === 'admin' ? '管理员' : '成员' }}
                                </span>
                            </td>
                            <td>{{ $member->joined_time ? substr($member->joined_time, 0, 10) : '--' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4">
                            <div class="empty-state" style="padding:32px 20px;">暂无成员</div>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@include('layout/app_footer')
