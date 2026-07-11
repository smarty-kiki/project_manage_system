@include('layout/app')

<div class="auth-page">
    <div class="auth-card">
        <h2>项目管理</h2>
        @if (!empty($error))
            <div class="alert alert-error">{{ $error }}</div>
        @endif
        @if (!empty($message))
            <div class="alert alert-success">{{ $message }}</div>
        @endif
        <form method="post" action="/api/account/send_code" id="sendCodeForm">
            <div class="form-group">
                <label>邮箱地址</label>
                <input type="email" name="email" class="form-control" placeholder="请输入邮箱" required id="emailInput">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%" id="sendCodeBtn">获取验证码</button>
        </form>
        <form method="post" action="/api/account/verify_code" id="verifyCodeForm" class="mt-16">
            <div class="form-group">
                <label>验证码</label>
                <div class="flex gap-8">
                    <input type="text" name="code" class="form-control" placeholder="请输入6位验证码" required id="codeInput" maxlength="6">
                    <button type="button" class="btn btn-default" id="resendBtn" style="width:120px;flex-shrink:0;" disabled>重新发送</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%" id="submitBtn">进入</button>
        </form>
        <div class="auth-footer">
            <p style="color:#999;font-size:13px;">输入邮箱并验证即可自动创建账号或登录</p>
        </div>
    </div>
</div>

<script>
(function() {
    var sendCodeForm = document.getElementById('sendCodeForm');
    var verifyCodeForm = document.getElementById('verifyCodeForm');
    var emailInput = document.getElementById('emailInput');
    var codeInput = document.getElementById('codeInput');
    var sendCodeBtn = document.getElementById('sendCodeBtn');
    var submitBtn = document.getElementById('submitBtn');
    var resendBtn = document.getElementById('resendBtn');

    var countdown = 0;

    sendCodeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var email = emailInput.value.trim();
        if (!email) { alert('请输入邮箱地址'); return; }

        sendCodeBtn.disabled = true;
        sendCodeBtn.textContent = '发送中...';

        fetch('/api/account/send_code', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'email=' + encodeURIComponent(email)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code === 0) {
                alert(data.msg || '验证码已发送');
                startCountdown();
            } else {
                alert(data.msg || '发送失败');
                sendCodeBtn.disabled = false;
                sendCodeBtn.textContent = '获取验证码';
            }
        })
        .catch(function() {
            alert('网络错误');
            sendCodeBtn.disabled = false;
            sendCodeBtn.textContent = '获取验证码';
        });
    });

    verifyCodeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var email = emailInput.value.trim();
        var code = codeInput.value.trim();
        if (!email || !code) { alert('请填写邮箱和验证码'); return; }

        submitBtn.disabled = true;
        submitBtn.textContent = '验证中...';

        fetch('/api/account/verify_code', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'email=' + encodeURIComponent(email) + '&code=' + encodeURIComponent(code)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code === 0) {
                window.location.href = data.data.redirect || '/account/team';
            } else {
                alert(data.msg || '验证失败');
                submitBtn.disabled = false;
                submitBtn.textContent = '进入';
            }
        })
        .catch(function() {
            alert('网络错误');
            submitBtn.disabled = false;
            submitBtn.textContent = '进入';
        });
    });

    resendBtn.addEventListener('click', function() {
        sendCodeForm.dispatchEvent(new Event('submit'));
    });

    function startCountdown() {
        countdown = 60;
        sendCodeBtn.disabled = true;
        resendBtn.disabled = true;
        sendCodeBtn.textContent = countdown + '秒后可重发';
        var timer = setInterval(function() {
            countdown--;
            if (countdown <= 0) {
                clearInterval(timer);
                sendCodeBtn.disabled = false;
                resendBtn.disabled = false;
                sendCodeBtn.textContent = '获取验证码';
                resendBtn.textContent = '重新发送';
            } else {
                sendCodeBtn.textContent = countdown + '秒后可重发';
                resendBtn.textContent = countdown + '秒';
            }
        }, 1000);
    }
})();
</script>
