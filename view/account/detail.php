@php
$secondary_items = [
    ['label' => '我的团队', 'href' => '/account/team', 'icon' => '&#9776;'],
    ['label' => '账户详情', 'href' => '/account/detail', 'icon' => '&#9998;', 'active' => true],
];
@endphp
@include('layout/app')

<div class="page-top-bar">
    <h2>账户详情</h2>
</div>

@if (!empty($error))
    <div class="alert alert-error">{{ $error }}</div>
@endif
@if (!empty($success))
    <div class="alert alert-success">{{ $success }}</div>
@endif

<div class="card" style="max-width:480px;">
    <div class="card-body">
        <div class="form-group">
            <label>邮箱</label>
            <input type="text" class="form-control" value="{{ $user->email }}" disabled style="background:#f5f5f5;">
        </div>
        <div class="form-group">
            <label>姓名</label>
            <input type="text" id="userName" class="form-control" value="{{ $user->name or '' }}" placeholder="请输入姓名">
        </div>
        <div class="flex gap-8" style="justify-content:flex-end">
            <a href="/account/team" class="btn btn-default" style="text-decoration:none;">返回</a>
            <button class="btn btn-primary" id="saveBtn" onclick="saveName()">保存</button>
        </div>
    </div>
</div>

<script>
function saveName() {
    var name = document.getElementById('userName').value.trim();
    if (!name) {
        alert('请输入姓名');
        return;
    }

    var btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.textContent = '保存中...';

    var formData = new URLSearchParams();
    formData.append('name', name);

    fetch('/api/account/update_name', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: formData.toString()
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.code === 0) {
            window.location.href = data.redirect || '/account/detail';
        } else {
            alert(data.msg || '保存失败');
            btn.disabled = false;
            btn.textContent = '保存';
        }
    })
    .catch(function() {
        alert('网络错误');
        btn.disabled = false;
        btn.textContent = '保存';
    });
}
</script>

@include('layout/app_footer')
