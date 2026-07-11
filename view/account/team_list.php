@php
$secondary_items = [
    ['label' => '我的团队', 'href' => '/account/team', 'icon' => '&#9776;', 'active' => true],
    ['label' => '创建团队', 'href' => '/account/team/create', 'icon' => '&#43;'],
];
@endphp
@include('layout/app')

<div class="page-top-bar">
    <h2>我的团队</h2>
    <a href="/account/team/create" class="btn btn-primary">创建团队</a>
</div>

@if (!empty($teams) && count($teams) > 0)
    <div class="team-grid">
        @foreach ($teams as $team)
            <div class="team-card" onclick="location.href='/account/team/{{ $team->id }}'">
                <h3>{{ $team->name }}</h3>
                <p>{{ $team->description or '暂无描述' }}</p>
                <div class="meta">
                    <span>创建于 {{ $team->create_time }}</span>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <p>还没有加入任何团队</p>
        <a href="/account/team/create" class="btn btn-primary mt-16">创建第一个团队</a>
    </div>
@endif
