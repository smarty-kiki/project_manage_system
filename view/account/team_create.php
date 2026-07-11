@include('layout/app')

<div class="app-layout">
@include('account/sidebar')

<div class="main-content">
    <div class="top-bar">
        <h2>创建团队</h2>
    </div>

    <div class="card" style="max-width:600px">
        <div class="card-body">
            @if (!empty($error))
                <div class="alert alert-error">{{ $error }}</div>
            @endif
            @if (!empty($message))
                <div class="alert alert-success">{{ $message }}</div>
            @endif
            <form method="post" action="/api/team/create" id="createTeamForm">
                <div class="form-group">
                    <label>团队名称 <span style="color:red">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="请输入团队名称" required id="teamName">
                </div>
                <div class="form-group">
                    <label>团队描述</label>
                    <textarea name="description" class="form-control" placeholder="简单描述这个团队的用途..." id="teamDesc"></textarea>
                </div>
                <div class="flex gap-8">
                    <button type="submit" class="btn btn-primary" id="submitBtn">创建团队</button>
                    <a href="/account/team" class="btn btn-default">取消</a>
                </div>
            </form>
        </div>
    </div>
</div>
</div>

<script>
document.getElementById('createTeamForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var name = document.getElementById('teamName').value.trim();
    var desc = document.getElementById('teamDesc').value.trim();
    var btn = document.getElementById('submitBtn');

    if (!name) { alert('请输入团队名称'); return; }

    btn.disabled = true;
    btn.textContent = '创建中...';

    fetch('/api/team/create', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'name=' + encodeURIComponent(name) + '&description=' + encodeURIComponent(desc)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            window.location.href = data.data.redirect || '/account/team';
        } else {
            alert(data.msg || '创建失败');
            btn.disabled = false;
            btn.textContent = '创建团队';
        }
    })
    .catch(function() {
        alert('网络错误');
        btn.disabled = false;
        btn.textContent = '创建团队';
    });
});
</script>
