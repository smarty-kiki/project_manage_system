@php
$secondary_items = [
    ['label' => '我的团队', 'href' => '/account/team', 'icon' => '&#9776;'],
    ['label' => '创建团队', 'href' => '/account/team/create', 'icon' => '&#43;', 'active' => true],
];
@endphp
@include('layout/app')

<div class="page-top-bar">
    <h2>创建团队</h2>
</div>

<div class="card" style="max-width:600px">
    <div class="card-body">
        <div id="errorMsg" class="alert alert-error" style="display:none;"></div>
        <div id="successMsg" class="alert alert-success" style="display:none;"></div>
        <form id="createTeamForm" onsubmit="handleCreateTeam();return false;">
            <div class="form-group">
                <label>团队名称 <span style="color:red">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="请输入团队名称" required id="teamName">
            </div>
            <div class="form-group">
                <label>团队描述</label>
                <textarea name="description" class="form-control" placeholder="简单描述这个团队的用途..." id="teamDesc"></textarea>
            </div>
            <div class="flex gap-8">
                <button type="submit" class="btn btn-primary" id="submitBtn" onclick="handleCreateTeam();return false;">创建团队</button>
                <a href="/account/team" class="btn btn-default">取消</a>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    var nameInput = document.getElementById('teamName');
    var descInput = document.getElementById('teamDesc');
    var submitBtn = document.getElementById('submitBtn');
    var errorMsg = document.getElementById('errorMsg');
    var successMsg = document.getElementById('successMsg');

    function showError(msg) {
        errorMsg.textContent = msg;
        errorMsg.style.display = 'block';
        successMsg.style.display = 'none';
    }

    function showSuccess(msg) {
        successMsg.textContent = msg;
        successMsg.style.display = 'block';
        errorMsg.style.display = 'none';
    }

    function clearMessages() {
        errorMsg.style.display = 'none';
        successMsg.style.display = 'none';
    }

    nameInput.addEventListener('input', clearMessages);

    window.handleCreateTeam = function() {
        var name = nameInput.value.trim();
        var desc = descInput.value.trim();

        if (!name) {
            showError('请输入团队名称');
            return;
        }

        clearMessages();
        submitBtn.disabled = true;
        submitBtn.textContent = '创建中...';

        fetch('/api/team/create', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: 'name=' + encodeURIComponent(name) + '&description=' + encodeURIComponent(desc)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code === 0) {
                window.location.href = data.data.redirect || '/account/team';
            } else {
                showError(data.msg || '创建失败');
                submitBtn.disabled = false;
                submitBtn.textContent = '创建团队';
            }
        })
        .catch(function() {
            showError('网络错误');
            submitBtn.disabled = false;
            submitBtn.textContent = '创建团队';
        });
    };
})();
</script>
