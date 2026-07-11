@include('layout/app')

<div class="app-layout">
@include('account/sidebar')

<div class="main-content">
    <div class="top-bar">
        <div>
            <h2>{{ $team->name }} - 成员管理</h2>
            <p style="color:#999;margin-top:4px">{{ $team->description or '暂无描述' }}</p>
        </div>
        <div class="flex gap-8">
            <button class="btn btn-primary btn-sm" id="inviteBtn">邀请成员</button>
            <a href="/account/team/{{ $team->id }}" class="btn btn-default btn-sm">返回团队</a>
        </div>
    </div>

    @if (!empty($error))
        <div class="alert alert-error">{{ $error }}</div>
    @endif

    <div class="card">
        <div class="card-header">团队成员 ({{ count($members) }})</div>
        <div class="card-body">
            <table class="member-table">
                <thead>
                    <tr>
                        <th>成员</th>
                        <th>邮箱</th>
                        <th>角色</th>
                        <th>加入时间</th>
                        @if ($is_creator)
                            <th>操作</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($members as $member)
                        @php $user = dao('team_account')->find_by_id($member->user_id); @endphp
                        <tr>
                            <td>{{ $user->name or $user->email }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if ($member->role === 'creator')
                                    <span class="role-badge role-creator">创建者</span>
                                @else
                                    <span class="role-badge role-member">成员</span>
                                @endif
                            </td>
                            <td>{{ $member->joined_time }}</td>
                            @if ($is_creator && $member->role !== 'creator')
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="removeMember({{ $member->user_id }})">移除</button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<div id="inviteModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:1000;justify-content:center;align-items:center;">
    <div class="card" style="width:100%;max-width:420px;margin:20px;">
        <div class="card-header">邀请成员</div>
        <div class="card-body">
            <div class="form-group">
                <label>输入对方邮箱地址</label>
                <input type="email" id="inviteEmail" class="form-control" placeholder="请输入邮箱">
            </div>
            <div class="flex gap-8" style="justify-content:flex-end">
                <button class="btn btn-default" onclick="closeInviteModal()">取消</button>
                <button class="btn btn-primary" id="confirmInviteBtn">发送邀请</button>
            </div>
        </div>
    </div>
</div>

<script>
var currentTeamId = {{ $team->id }};

document.getElementById('inviteBtn').addEventListener('click', function() {
    document.getElementById('inviteModal').style.display = 'flex';
    document.getElementById('inviteEmail').value = '';
    document.getElementById('inviteEmail').focus();
});

function closeInviteModal() {
    document.getElementById('inviteModal').style.display = 'none';
}

document.getElementById('confirmInviteBtn').addEventListener('click', function() {
    var email = document.getElementById('inviteEmail').value.trim();
    if (!email) { alert('请输入邮箱地址'); return; }

    var btn = document.getElementById('confirmInviteBtn');
    btn.disabled = true;
    btn.textContent = '发送中...';

    fetch('/api/team/invite', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'team_id=' + encodeURIComponent(currentTeamId) + '&email=' + encodeURIComponent(email)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            alert(data.msg || '邀请已发送');
            closeInviteModal();
            location.reload();
        } else {
            alert(data.msg || '邀请失败');
            btn.disabled = false;
            btn.textContent = '发送邀请';
        }
    })
    .catch(function() {
        alert('网络错误');
        btn.disabled = false;
        btn.textContent = '发送邀请';
    });
});

function removeMember(userId) {
    if (!confirm('确定要移除该成员吗？')) return;

    fetch('/api/team/member/remove', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'team_id=' + encodeURIComponent(currentTeamId) + '&user_id=' + encodeURIComponent(userId)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            location.reload();
        } else {
            alert(data.msg || '移除失败');
        }
    })
    .catch(function() {
        alert('网络错误');
    });
}

document.getElementById('inviteModal').addEventListener('click', function(e) {
    if (e.target === this) closeInviteModal();
});
</script>
