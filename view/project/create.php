@php
$hide_sidebar = true;
$secondary_items = [];
@endphp
@include('layout/app')

<div class="page-top-bar">
    <h2>新建项目</h2>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body">
        <form id="createForm" onsubmit="return false;">
            <div class="form-group">
                <label>所属团队</label>
                <select id="team_id" class="form-control" onchange="onTeamChange()">
                    @foreach ($user_teams as $t)
                    <option value="{{ $t->id }}" {{ $t->id == $team->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>项目名称 <span style="color:#ff4d4f;">*</span></label>
                <input type="text" id="name" class="form-control" placeholder="请输入项目名称" maxlength="100">
            </div>
            <div class="form-group">
                <label>项目描述</label>
                <textarea id="description" class="form-control" rows="4" placeholder="请输入项目描述" maxlength="500"></textarea>
            </div>
            <div id="formError" style="display:none;" class="alert-error" style="padding:10px 16px;border-radius:4px;margin-bottom:16px;"></div>
            <div style="text-align: right;">
                <a href="/team/{{ $team->id }}/dashboard" class="btn btn-default">取消</a>
                <button type="button" class="btn btn-primary" onclick="submitCreate()">创建</button>
            </div>
        </form>
    </div>
</div>

<script>
function onTeamChange() {
    var teamId = document.getElementById('team_id').value;
    var firstLink = document.querySelector('.sidebar-item[href*="team/' + teamId + '/dashboard"]');
    if (firstLink) {
        // no-op, team is valid
    }
}

function showError(msg) {
    var el = document.getElementById('formError');
    el.textContent = msg;
    el.style.display = 'block';
}

function submitCreate() {
    var teamId = document.getElementById('team_id').value;
    var name = document.getElementById('name').value.trim();
    var description = document.getElementById('description').value.trim();
    var errorEl = document.getElementById('formError');

    if (!name) {
        showError('请输入项目名称');
        return;
    }

    errorEl.style.display = 'none';

    var params = 'team_id=' + encodeURIComponent(teamId) +
                 '&name=' + encodeURIComponent(name) +
                 '&description=' + encodeURIComponent(description) +
                 '&VAR_AJAX_SUBMIT=1';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/project/create', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) return;
        if (xhr.status === 200) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.code === 0) {
                    location.href = '/team/' + teamId + '/project/' + data.data.id;
                } else {
                    showError(data.msg || '创建失败');
                }
            } catch (e) {
                showError('创建失败：响应格式错误');
            }
        } else {
            showError('创建失败：' + (xhr.responseText || '网络错误'));
        }
    };
    xhr.send(params);
}
</script>

@include('layout/app_footer')
