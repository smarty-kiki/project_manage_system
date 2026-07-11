@php
$project_list = $projects ?? [];
$secondary_items = [];
@endphp
@include('layout/app')

<div class="page-top-bar">
    <h2>{{ $team->name or '团队' }} - 项目</h2>
</div>

@if (empty($project_list))
<div class="card">
    <div class="empty-state">
        <p>暂无项目</p>
    </div>
</div>
@else
<div class="team-grid">
    @foreach ($project_list as $p)
    <a href="/team/{{ $team->id }}/project/{{ $p->id }}" class="team-card">
        <h3>{{ $p->name }}</h3>
        <p>{{ $p->description or '暂无描述' }}</p>
        <div class="meta">创建于 {{ $p->create_time }}</div>
    </a>
    @endforeach
</div>
@endif

<!-- Create Project Modal -->
<div id="createModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.4); z-index:300; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:8px; width:100%; max-width:480px; margin:20px; box-shadow:0 4px 16px rgba(0,0,0,.15);">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <span>新建项目</span>
            <a href="#" onclick="hideCreateModal(); return false;" style="color:#999; font-size:20px; text-decoration:none;">&times;</a>
        </div>
        <div class="card-body">
            <form id="createProjectForm" onsubmit="submitCreateProject(event)">
                <div class="form-group">
                    <label>项目名称 <span style="color:#ff4d4f">*</span></label>
                    <input type="text" class="form-control" id="projName" placeholder="请输入项目名称" required>
                </div>
                <div class="form-group">
                    <label>项目描述</label>
                    <textarea class="form-control" id="projDesc" placeholder="请输入项目描述（选填）"></textarea>
                </div>
                <input type="hidden" id="projTeamId" value="{{ $team->id or '' }}">
                <div class="text-right">
                    <button type="button" class="btn btn-default" onclick="hideCreateModal()">取消</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">创建</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showCreateModal() {
    var modal = document.getElementById('createModal');
    modal.style.display = 'flex';
    document.getElementById('projName').focus();
}

function hideCreateModal() {
    document.getElementById('createModal').style.display = 'none';
}

function submitCreateProject(e) {
    e.preventDefault();
    var btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.textContent = '创建中...';

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/project/create', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        btn.disabled = false;
        btn.textContent = '创建';
        if (xhr.status === 200) {
            hideCreateModal();
            location.reload();
        } else {
            alert('创建失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.textContent = '创建';
        alert('网络错误，请重试');
    };

    var params = 'team_id=' + encodeURIComponent(document.getElementById('projTeamId').value) +
                 '&name=' + encodeURIComponent(document.getElementById('projName').value) +
                 '&description=' + encodeURIComponent(document.getElementById('projDesc').value);
    xhr.send(params);
}

// Close modal on overlay click
document.getElementById('createModal').addEventListener('click', function(e) {
    if (e.target === this) hideCreateModal();
});
</script>

@include('layout/app_footer')
