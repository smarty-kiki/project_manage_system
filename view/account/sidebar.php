<div class="sidebar">
    <div class="sidebar-header">{{ $user->name or '用户' }}</div>
    <nav class="sidebar-nav">
        <a href="/account/team" class="sidebar-item">
            <span class="icon">&#9776;</span>
            我的团队
        </a>
        <a href="/account/team/create" class="sidebar-item">
            <span class="icon">&#43;</span>
            创建团队
        </a>
        <a href="/account/logout" class="sidebar-item" style="margin-top:auto">
            <span class="icon">&#8594;</span>
            退出登录
        </a>
    </nav>
</div>
