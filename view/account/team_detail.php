@php
$secondary_items = [
    ['label' => '我的团队', 'href' => '/account/team', 'icon' => '&#9776;'],
    ['label' => '创建团队', 'href' => '/account/team/create', 'icon' => '&#43;'],
];
@endphp
@include('layout/app')

<div class="page-top-bar">
    <div>
        <h2>{{ $team->name }}</h2>
        <p style="color:#999;margin-top:4px">{{ $team->description or '暂无描述' }}</p>
    </div>
    <div class="flex gap-8">
        @if (!empty($current_user_role) && $current_user_role === 'creator')
            <a href="/team/{{ $team->id }}/member" class="btn btn-default btn-sm">成员管理</a>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">团队成员 ({{ count($members) }})</div>
    <div class="card-body">
        @if (!empty($members) && count($members) > 0)
            <table class="member-table">
                <thead>
                    <tr>
                        <th>成员</th>
                        <th>邮箱</th>
                        <th>角色</th>
                        <th>加入时间</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        @php $user = dao('team_account')->find_by_id($member->user_id); @endphp
                        <tr>
                            <td>{{ $user->name or $user->email }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($member->role === 'admin')
                                    <span class="role-badge role-admin">管理员</span>
                                @else
                                    <span class="role-badge role-member">成员</span>
                                @endif
                            </td>
                            <td>{{ $member->joined_time }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="empty-state">
                <p>暂无成员</p>
            </div>
        @endif
    </div>
</div>

@include('layout/app_footer')

