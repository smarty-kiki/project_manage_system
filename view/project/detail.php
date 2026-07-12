@php
$secondary_items = [];
$systems = $systems ?? [];
$business_processes = $business_processes ?? [];
$process_nodes = $process_nodes ?? [];
$requirements = $requirements ?? [];
$bugs = $bugs ?? [];
$modules = $modules ?? [];
$project_roles = $project_roles ?? [];
$role_modules = $role_modules ?? [];
@endphp
@include('layout/app')

<div class="page-top-bar">
    <h2>{{ $project->name or '项目' }}</h2>
    <a href="/team/{{ $team->id }}/project" class="btn btn-default btn-sm">&larr; 返回项目列表</a>
</div>

<!-- Overview Section (always visible) -->
<div style="margin-bottom: 16px;">
    <div style="display: flex; gap: 24px; margin-bottom: 12px; color: #666; font-size: 14px;">
        <span>创建时间：{{ $project->create_time }}</span>
        <span>最近更新：{{ $project->update_time }}</span>
    </div>
    <div style="color: #666; line-height: 1.8; font-size: 14px;">
        {{ $project->description or '暂无描述' }}
    </div>
</div>

<!-- Tab Navigation -->
<div style="background: #fff; border-radius: 8px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.1);">
    <div style="display: flex; border-bottom: 1px solid #f0f0f0; padding: 0 8px;">
        <a href="javascript:void(0)" onclick="switchTab('role')" id="tab-role" class="tab-link active">角色 <span style="display:inline-block;background:#1890ff;color:#fff;font-size:12px;padding:1px 7px;border-radius:10px;margin-left:4px;">{{ count($project_roles) }}</span></a>
        <a href="javascript:void(0)" onclick="switchTab('process')" id="tab-process" class="tab-link">业务流程 <span style="display:inline-block;background:#1890ff;color:#fff;font-size:12px;padding:1px 7px;border-radius:10px;margin-left:4px;">{{ count($business_processes) }}</span></a>
        <a href="javascript:void(0)" onclick="switchTab('system')" id="tab-system" class="tab-link">系统 <span style="display:inline-block;background:#1890ff;color:#fff;font-size:12px;padding:1px 7px;border-radius:10px;margin-left:4px;">{{ count($systems) }}</span></a>
        <a href="javascript:void(0)" onclick="switchTab('requirement')" id="tab-requirement" class="tab-link">需求 <span style="display:inline-block;background:#1890ff;color:#fff;font-size:12px;padding:1px 7px;border-radius:10px;margin-left:4px;">{{ count($requirements) }}</span></a>
        <a href="javascript:void(0)" onclick="switchTab('bug')" id="tab-bug" class="tab-link">BUG <span style="display:inline-block;background:#1890ff;color:#fff;font-size:12px;padding:1px 7px;border-radius:10px;margin-left:4px;">{{ count($bugs) }}</span></a>
    </div>
</div>

<!-- Tab Contents -->
<div id="tab-content-role" class="tab-content">
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span>角色列表</span>
            <button class="btn btn-primary btn-sm" onclick="showModal('roleModal')">+ 新建角色</button>
        </div>
        <div class="card-body">
            @if (empty($project_roles))
            <div class="empty-state"><p>暂无角色</p></div>
            @else
            @php
                $module_names = [];
                foreach ($modules as $m) { $module_names[$m->id] = $m->name; }
                $node_names = [];
                foreach ($process_nodes as $pn) { $node_names[$pn->id] = $pn->name; }
            @endphp
            <table class="member-table">
                <tr>
                    <th>名称</th>
                    <th>描述</th>
                    <th>流程节点</th>
                    <th>关联模块</th>
                </tr>
                @foreach ($project_roles as $r)
                <tr>
                    <td><strong>{{ $r->name }}</strong></td>
                    <td style="color: #666;">{{ $r->description or '-' }}</td>
                    <td>{{ $node_names[$r->process_node_id] ?? '-' }}</td>
                    <td>
                        @php
                            $modIds = $role_modules[$r->id] ?? [];
                            $modNames = [];
                            foreach ($modIds as $mid) { if (isset($module_names[$mid])) { $modNames[] = $module_names[$mid]; } }
                            echo implode(', ', $modNames) ?: '-';
                        @endphp
                    </td>
                </tr>
                @endforeach
            </table>
            @endif
        </div>
    </div>
</div>

<div id="tab-content-process" class="tab-content" style="display: none;">
    @php
        $nodes_by_process = [];
        foreach ($process_nodes as $pn) {
            $nodes_by_process[$pn->business_process_id][] = $pn;
        }
        $role_map = [];
        foreach ($project_roles as $r) { $role_map[$r->id] = $r; }
    @endphp
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span>业务流程列表</span>
            <button class="btn btn-primary btn-sm" onclick="showModal('processModal')">+ 新建流程</button>
        </div>
        <div class="card-body">
            @if (empty($business_processes))
            <div class="empty-state"><p>暂无业务流程</p></div>
            @else
            @foreach ($business_processes as $bp)
            @php
                $initiator = null;
                if ($bp->initiator_role_id) {
                    foreach ($project_roles as $r) { if ($r->id == $bp->initiator_role_id) { $initiator = $r; break; } }
                }
                $bp_nodes = $nodes_by_process[$bp->id] ?? [];
            @endphp
            <div style="margin-bottom: 16px; padding: 12px; background: #fafafa; border-radius: 6px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: {{ !empty($bp_nodes) ? '8px' : '0' }};">
                    <div>
                        <strong style="font-size: 14px;">{{ $bp->name }}</strong>
                        <span style="color: #999; font-size: 13px; margin-left: 8px;">{{ $bp->description or '' }}</span>
                        @if ($initiator)
                        <span style="color: #1890ff; font-size: 12px; margin-left: 8px;">发起：{{ $initiator->name }}</span>
                        @endif
                    </div>
                    <button class="btn btn-default btn-xs" onclick="showProcessNodeModal({{ $bp->id }})">+ 添加节点</button>
                </div>
                @if (!empty($bp_nodes))
                <table class="member-table" style="font-size: 13px;">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>节点名称</th>
                        <th>描述</th>
                        <th>绑定角色</th>
                    </tr>
                    @foreach ($bp_nodes as $node)
                    @php
                        $node_role = null;
                        if ($node->project_role_id) {
                            foreach ($project_roles as $r) { if ($r->id == $node->project_role_id) { $node_role = $r; break; } }
                        }
                    @endphp
                    <tr>
                        <td>{{ $node->sort_order }}</td>
                        <td>{{ $node->name }}</td>
                        <td style="color: #999;">{{ $node->description or '-' }}</td>
                        <td>{{ $node_role ? $node_role->name : '-' }}</td>
                    </tr>
                    @endforeach
                </table>
                @endif
            </div>
            @endforeach
            @endif
        </div>
    </div>
</div>

<div id="tab-content-system" class="tab-content" style="display: none;">
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span>系统列表</span>
            <button class="btn btn-primary btn-sm" onclick="showModal('systemModal')">+ 新建系统</button>
        </div>
        <div class="card-body">
            @if (empty($systems))
            <div class="empty-state"><p>暂无系统</p></div>
            @else
            <table class="member-table">
                <tr>
                    <th>名称</th>
                    <th>描述</th>
                    <th>Git 链接</th>
                    <th>模块数</th>
                </tr>
                @foreach ($systems as $s)
                <tr>
                    <td><strong>{{ $s->name }}</strong></td>
                    <td style="color: #666;">{{ $s->description or '-' }}</td>
                    <td>
                        @if ($s->git_url)
                        <a href="{{ $s->git_url }}" target="_blank" style="color: #1890ff;">{{ $s->git_url }}</a>
                        @else
                        <span style="color: #999;">-</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $sysModules = array_filter($modules, function($m) use ($s) { return $m->system_id == $s->id; });
                        @endphp
                        {{ count($sysModules) }} 个
                    </td>
                </tr>
                @endforeach
            </table>
            @endif
        </div>
    </div>
</div>

<div id="tab-content-requirement" class="tab-content" style="display: none;">
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span>需求列表</span>
            <button class="btn btn-primary btn-sm" onclick="showModal('requirementModal')">+ 新建需求</button>
        </div>
        <div class="card-body">
            @if (empty($requirements))
            <div class="empty-state"><p>暂无需求</p></div>
            @else
            <table class="member-table">
                <tr>
                    <th>名称</th>
                    <th>描述</th>
                    <th>关联系统</th>
                    <th>关联模块</th>
                    <th>关联角色</th>
                </tr>
                @foreach ($requirements as $req)
                @php
                    $sys = null;
                    $mod = null;
                    $role = null;
                    if ($req->system_id) {
                        foreach ($systems as $s) { if ($s->id == $req->system_id) { $sys = $s; break; } }
                    }
                    if ($req->module_id) {
                        foreach ($modules as $m) { if ($m->id == $req->module_id) { $mod = $m; break; } }
                    }
                    if ($req->role_id) {
                        foreach ($project_roles as $r) { if ($r->id == $req->role_id) { $role = $r; break; } }
                    }
                @endphp
                <tr>
                    <td><strong>{{ $req->name }}</strong></td>
                    <td style="color: #666;">{{ $req->description or '-' }}</td>
                    <td>{{ $sys ? $sys->name : '-' }}</td>
                    <td>{{ $mod ? $mod->name : '-' }}</td>
                    <td>{{ $role ? $role->name : '-' }}</td>
                </tr>
                @endforeach
            </table>
            @endif
        </div>
    </div>
</div>

<div id="tab-content-bug" class="tab-content" style="display: none;">
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span>BUG 列表</span>
            <button class="btn btn-primary btn-sm" onclick="showModal('bugModal')">+ 新建 BUG</button>
        </div>
        <div class="card-body">
            @if (empty($bugs))
            <div class="empty-state"><p>暂无 BUG</p></div>
            @else
            <table class="member-table">
                <tr>
                    <th>名称</th>
                    <th>描述</th>
                    <th>关联需求</th>
                    <th>关联角色</th>
                </tr>
                @foreach ($bugs as $b)
                @php
                    $linkedReq = null;
                    $role = null;
                    if ($b->requirement_id) {
                        foreach ($requirements as $r) { if ($r->id == $b->requirement_id) { $linkedReq = $r; break; } }
                    }
                    if ($b->role_id) {
                        foreach ($project_roles as $r) { if ($r->id == $b->role_id) { $role = $r; break; } }
                    }
                @endphp
                <tr>
                    <td><strong>{{ $b->name }}</strong></td>
                    <td style="color: #666;">{{ $b->description or '-' }}</td>
                    <td>{{ $linkedReq ? $linkedReq->name : '-' }}</td>
                    <td>{{ $role ? $role->name : '-' }}</td>
                </tr>
                @endforeach
            </table>
            @endif
        </div>
    </div>
</div>

<style>
.tab-link { padding: 12px 20px; color: #666; font-size: 14px; border-bottom: 2px solid transparent; cursor: pointer; }
.tab-link:hover { color: #1890ff; }
.tab-link.active { color: #1890ff; border-bottom-color: #1890ff; font-weight: 500; }
.member-table { width: 100%; border-collapse: collapse; }
.member-table th, .member-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #f5f5f5; }
.member-table th { background: #fafafa; font-weight: 600; color: #666; }
</style>

<!-- System Modal -->
<div id="systemModal" class="modal-overlay" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-header"><span>新建系统</span><a href="javascript:void(0)" onclick="hideModal('systemModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitForm(event, '/api/system/create', 'systemModal')">
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div class="form-group"><label>系统名称 *</label><input type="text" class="form-control" name="name" required></div>
                <div class="form-group"><label>Git 链接</label><input type="text" class="form-control" name="git_url" placeholder="https://github.com/..."></div>
                <div class="form-group"><label>描述</label><textarea class="form-control" name="description"></textarea></div>
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('systemModal')">取消</button><button type="submit" class="btn btn-primary">创建</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Business Process Modal -->
<div id="processModal" class="modal-overlay" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-header"><span>新建业务流程</span><a href="javascript:void(0)" onclick="hideModal('processModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitForm(event, '/api/business_process/create', 'processModal')">
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div class="form-group"><label>流程名称 *</label><input type="text" class="form-control" name="name" required></div>
                <div class="form-group"><label>描述</label><textarea class="form-control" name="description"></textarea></div>
                <div class="form-group">
                    <label>发起角色</label>
                    <select class="form-control" name="initiator_role_id">
                        <option value="0">不指定</option>
                        @foreach ($project_roles as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('processModal')">取消</button><button type="submit" class="btn btn-primary">创建</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Process Node Modal -->
<div id="processNodeModal" class="modal-overlay" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-header"><span>新建流程节点</span><a href="javascript:void(0)" onclick="hideModal('processNodeModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitProcessNodeForm(event, 'processNodeModal')">
                <input type="hidden" name="business_process_id" id="node_bp_id" value="">
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div class="form-group"><label>节点名称 *</label><input type="text" class="form-control" name="name" required></div>
                <div class="form-group"><label>描述</label><textarea class="form-control" name="description"></textarea></div>
                <div class="form-group">
                    <label>绑定角色 *</label>
                    <select class="form-control" name="project_role_id" required>
                        <option value="0">请选择角色</option>
                        @foreach ($project_roles as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>排序</label><input type="number" class="form-control" name="sort_order" value="0"></div>
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('processNodeModal')">取消</button><button type="submit" class="btn btn-primary">添加节点</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Role Modal -->
<div id="roleModal" class="modal-overlay" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-header"><span>新建角色</span><a href="javascript:void(0)" onclick="hideModal('roleModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitRoleForm(event, 'roleModal')">
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div class="form-group"><label>角色名称 *</label><input type="text" class="form-control" name="name" required placeholder="如：顾客、商品运营"></div>
                <div class="form-group"><label>描述</label><textarea class="form-control" name="description" placeholder="角色职责说明"></textarea></div>
                <div class="form-group">
                    <label>关联流程节点</label>
                    <select class="form-control" name="process_node_id">
                        <option value="0">不关联</option>
                        @foreach ($process_nodes as $pn)
                        <option value="{{ $pn->id }}">{{ $pn->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('roleModal')">取消</button><button type="submit" class="btn btn-primary">创建</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Requirement Modal -->
<div id="requirementModal" class="modal-overlay" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-header"><span>新建需求</span><a href="javascript:void(0)" onclick="hideModal('requirementModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitForm(event, '/api/requirement/create', 'requirementModal')">
                <div class="form-group"><label>需求名称 *</label><input type="text" class="form-control" name="name" required></div>
                <div class="form-group">
                    <label>关联系统</label>
                    <select class="form-control" name="system_id">
                        <option value="0">不关联</option>
                        @foreach ($systems as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>关联模块</label>
                    <select class="form-control" name="module_id">
                        <option value="0">不关联</option>
                        @foreach ($modules as $m)
                        <option value="{{ $m->id }}" data-system="{{ $m->system_id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>关联角色</label>
                    <select class="form-control" name="role_id">
                        <option value="0">不关联</option>
                        @foreach ($project_roles as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div class="form-group"><label>描述</label><textarea class="form-control" name="description"></textarea></div>
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('requirementModal')">取消</button><button type="submit" class="btn btn-primary">创建</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Bug Modal -->
<div id="bugModal" class="modal-overlay" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-header"><span>新建 BUG</span><a href="javascript:void(0)" onclick="hideModal('bugModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitForm(event, '/api/bug/create', 'bugModal')">
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div class="form-group"><label>BUG 名称 *</label><input type="text" class="form-control" name="name" required></div>
                <div class="form-group">
                    <label>关联需求</label>
                    <select class="form-control" name="requirement_id">
                        <option value="0">不关联</option>
                        @foreach ($requirements as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>关联角色</label>
                    <select class="form-control" name="role_id">
                        <option value="0">不关联</option>
                        @foreach ($project_roles as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>描述</label><textarea class="form-control" name="description"></textarea></div>
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('bugModal')">取消</button><button type="submit" class="btn btn-primary">创建</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function switchTab(tab) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-link').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-content-' + tab).style.display = 'block';
    document.getElementById('tab-' + tab).classList.add('active');
}

function showModal(id) {
    var modal = document.getElementById(id);
    if (modal) { modal.style.display = 'flex'; }
}

function hideModal(id) {
    var modal = document.getElementById(id);
    if (modal) { modal.style.display = 'none'; }
}

function submitForm(e, url, modalId) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '提交中...';

    var params = [];
    var inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(function(input) {
        if (input.name && input.type !== 'submit') {
            params.push(encodeURIComponent(input.name) + '=' + encodeURIComponent(input.value));
        }
    });

    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        btn.disabled = false;
        btn.textContent = '创建';
        if (xhr.status === 200) {
            hideModal(modalId);
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
    xhr.send(params.join('&'));
}

function submitRoleForm(e, modalId) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '提交中...';

    var params = [];
    var inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(function(input) {
        if (input.name && input.type !== 'submit') {
            params.push(encodeURIComponent(input.name) + '=' + encodeURIComponent(input.value));
        }
    });

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/project_role/create', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        btn.disabled = false;
        btn.textContent = '创建';
        if (xhr.status === 200) {
            hideModal(modalId);
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
    xhr.send(params.join('&'));
}

function showProcessNodeModal(business_process_id) {
    document.getElementById('node_bp_id').value = business_process_id;
    showModal('processNodeModal');
}

function submitProcessNodeForm(e, modalId) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '提交中...';

    var params = [];
    var inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(function(input) {
        if (input.name && input.type !== 'submit') {
            params.push(encodeURIComponent(input.name) + '=' + encodeURIComponent(input.value));
        }
    });

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/process_node/create', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        btn.disabled = false;
        btn.textContent = '添加节点';
        if (xhr.status === 200) {
            hideModal(modalId);
            location.reload();
        } else {
            alert('创建失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.textContent = '添加节点';
        alert('网络错误，请重试');
    };
    xhr.send(params.join('&'));
}

document.querySelectorAll('.modal-overlay').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === this) hideModal(this.id);
    });
});
</script>

<style>
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.4); z-index: 300; display: flex; justify-content: center; align-items: center; }
.modal-dialog { background: #fff; border-radius: 8px; width: 100%; max-width: 480px; margin: 20px; box-shadow: 0 4px 16px rgba(0,0,0,.15); }
.modal-header { padding: 16px 20px; border-bottom: 1px solid #f0f0f0; font-weight: 600; font-size: 16px; display: flex; justify-content: space-between; align-items: center; }
.modal-header a { color: #999; font-size: 20px; text-decoration: none; }
.modal-body { padding: 20px; }
</style>

@include('layout/app_footer')
