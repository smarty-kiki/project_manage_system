@php $title = '404 - 页面不存在'; $is_auth = true; @endphp
@include('layout/app')

<div class="auth-card" style="text-align:center;padding:60px 40px;">
    <div style="font-size:64px;font-weight:700;color:#d9d9d9;margin-bottom:16px;">404</div>
    <h2 style="color:#333;margin-bottom:8px;">页面不存在</h2>
    <p style="color:#999;margin-bottom:24px;">您访问的页面不存在或已被移除</p>
    <a href="/" class="btn btn-primary">返回首页</a>
</div>

@include('layout/app_footer')
