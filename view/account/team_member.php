@php
$secondary_items = [
    ['label' => '总览', 'href' => '/team/' . $team->id . '/dashboard', 'icon' => '&#9633;'],
    ['label' => '团队成员', 'href' => '/team/' . $team->id . '/member', 'icon' => '&#9783;', 'active' => true],
];
@endphp
@include('layout/app')

<div class="page-top-bar">
    <div>
        <h2>{{ $team->name }} - 成员管理</h2>
        <p style="color:#999;margin-top:4px">{{ $team->description or '暂无描述' }}</p>
    </div>
    <div class="flex gap-8">
        <button class="btn btn-primary btn-sm" id="inviteBtn" onclick="openInviteModal()">邀请成员</button>
    </div>
</div>

@if (!empty($error))
    <div class="alert alert-error">{{ $error }}</div>
@endif
<div id="pageError" class="alert alert-error" style="display:none;"></div>

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
                            @if ($member->role === 'admin')
                                <span class="role-badge role-admin">管理员</span>
                            @else
                                <span class="role-badge role-member">成员</span>
                            @endif
                        </td>
                        <td>{{ $member->joined_time }}</td>
                        @if ($is_creator && $member->role !== 'admin')
                            <td>
                                <button class="btn btn-default btn-sm" onclick="grantAdmin({{ $member->user_id }})">授权管理员</button>
                                <button class="btn btn-danger btn-sm" onclick="removeMember({{ $member->user_id }})">移除</button>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div id="inviteModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.5);z-index:1000;justify-content:center;align-items:center;">
    <div class="card" style="width:100%;max-width:420px;margin:20px;">
        <div class="card-header">邀请成员</div>
        <div class="card-body">
            <div id="inviteError" class="alert alert-error" style="display:none;"></div>
            <div id="inviteSuccess" class="alert alert-success" style="display:none;"></div>
            <div class="form-group">
                <label>输入对方邮箱地址</label>
                <input type="email" id="inviteEmail" class="form-control" placeholder="请输入邮箱">
            </div>
            <div class="flex gap-8" style="justify-content:flex-end">
                <button class="btn btn-default" onclick="closeInviteModal()">取消</button>
                <button class="btn btn-primary" id="confirmInviteBtn" onclick="confirmInvite()">发送邀请</button>
            </div>
        </div>
    </div>
</div>

<script>
var currentTeamId = {{ $team->id }};

function showInviteError(msg) {
    var el = document.getElementById('inviteError');
    el.textContent = msg;
    el.style.display = 'block';
    document.getElementById('inviteSuccess').style.display = 'none';
}

function showInviteSuccess(msg) {
    var el = document.getElementById('inviteSuccess');
    el.textContent = msg;
    el.style.display = 'block';
    document.getElementById('inviteError').style.display = 'none';
}

function showPageError(msg) {
    var el = document.getElementById('pageError');
    el.textContent = msg;
    el.style.display = 'block';
}

window.openInviteModal = function() {
    var modal = document.getElementById('inviteModal');
    modal.style.display = 'flex';
    document.getElementById('inviteEmail').value = '';
    document.getElementById('inviteError').style.display = 'none';
    document.getElementById('inviteSuccess').style.display = 'none';
    document.getElementById('inviteEmail').focus();
};

window.closeInviteModal = function() {
    document.getElementById('inviteModal').style.display = 'none';
};

window.confirmInvite = function() {
    var email = document.getElementById('inviteEmail').value.trim();
    if (!email) {
        showInviteError('请输入邮箱地址');
        return;
    }

    var btn = document.getElementById('confirmInviteBtn');
    btn.disabled = true;
    btn.textContent = '发送中...';
    document.getElementById('inviteError').style.display = 'none';

    fetch('/api/team/invite', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: 'team_id=' + encodeURIComponent(currentTeamId) + '&email=' + encodeURIComponent(email)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            showInviteSuccess(data.msg || '邀请已发送');
            setTimeout(function() {
                closeInviteModal();
                location.reload();
            }, 800);
        } else {
            showInviteError(data.msg || '邀请失败');
            btn.disabled = false;
            btn.textContent = '发送邀请';
        }
    })
    .catch(function() {
        showInviteError('网络错误');
        btn.disabled = false;
        btn.textContent = '发送邀请';
    });
};

window.confirmRemove = function(userId) {
    fetch('/api/team/member/remove', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: 'team_id=' + encodeURIComponent(currentTeamId) + '&user_id=' + encodeURIComponent(userId)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            closeInviteModal();
            location.reload();
        } else {
            showPageError(data.msg || '移除失败');
            restoreInviteModal();
        }
    })
    .catch(function() {
        showPageError('网络错误');
        restoreInviteModal();
    });
};

window.grantAdmin = function(userId) {
    var modal = document.getElementById('inviteModal');
    var cardBody = modal.querySelector('.card-body');
    modal.style.display = 'flex';
    cardBody.innerHTML = '<p style="margin-bottom:16px;">确定要将该成员授权为管理员吗？</p>' +
        '<div class="flex gap-8" style="justify-content:flex-end">' +
        '<button class="btn btn-default" onclick="restoreInviteModal()">取消</button>' +
        '<button class="btn btn-primary" onclick="confirmGrantAdmin(' + userId + ')">确认授权</button>' +
        '</div>';
};

window.confirmGrantAdmin = function(userId) {
    var btn = document.querySelector('#confirmInviteBtn');
    if (btn) { btn.disabled = true; btn.textContent = '处理中...'; }

    fetch('/api/team/member/grant_admin', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: 'team_id=' + encodeURIComponent(currentTeamId) + '&user_id=' + encodeURIComponent(userId)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            closeInviteModal();
            location.reload();
        } else {
            showPageError(data.msg || '授权失败');
            restoreInviteModal();
        }
    })
    .catch(function() {
        showPageError('网络错误');
        restoreInviteModal();
    });
};

window.restoreInviteModal = function() {
    var modal = document.getElementById('inviteModal');
    var cardBody = modal.querySelector('.card-body');
    cardBody.innerHTML = '<div class="form-group">' +
        '<label>输入对方邮箱地址</label>' +
        '<input type="email" id="inviteEmail" class="form-control" placeholder="请输入邮箱">' +
        '</div>' +
        '<div id="inviteError" class="alert alert-error" style="display:none;"></div>' +
        '<div id="inviteSuccess" class="alert alert-success" style="display:none;"></div>' +
        '<div class="flex gap-8" style="justify-content:flex-end">' +
        '<button class="btn btn-default" onclick="closeInviteModal()">取消</button>' +
        '<button class="btn btn-primary" id="confirmInviteBtn" onclick="confirmInvite()">发送邀请</button>' +
        '</div>';
    document.getElementById('inviteEmail').value = '';
    document.getElementById('inviteError').style.display = 'none';
    document.getElementById('inviteSuccess').style.display = 'none';
};

window.removeMember = function(userId) {
    var modal = document.getElementById('inviteModal');
    var cardBody = modal.querySelector('.card-body');

    cardBody.innerHTML = '<p style="margin-bottom:16px;">确定要移除该成员吗？</p>' +
        '<div class="flex gap-8" style="justify-content:flex-end">' +
        '<button class="btn btn-default" onclick="restoreInviteModal()">取消</button>' +
        '<button class="btn btn-danger" onclick="confirmRemove(' + userId + ')">确认移除</button>' +
        '</div>';
};

document.getElementById('inviteModal').addEventListener('click', function(e) {
    if (e.target === this) closeInviteModal();
});
</script>

@include('layout/app_footer')
