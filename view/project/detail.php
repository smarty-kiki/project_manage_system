@php
$secondary_items = [];
@endphp
@include('layout/app')

<div class="page-top-bar">
    <h2>{{ $project->name or '项目' }}</h2>
    <a href="/team/{{ $team->id }}/project" class="btn btn-default btn-sm">&larr; 返回项目列表</a>
</div>

<!-- Project Overview Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <div class="card" style="padding: 16px 20px;">
        <div style="color: #999; font-size: 13px; margin-bottom: 8px;">项目名称</div>
        <div style="font-size: 18px; font-weight: 600; color: #333;">{{ $project->name }}</div>
    </div>
    <div class="card" style="padding: 16px 20px;">
        <div style="color: #999; font-size: 13px; margin-bottom: 8px;">状态</div>
        <div style="font-size: 18px; font-weight: 600; color: #52c41a;">运行中</div>
    </div>
    <div class="card" style="padding: 16px 20px;">
        <div style="color: #999; font-size: 13px; margin-bottom: 8px;">创建时间</div>
        <div style="font-size: 16px; font-weight: 600; color: #333;">{{ $project->create_time }}</div>
    </div>
    <div class="card" style="padding: 16px 20px;">
        <div style="color: #999; font-size: 13px; margin-bottom: 8px;">最近更新</div>
        <div style="font-size: 16px; font-weight: 600; color: #333;">{{ $project->update_time }}</div>
    </div>
</div>

<!-- Project Description -->
<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">项目描述</div>
    <div class="card-body">
        <p style="color: #666; line-height: 1.8;">{{ $project->description or '暂无描述' }}</p>
    </div>
</div>

<!-- Activity & Stats Section -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header">最近动态</div>
        <div class="card-body">
            <div class="empty-state" style="padding: 32px 20px;">
                <p>暂无活动记录</p>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="card">
        <div class="card-header">项目统计</div>
        <div class="card-body">
            <table class="member-table" style="margin-top: 8px;">
                <tr>
                    <td style="color: #999;">创建者</td>
                    <td style="text-align: right;">{{ $project->creator_id }}</td>
                </tr>
                <tr>
                    <td style="color: #999;">所属团队</td>
                    <td style="text-align: right;">{{ $team->name }}</td>
                </tr>
                <tr>
                    <td style="color: #999;">项目状态</td>
                    <td style="text-align: right;"><span style="color: #52c41a;">启用</span></td>
                </tr>
                <tr>
                    <td style="color: #999;">项目 ID</td>
                    <td style="text-align: right; color: #999;">{{ $project->id }}</td>
                </tr>
            </table>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

@include('layout/app_footer')
