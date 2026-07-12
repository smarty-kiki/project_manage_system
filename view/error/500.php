@php $title = '500 - 服务器错误'; $is_auth = true; @endphp
@include('layout/app')

<div class="auth-card" style="text-align:center;padding:60px 40px;">
    <div style="font-size:64px;font-weight:700;color:#d9d9d9;margin-bottom:16px;">500</div>
    <h2 style="color:#333;margin-bottom:8px;">服务器错误</h2>
    <p style="color:#999;margin-bottom:24px;">服务暂时出了点问题，请稍后再试</p>
    @if (isset($code))
    <div style="background:#fff2f0;border:1px solid #ffccc7;color:#a8071a;padding:8px 16px;border-radius:4px;margin-bottom:24px;font-size:13px;">
        {{ $code }}: {{ $message }}
    </div>
    @endif
    <a href="/" class="btn btn-primary">返回首页</a>
</div>

@include('layout/app_footer')
