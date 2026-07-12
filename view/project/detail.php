@php
$secondary_items = [];
$hide_sidebar = true;
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

<!-- Discussion Card -->
<div class="card" style="margin-bottom: 16px;">
    <div class="card-body">
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <div style="position: relative;">
                <textarea id="discussionInput" class="form-control" rows="3" placeholder="输入内容..." style="resize: vertical; padding-bottom: 40px;"></textarea>
                <div style="position: absolute; bottom: 8px; right: 8px;">
                    <button class="btn btn-primary btn-sm" onclick="submitDiscussion()">发送</button>
                </div>
            </div>
            <div id="discussionEcho" style="display: none; border-top: 1px solid #f0f0f0; padding-top: 12px;">
                <div id="discussionText" style="color: #333; font-size: 14px; line-height: 1.8; margin-bottom: 12px; white-space: pre-wrap;"></div>
                <div style="background: #fafafa; border-radius: 6px; padding: 12px;">
                    <div style="font-size: 13px; color: #666; margin-bottom: 8px;">待提交确认</div>
                    <div style="display: flex; border-bottom: 1px solid #f0f0f0; padding: 0 8px; margin-bottom: 8px;">
                        <a href="javascript:void(0)" onclick="switchPendingTab('role')" id="pending-tab-role" class="tab-link active" style="font-size: 13px;">角色 <span style="display:inline-block;background:#1890ff;color:#fff;font-size:11px;padding:1px 6px;border-radius:10px;margin-left:4px;">{{ count($project_roles) }}</span></a>
                        <a href="javascript:void(0)" onclick="switchPendingTab('process')" id="pending-tab-process" class="tab-link" style="font-size: 13px;">业务流程 <span style="display:inline-block;background:#1890ff;color:#fff;font-size:11px;padding:1px 6px;border-radius:10px;margin-left:4px;">{{ count($business_processes) }}</span></a>
                        <a href="javascript:void(0)" onclick="switchPendingTab('system')" id="pending-tab-system" class="tab-link" style="font-size: 13px;">系统 <span style="display:inline-block;background:#1890ff;color:#fff;font-size:11px;padding:1px 6px;border-radius:10px;margin-left:4px;">{{ count($systems) }}</span></a>
                        <a href="javascript:void(0)" onclick="switchPendingTab('requirement')" id="pending-tab-requirement" class="tab-link" style="font-size: 13px;">需求 <span style="display:inline-block;background:#1890ff;color:#fff;font-size:11px;padding:1px 6px;border-radius:10px;margin-left:4px;">{{ count($requirements) }}</span></a>
                        <a href="javascript:void(0)" onclick="switchPendingTab('bug')" id="pending-tab-bug" class="tab-link" style="font-size: 13px;">BUG <span style="display:inline-block;background:#1890ff;color:#fff;font-size:11px;padding:1px 6px;border-radius:10px;margin-left:4px;">{{ count($bugs) }}</span></a>
                    </div>
                    <div id="pending-content-role" class="pending-content">
                        <div style="color: #999; font-size: 13px;">角色 {{ count($project_roles) }} 个</div>
                    </div>
                    <div id="pending-content-process" class="pending-content" style="display: none;">
                        <div style="color: #999; font-size: 13px;">业务流程 {{ count($business_processes) }} 个</div>
                    </div>
                    <div id="pending-content-system" class="pending-content" style="display: none;">
                        <div style="color: #999; font-size: 13px;">系统 {{ count($systems) }} 个</div>
                    </div>
                    <div id="pending-content-requirement" class="pending-content" style="display: none;">
                        <div style="color: #999; font-size: 13px;">需求 {{ count($requirements) }} 个</div>
                    </div>
                    <div id="pending-content-bug" class="pending-content" style="display: none;">
                        <div style="color: #999; font-size: 13px;">BUG {{ count($bugs) }} 个</div>
                    </div>
                </div>
            </div>
        </div>
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
            <button class="btn btn-primary btn-sm" onclick="resetRoleModal(); showModal('roleModal')">+ 新建角色</button>
        </div>
        <div class="card-body">
            @if (empty($project_roles))
            <div class="empty-state"><p>暂无角色</p></div>
            @else
            @php
                $module_names = [];
                foreach ($modules as $m) { $module_names[$m->id] = $m->name; }
                $nodes_by_role = [];
                foreach ($process_nodes as $pn) {
                    if ($pn->project_role_id) {
                        $nodes_by_role[$pn->project_role_id][] = $pn->name;
                    }
                }
            @endphp
            <table class="member-table">
                <tr>
                    <th>名称</th>
                    <th>描述</th>
                    <th>流程节点</th>
                    <th>关联模块</th>
                    <th style="width: 100px;">操作</th>
                </tr>
                @foreach ($project_roles as $r)
                <tr>
                    <td><strong>{{ $r->name }}</strong></td>
                    <td style="color: #666;">{{ $r->description or '-' }}</td>
                    <td>{{ implode(', ', $nodes_by_role[$r->id] ?? []) ?: '-' }}</td>
                    <td>
                        @php
                            $modIds = $role_modules[$r->id] ?? [];
                            $modNames = [];
                            foreach ($modIds as $mid) { if (isset($module_names[$mid])) { $modNames[] = $module_names[$mid]; } }
                            echo implode(', ', $modNames) ?: '-';
                        @endphp
                    </td>
                    <td>
                        <a href="javascript:void(0)" class="action-link" onclick="editRole({{ $r->id }}, '{{ addslashes($r->name) }}', '{{ addslashes($r->description or '') }}')">编辑</a>
                        <a href="javascript:void(0)" class="action-link action-link-danger" onclick="deleteRole({{ $r->id }}, '{{ addslashes($r->name) }}')">删除</a>
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
                    <div>
                        <a href="javascript:void(0)" class="action-link" onclick="editProcess({{ $bp->id }}, '{{ addslashes($bp->name) }}', '{{ addslashes($bp->description or '') }}', {{ $bp->initiator_role_id }})">编辑</a>
                        <a href="javascript:void(0)" class="action-link action-link-danger" onclick="deleteProcess({{ $bp->id }}, '{{ addslashes($bp->name) }}')">删除</a>
                        <button class="btn btn-default btn-xs" onclick="showProcessNodeModal({{ $bp->id }})" style="margin-left: 8px;">+ 添加节点</button>
                    </div>
                </div>
                @if (!empty($bp_nodes))
                <table class="member-table" style="font-size: 13px;">
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th>节点名称</th>
                        <th>描述</th>
                        <th>绑定角色</th>
                        <th style="width: 100px;">操作</th>
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
                        <td>
                            <a href="javascript:void(0)" class="action-link" onclick="editProcessNode({{ $node->id }}, '{{ addslashes($node->name) }}', '{{ addslashes($node->description or '') }}', {{ $node->sort_order }}, {{ $node->project_role_id }})">编辑</a>
                            <a href="javascript:void(0)" class="action-link action-link-danger" onclick="deleteProcessNode({{ $node->id }}, '{{ addslashes($node->name) }}')">删除</a>
                        </td>
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
    <div class="split-pane">
        <!-- Left: System list -->
        <div class="split-left">
            <div class="split-panel-header">
                <span class="split-panel-title">系统列表</span>
                <button class="btn btn-primary btn-sm" onclick="openSystemModal()">+ 新建</button>
            </div>
            <div class="split-panel-body" id="systemList">
                @if (empty($systems))
                <div class="empty-state"><p>暂无系统</p></div>
                @else
                @foreach ($systems as $s)
                @php $sysModules = array_filter($modules, function($m) use ($s) { return $m->system_id == $s->id; }); @endphp
                <div class="system-item" data-system-id="{{ $s->id }}" onclick="selectSystem({{ $s->id }})">
                    <div class="system-item-name">{{ $s->name }}</div>
                    <div class="system-item-meta">{{ count($sysModules) }} 个模块</div>
                    <div class="system-item-actions">
                        <a href="javascript:void(0)" class="action-link" onclick="event.stopPropagation(); editSystem({{ $s->id }}, '{{ $s->name }}', '{{ $s->git_url or '' }}', '{{ addslashes($s->description or '') }}')">编辑</a>
                        <a href="javascript:void(0)" class="action-link action-link-danger" onclick="event.stopPropagation(); deleteSystem({{ $s->id }}, '{{ $s->name }}')">删除</a>
                    </div>
                </div>
                @endforeach
                @endif
            </div>
        </div>
        <!-- Right: Module list -->
        <div class="split-right">
            <div class="split-panel-header">
                <span class="split-panel-title" id="modulePanelTitle">选择一个系统查看模块</span>
                <button class="btn btn-primary btn-sm" id="moduleAddBtn" style="display:none;" onclick="openModuleModal()">+ 新建模块</button>
            </div>
            <div class="split-panel-body" id="modulePanel">
                <div class="empty-state" id="moduleEmpty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    <p>请在左侧选择一个系统</p>
                </div>
                <div id="moduleList" style="display:none;"></div>
            </div>
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
                    <th style="width: 100px;">操作</th>
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
                    <td>
                        <a href="javascript:void(0)" class="action-link" onclick="editRequirement({{ $req->id }}, {{ $req->project_id }}, {{ $req->system_id }}, {{ $req->module_id }}, {{ $req->role_id }}, '{{ addslashes($req->name) }}', '{{ addslashes($req->description or '') }}')">编辑</a>
                        <a href="javascript:void(0)" class="action-link action-link-danger" onclick="deleteRequirement({{ $req->id }}, '{{ addslashes($req->name) }}')">删除</a>
                    </td>
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
                    <th style="width: 100px;">操作</th>
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
                    <td>
                        <a href="javascript:void(0)" class="action-link" onclick="editBug({{ $b->id }}, {{ $b->project_id }}, {{ $b->requirement_id }}, {{ $b->role_id }}, '{{ addslashes($b->name) }}', '{{ addslashes($b->description or '') }}')">编辑</a>
                        <a href="javascript:void(0)" class="action-link action-link-danger" onclick="deleteBug({{ $b->id }}, '{{ addslashes($b->name) }}')">删除</a>
                    </td>
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
        <div class="modal-header"><span id="systemModalTitle">新建系统</span><a href="javascript:void(0)" onclick="hideModal('systemModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitSystemForm(event, 'systemModal')">
                <input type="hidden" name="system_id" id="systemModalId" value="">
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div class="form-group"><label>系统名称 *</label><input type="text" class="form-control" name="name" id="systemModalName" required></div>
                <div class="form-group"><label>Git 链接</label><input type="text" class="form-control" name="git_url" id="systemModalGit" placeholder="https://github.com/..."></div>
                <div class="form-group"><label>描述</label><textarea class="form-control" name="description" id="systemModalDesc"></textarea></div>
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('systemModal')">取消</button><button type="submit" class="btn btn-primary" id="systemModalSubmit">创建</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Module Modal -->
<div id="moduleModal" class="modal-overlay" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-header"><span id="moduleModalTitle">新建模块</span><a href="javascript:void(0)" onclick="hideModal('moduleModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitModuleForm(event, 'moduleModal')">
                <input type="hidden" name="module_id" id="moduleModalId" value="">
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div class="form-group"><label>所属系统 *</label>
                    <select class="form-control" name="system_id" id="moduleModalSystemId" required>
                        <option value="0">请选择系统</option>
                        @foreach ($systems as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group"><label>模块名称 *</label><input type="text" class="form-control" name="name" id="moduleModalName" required></div>
                <div class="form-group"><label>描述</label><textarea class="form-control" name="description" id="moduleModalDesc"></textarea></div>
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('moduleModal')">取消</button><button type="submit" class="btn btn-primary" id="moduleModalSubmit">创建</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Business Process Modal -->
<div id="processModal" class="modal-overlay" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-header"><span id="processModalTitle">新建业务流程</span><a href="javascript:void(0)" onclick="hideModal('processModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitProcessForm(event, 'processModal')">
                <input type="hidden" name="bp_id" id="processModalId" value="">
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div class="form-group"><label>流程名称 *</label><input type="text" class="form-control" name="name" id="processModalName" required></div>
                <div class="form-group"><label>描述</label><textarea class="form-control" name="description" id="processModalDesc"></textarea></div>
                <div class="form-group">
                    <label>发起角色</label>
                    <select class="form-control" name="initiator_role_id" id="processModalRole">
                        <option value="0">不指定</option>
                        @foreach ($project_roles as $r)
                        <option value="{{ $r->id }}">{{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('processModal')">取消</button><button type="submit" class="btn btn-primary" id="processModalSubmit">创建</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Process Node Modal -->
<div id="processNodeModal" class="modal-overlay" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-header"><span id="processNodeModalTitle">新建流程节点</span><a href="javascript:void(0)" onclick="hideModal('processNodeModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitProcessNodeForm(event, 'processNodeModal')">
                <input type="hidden" name="node_id" id="nodeModalId" value="">
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
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('processNodeModal')">取消</button><button type="submit" class="btn btn-primary" id="processNodeModalSubmit">添加节点</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Role Modal -->
<div id="roleModal" class="modal-overlay" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-header"><span id="roleModalTitle">新建角色</span><a href="javascript:void(0)" onclick="hideModal('roleModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitRoleForm(event, 'roleModal')">
                <input type="hidden" name="role_id" id="roleModalId" value="">
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                <div class="form-group"><label>角色名称 *</label><input type="text" class="form-control" name="name" id="roleModalName" required placeholder="如：顾客、商品运营"></div>
                <div class="form-group"><label>描述</label><textarea class="form-control" name="description" id="roleModalDesc" placeholder="角色职责说明"></textarea></div>
                <div class="form-group" id="roleModalModuleSection" style="display: none;">
                    <label>关联模块</label>
                    <div id="roleModalModuleCheckboxes" style="max-height: 240px; overflow-y: auto; border: 1px solid #f0f0f0; border-radius: 6px; padding: 8px 12px;">
                        @php
                            $modules_by_system = [];
                            foreach ($modules as $m) {
                                $modules_by_system[$m->system_id][] = $m;
                            }
                            $system_names = [];
                            foreach ($systems as $s) { $system_names[$s->id] = $s->name; }
                        @endphp
                        @foreach ($systems as $s)
                            @if (!empty($modules_by_system[$s->id]))
                            <div style="margin-bottom: 8px;">
                                <div style="font-size: 12px; color: #999; margin-bottom: 4px;">{{ $s->name }}</div>
                                @foreach ($modules_by_system[$s->id] as $m)
                                <label style="display: inline-flex; align-items: center; margin-right: 12px; margin-bottom: 4px; font-size: 13px; cursor: pointer;">
                                    <input type="checkbox" class="role-module-check" data-role-id="" data-module-id="{{ $m->id }}" value="{{ $m->id }}" style="margin-right: 4px;">
                                    {{ $m->name }}
                                </label>
                                @endforeach
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('roleModal')">取消</button><button type="submit" class="btn btn-primary" id="roleModalSubmit">创建</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Requirement Modal -->
<div id="requirementModal" class="modal-overlay" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-header"><span id="requirementModalTitle">新建需求</span><a href="javascript:void(0)" onclick="hideModal('requirementModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitRequirementForm(event, 'requirementModal')">
                <input type="hidden" name="requirement_id" id="requirementModalId" value="">
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
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('requirementModal')">取消</button><button type="submit" class="btn btn-primary" id="requirementModalSubmit">创建</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Bug Modal -->
<div id="bugModal" class="modal-overlay" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-header"><span id="bugModalTitle">新建 BUG</span><a href="javascript:void(0)" onclick="hideModal('bugModal')">&times;</a></div>
        <div class="modal-body">
            <form onsubmit="submitBugForm(event, 'bugModal')">
                <input type="hidden" name="bug_id" id="bugModalId" value="">
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
                <div class="text-right"><button type="button" class="btn btn-default" onclick="hideModal('bugModal')">取消</button><button type="submit" class="btn btn-primary" id="bugModalSubmit">创建</button></div>
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

function getCurrentTab() {
    var active = document.querySelector('.tab-link.active');
    return active ? active.id.replace('tab-', '') : 'role';
}

function saveCurrentTab() {
    sessionStorage.setItem('activeTab', getCurrentTab());
}

function restoreTab() {
    var tab = sessionStorage.getItem('activeTab');
    if (tab) {
        switchTab(tab);
        sessionStorage.removeItem('activeTab');
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        restoreTab();
        restoreSelectedSystem();
    });
} else {
    restoreTab();
    restoreSelectedSystem();
}

function showModal(id) {
    var modal = document.getElementById(id);
    if (modal) { modal.style.display = 'flex'; }
}

function hideModal(id) {
    var modal = document.getElementById(id);
    if (modal) { modal.style.display = 'none'; }
}

function showError(form, message) {
    var existing = form.querySelector('.form-error');
    if (existing) existing.remove();
    var div = document.createElement('div');
    div.className = 'form-error';
    div.style.cssText = 'background:#fff2f0;border:1px solid #ffccc7;color:#a8071a;padding:8px 12px;border-radius:4px;margin-bottom:12px;font-size:13px;';
    div.textContent = message;
    form.insertBefore(div, form.firstChild);
    setTimeout(function() { div.remove(); }, 5000);
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
            saveCurrentTab();
            location.reload();
        } else {
            showError(form, '创建失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.textContent = '创建';
        showError(form, '网络错误，请重试');
    };
    xhr.send(params.join('&'));
}

function resetRoleModal() {
    document.getElementById('roleModalId').value = '';
    document.getElementById('roleModalTitle').textContent = '新建角色';
    document.getElementById('roleModalSubmit').textContent = '创建';
    document.getElementById('roleModalModuleSection').style.display = 'none';
    document.querySelectorAll('.role-module-check').forEach(function(cb) {
        cb.checked = false;
        cb.dataset.roleId = '';
    });
}

function submitRoleForm(e, modalId) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '提交中...';

    var roleId = document.getElementById('roleModalId').value;
    var url = roleId ? '/api/project_role/update' : '/api/project_role/create';

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
        btn.textContent = '提交中...';
        if (xhr.status === 200) {
            hideModal(modalId);
            saveCurrentTab();
            location.reload();
        } else {
            showError(form, '操作失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.textContent = '提交中...';
        showError(form, '网络错误，请重试');
    };
    xhr.send(params.join('&'));
}

function editRole(id, name, description) {
    document.getElementById('roleModalTitle').textContent = '编辑角色';
    document.getElementById('roleModalSubmit').textContent = '保存';
    document.getElementById('roleModalId').value = id;
    document.getElementById('roleModalName').value = name;
    document.getElementById('roleModalDesc').value = description;
    document.getElementById('roleModalModuleSection').style.display = 'block';

    var linked = {};
    @foreach ($role_modules as $rid => $mids)
    linked[{{ $rid }}] = {{ json_encode($mids) }};
    @endforeach
    var ids = linked[id] || [];

    var checkboxes = document.querySelectorAll('.role-module-check');
    checkboxes.forEach(function(cb) {
        cb.dataset.roleId = id;
        cb.checked = ids.indexOf(parseInt(cb.value)) !== -1;
        cb.onchange = function() {
            toggleRoleModule(parseInt(cb.dataset.roleId), parseInt(cb.value), cb.checked);
        };
    });

    showModal('roleModal');
}

function toggleRoleModule(roleId, moduleId, checked) {
    var url = checked ? '/api/project_role/link_module' : '/api/project_role/unlink_module';
    var params = 'role_id=' + roleId + '&module_id=' + moduleId;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        if (xhr.status !== 200) {
            cb.checked = !checked;
            alert((checked ? '关联' : '取消关联') + '失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.onerror = function() {
        cb.checked = !checked;
        alert('网络错误，请重试');
    };
    var cb = document.querySelector('.role-module-check[data-module-id="' + moduleId + '"]');
    xhr.send(params);
}

function deleteRole(id, name) {
    if (!confirm('确定删除角色「' + name + '」吗？')) return;
    var params = 'role_id=' + encodeURIComponent(id);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/project_role/delete', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        if (xhr.status === 200) {
            saveCurrentTab();
            location.reload();
        } else {
            alert('删除失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.send(params);
}

function submitDiscussion() {
    var textarea = document.getElementById('discussionInput');
    var text = textarea.value.trim();
    if (!text) return;

    document.getElementById('discussionText').textContent = text;
    document.getElementById('discussionEcho').style.display = 'block';
    textarea.value = '';
}

function switchPendingTab(tab) {
    document.querySelectorAll('.pending-content').forEach(function(el) { el.style.display = 'none'; });
    document.querySelectorAll('[id^="pending-tab-"]').forEach(function(el) { el.classList.remove('active'); });
    document.getElementById('pending-content-' + tab).style.display = 'block';
    document.getElementById('pending-tab-' + tab).classList.add('active');
}

function showProcessNodeModal(business_process_id) {
    document.getElementById('node_bp_id').value = business_process_id;
    showModal('processNodeModal');
}

function submitModuleForm(e, modalId) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '创建中...';

    var params = [];
    var inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(function(input) {
        if (input.name && input.type !== 'submit') {
            params.push(encodeURIComponent(input.name) + '=' + encodeURIComponent(input.value));
        }
    });

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/module/create', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        btn.disabled = false;
        btn.textContent = '创建';
        if (xhr.status === 200) {
            hideModal(modalId);
            saveCurrentTab();
            location.reload();
        } else {
            showError(form, '创建失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.textContent = '创建';
        showError(form, '网络错误，请重试');
    };
    xhr.send(params.join('&'));
}

/* ===== Split-pane system/module management ===== */
var selectedSystemId = null;

function saveSelectedSystem() {
    if (selectedSystemId) {
        sessionStorage.setItem('selectedSystemId', selectedSystemId);
    } else {
        sessionStorage.removeItem('selectedSystemId');
    }
}

function restoreSelectedSystem() {
    var systemId = sessionStorage.getItem('selectedSystemId');
    if (systemId) {
        var el = document.querySelector('.system-item[data-system-id="' + systemId + '"]');
        if (el) {
            selectSystem(parseInt(systemId));
        }
        sessionStorage.removeItem('selectedSystemId');
    }
}

function selectSystem(systemId) {
    selectedSystemId = systemId;
    saveSelectedSystem();
    document.querySelectorAll('.system-item').forEach(function(el) {
        el.classList.toggle('active', parseInt(el.dataset.systemId) === systemId);
    });
    document.getElementById('modulePanelTitle').textContent = '模块列表';
    document.getElementById('moduleAddBtn').style.display = 'inline-block';
    document.getElementById('moduleEmpty').style.display = 'none';
    renderModules(systemId);
}

function renderModules(systemId) {
    var container = document.getElementById('moduleList');
    var modules = {};

    @foreach ($modules as $m)
    @if ($m->system_id)
    modules[{{ $m->system_id }}] = modules[{{ $m->system_id }}] || [];
    modules[{{ $m->system_id }}].push({ id: {{ $m->id }}, name: '{{ addslashes($m->name) }}', description: '{{ addslashes($m->description or '') }}' });
    @endif
    @endforeach

    var list = modules[systemId] || [];
    if (list.length === 0) {
        container.innerHTML = '<div class="empty-state" style="padding:32px 20px;"><p>该系统暂无模块</p></div>';
        container.style.display = 'block';
        return;
    }

    var html = '';
    list.forEach(function(m) {
        html += '<div class="module-item">' +
            '<div class="module-info">' +
                '<div class="module-name">' + escapeHtml(m.name) + '</div>' +
                '<div class="module-desc">' + escapeHtml(m.description || '-') + '</div>' +
            '</div>' +
            '<div class="module-actions">' +
                '<a href="javascript:void(0)" class="action-link" onclick="editModule(' + m.id + ',\'' + escapeJs(m.name) + '\',\'' + escapeJs(m.description) + '\')">编辑</a>' +
                '<a href="javascript:void(0)" class="action-link action-link-danger" onclick="deleteModule(' + m.id + ',\'' + escapeJs(m.name) + '\')">删除</a>' +
            '</div>' +
        '</div>';
    });
    container.innerHTML = html;
    container.style.display = 'block';
}

function openSystemModal() {
    document.getElementById('systemModalTitle').textContent = '新建系统';
    document.getElementById('systemModalSubmit').textContent = '创建';
    document.getElementById('systemModalId').value = '';
    document.getElementById('systemModalName').value = '';
    document.getElementById('systemModalGit').value = '';
    document.getElementById('systemModalDesc').value = '';
    showModal('systemModal');
}

function editSystem(id, name, gitUrl, description) {
    document.getElementById('systemModalTitle').textContent = '编辑系统';
    document.getElementById('systemModalSubmit').textContent = '保存';
    document.getElementById('systemModalId').value = id;
    document.getElementById('systemModalName').value = name;
    document.getElementById('systemModalGit').value = gitUrl;
    document.getElementById('systemModalDesc').value = description;
    showModal('systemModal');
}

function submitSystemForm(e, modalId) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '提交中...';

    var systemId = document.getElementById('systemModalId').value;
    var url = systemId ? '/api/system/update' : '/api/system/create';

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
        btn.textContent = '提交中...';
        if (xhr.status === 200) {
            hideModal(modalId);
            saveCurrentTab();
            location.reload();
        } else {
            showError(form, '操作失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.textContent = '提交中...';
        showError(form, '网络错误，请重试');
    };
    xhr.send(params.join('&'));
}

function deleteSystem(id, name) {
    if (!confirm('确定删除系统「' + name + '」吗？该系统的所有模块也将被删除。')) return;
    var params = 'system_id=' + encodeURIComponent(id);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/system/delete', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        if (xhr.status === 200) {
            if (selectedSystemId === id) {
                selectedSystemId = null;
                document.getElementById('modulePanelTitle').textContent = '选择一个系统查看模块';
                document.getElementById('moduleAddBtn').style.display = 'none';
                document.getElementById('moduleList').style.display = 'none';
                document.getElementById('moduleEmpty').style.display = 'block';
            }
            saveCurrentTab();
            location.reload();
        } else {
            alert('删除失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.send(params);
}

function openModuleModal() {
    if (!selectedSystemId) { alert('请先在左侧选择一个系统'); return; }
    document.getElementById('moduleModalTitle').textContent = '新建模块';
    document.getElementById('moduleModalSubmit').textContent = '创建';
    document.getElementById('moduleModalId').value = '';
    document.getElementById('moduleModalSystemId').value = selectedSystemId;
    document.getElementById('moduleModalName').value = '';
    document.getElementById('moduleModalDesc').value = '';
    showModal('moduleModal');
}

function editModule(id, name, description) {
    document.getElementById('moduleModalTitle').textContent = '编辑模块';
    document.getElementById('moduleModalSubmit').textContent = '保存';
    document.getElementById('moduleModalId').value = id;
    document.getElementById('moduleModalName').value = name;
    document.getElementById('moduleModalDesc').value = description;
    showModal('moduleModal');
}

function submitModuleForm(e, modalId) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '提交中...';

    var moduleId = document.getElementById('moduleModalId').value;
    var url = moduleId ? '/api/module/update' : '/api/module/create';

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
        btn.textContent = '提交中...';
        if (xhr.status === 200) {
            hideModal(modalId);
            saveCurrentTab();
            location.reload();
        } else {
            showError(form, '操作失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.textContent = '提交中...';
        showError(form, '网络错误，请重试');
    };
    xhr.send(params.join('&'));
}

function deleteModule(id, name) {
    if (!confirm('确定删除模块「' + name + '」吗？')) return;
    var params = 'module_id=' + encodeURIComponent(id);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/module/delete', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        if (xhr.status === 200) {
            saveCurrentTab();
            location.reload();
        } else {
            alert('删除失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.send(params);
}

function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function escapeJs(str) {
    return str.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"').replace(/\n/g, '\\n').replace(/\r/g, '');
}

function submitProcessNodeForm(e, modalId) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '提交中...';

    var nodeId = document.getElementById('nodeModalId').value;
    var url = nodeId ? '/api/process_node/update' : '/api/process_node/create';

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
        btn.textContent = '提交中...';
        if (xhr.status === 200) {
            hideModal(modalId);
            saveCurrentTab();
            location.reload();
        } else {
            showError(form, '操作失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.textContent = '提交中...';
        showError(form, '网络错误，请重试');
    };
    xhr.send(params.join('&'));
}

function editProcessNode(id, name, description, sortOrder, projectRoleId) {
    document.getElementById('processNodeModalTitle').textContent = '编辑流程节点';
    document.getElementById('processNodeModalSubmit').textContent = '保存';
    document.getElementById('nodeModalId').value = id;
    var form = document.querySelector('#processNodeModal form');
    form.querySelector('input[name="name"]').value = name;
    form.querySelector('textarea[name="description"]').value = description;
    form.querySelector('input[name="sort_order"]').value = sortOrder;
    form.querySelector('select[name="project_role_id"]').value = projectRoleId;
    showModal('processNodeModal');
}

function deleteProcessNode(id, name) {
    if (!confirm('确定删除流程节点「' + name + '」吗？')) return;
    var params = 'node_id=' + encodeURIComponent(id);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/process_node/delete', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        if (xhr.status === 200) {
            saveCurrentTab();
            location.reload();
        } else {
            alert('删除失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.send(params);
}

function submitProcessForm(e, modalId) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '提交中...';

    var bpId = document.getElementById('processModalId').value;
    var url = bpId ? '/api/business_process/update' : '/api/business_process/create';

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
        btn.textContent = '提交中...';
        if (xhr.status === 200) {
            hideModal(modalId);
            saveCurrentTab();
            location.reload();
        } else {
            showError(form, '操作失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.textContent = '提交中...';
        showError(form, '网络错误，请重试');
    };
    xhr.send(params.join('&'));
}

function editProcess(id, name, description, initiatorRoleId) {
    document.getElementById('processModalTitle').textContent = '编辑业务流程';
    document.getElementById('processModalSubmit').textContent = '保存';
    document.getElementById('processModalId').value = id;
    document.getElementById('processModalName').value = name;
    document.getElementById('processModalDesc').value = description;
    document.getElementById('processModalRole').value = initiatorRoleId;
    showModal('processModal');
}

function deleteProcess(id, name) {
    if (!confirm('确定删除业务流程「' + name + '」吗？该流程下的节点也将被删除。')) return;
    var params = 'bp_id=' + encodeURIComponent(id);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/business_process/delete', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        if (xhr.status === 200) {
            saveCurrentTab();
            location.reload();
        } else {
            alert('删除失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.send(params);
}

function submitRequirementForm(e, modalId) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '提交中...';

    var requirementId = document.getElementById('requirementModalId').value;
    var url = requirementId ? '/api/requirement/update' : '/api/requirement/create';

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
        btn.textContent = '提交中...';
        if (xhr.status === 200) {
            hideModal(modalId);
            saveCurrentTab();
            location.reload();
        } else {
            showError(form, '操作失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.textContent = '提交中...';
        showError(form, '网络错误，请重试');
    };
    xhr.send(params.join('&'));
}

function editRequirement(id, projectId, systemId, moduleId, roleId, name, description) {
    document.getElementById('requirementModalTitle').textContent = '编辑需求';
    document.getElementById('requirementModalSubmit').textContent = '保存';
    document.getElementById('requirementModalId').value = id;
    var form = document.querySelector('#requirementModal form');
    form.querySelector('input[name="name"]').value = name;
    form.querySelector('textarea[name="description"]').value = description;
    form.querySelector('select[name="system_id"]').value = systemId;
    form.querySelector('select[name="module_id"]').value = moduleId;
    form.querySelector('select[name="role_id"]').value = roleId;
    showModal('requirementModal');
}

function deleteRequirement(id, name) {
    if (!confirm('确定删除需求「' + name + '」吗？')) return;
    var params = 'requirement_id=' + encodeURIComponent(id);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/requirement/delete', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        if (xhr.status === 200) {
            saveCurrentTab();
            location.reload();
        } else {
            alert('删除失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.send(params);
}

function submitBugForm(e, modalId) {
    e.preventDefault();
    var form = e.target;
    var btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '提交中...';

    var bugId = document.getElementById('bugModalId').value;
    var url = bugId ? '/api/bug/update' : '/api/bug/create';

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
        btn.textContent = '提交中...';
        if (xhr.status === 200) {
            hideModal(modalId);
            saveCurrentTab();
            location.reload();
        } else {
            showError(form, '操作失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.onerror = function() {
        btn.disabled = false;
        btn.textContent = '提交中...';
        showError(form, '网络错误，请重试');
    };
    xhr.send(params.join('&'));
}

function editBug(id, projectId, requirementId, roleId, name, description) {
    document.getElementById('bugModalTitle').textContent = '编辑 BUG';
    document.getElementById('bugModalSubmit').textContent = '保存';
    document.getElementById('bugModalId').value = id;
    var form = document.querySelector('#bugModal form');
    form.querySelector('input[name="name"]').value = name;
    form.querySelector('textarea[name="description"]').value = description;
    form.querySelector('select[name="requirement_id"]').value = requirementId;
    form.querySelector('select[name="role_id"]').value = roleId;
    showModal('bugModal');
}

function deleteBug(id, name) {
    if (!confirm('确定删除 BUG「' + name + '」吗？')) return;
    var params = 'bug_id=' + encodeURIComponent(id);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/api/bug/delete', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onload = function() {
        if (xhr.status === 200) {
            saveCurrentTab();
            location.reload();
        } else {
            alert('删除失败：' + (xhr.responseText || '未知错误'));
        }
    };
    xhr.send(params);
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
.modal-body .text-right .btn:not(:last-child) { margin-right: 8px; }

/* Split pane */
.split-pane { display: flex; border: 1px solid #f0f0f0; border-radius: 8px; background: #fff; min-height: 400px; }
.split-left { width: 35%; border-right: 1px solid #f0f0f0; display: flex; flex-direction: column; background: #fafafa; }
.split-right { width: 65%; display: flex; flex-direction: column; }
.split-panel-header { padding: 14px 16px; border-bottom: 1px solid #f0f0f0; font-weight: 600; font-size: 14px; display: flex; justify-content: space-between; align-items: center; background: #fff; }
.split-panel-body { flex: 1; overflow-y: auto; padding: 8px; }
.system-item { padding: 12px 14px; border-radius: 6px; cursor: pointer; margin-bottom: 4px; border: 1px solid transparent; transition: all .15s; }
.system-item:hover { background: #e6f7ff; border-color: #91d5ff; }
.system-item.active { background: #e6f7ff; border-color: #1890ff; }
.system-item-name { font-size: 14px; font-weight: 500; color: #333; margin-bottom: 4px; }
.system-item-meta { font-size: 12px; color: #999; }
.system-item-actions { margin-top: 6px; display: flex; gap: 12px; }
.action-link { font-size: 12px; color: #1890ff; cursor: pointer; text-decoration: none; }
.action-link:hover { text-decoration: underline; }
.action-link-danger { color: #ff4d4f; }
.module-item { display: flex; align-items: flex-start; padding: 10px 14px; border-bottom: 1px solid #f5f5f5; transition: background .15s; }
.module-item:last-child { border-bottom: none; }
.module-item:hover { background: #f5f7fa; }
.module-info { flex: 1; min-width: 0; }
.module-name { font-size: 14px; font-weight: 500; color: #333; margin-bottom: 2px; }
.module-desc { font-size: 12px; color: #999; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.module-actions { display: flex; gap: 10px; flex-shrink: 0; margin-left: 12px; padding-top: 2px; }

@media (max-width: 768px) {
    .split-pane { flex-direction: column; }
    .split-left, .split-right { width: 100%; }
    .split-left { border-right: none; border-bottom: 1px solid #f0f0f0; max-height: 200px; }
}
</style>

@include('layout/app_footer')
