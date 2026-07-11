@include('layout/app')

<div class="auth-page">
    <div class="auth-card">
        <h2>注册账号</h2>
        @if (!empty($error))
            <div class="alert alert-error">{{ $error }}</div>
        @endif
        <form method="post" action="/api/account/send_code" id="sendCodeForm">
            <div class="form-group">
                <label>邮箱地址</label>
                <input type="email" name="email" class="form-control" placeholder="请输入邮箱" required id="emailInput">
            </div>
            <div class="form-group">
                <label>昵称</label>
                <input type="text" name="nickname" class="form-control" placeholder="请输入昵称" id="nicknameInput">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%" id="sendCodeBtn">获取验证码</button>
        </form>
        <form method="post" action="/api/account/verify_code" id="verifyCodeForm" class="mt-16">
            <input type="hidden" name="type" id="formType" value="register">
            <div class="form-group">
                <label>验证码</label>
                <div class="flex gap-8">
                    <input type="text" name="code" class="form-control" placeholder="请输入6位验证码" required id="codeInput" maxlength="6">
                    <button type="button" class="btn btn-default" id="resendBtn" style="width:120px;flex-shrink:0;" disabled>重新发送</button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%" id="submitBtn">注册并登录</button>
        </form>
        <div class="auth-footer">
            <p>已有账号？<a href="/account/login">立即登录</a></p>
        </div>
    </div>
</div>

<script>
(function() {
    var sendCodeForm = document.getElementById('sendCodeForm');
    var verifyCodeForm = document.getElementById('verifyCodeForm');
    var emailInput = document.getElementById('emailInput');
    var codeInput = document.getElementById('codeInput');
    var formType = document.getElementById('formType');
    var sendCodeBtn = document.getElementById('sendCodeBtn');
    var submitBtn = document.getElementById('submitBtn');
    var resendBtn = document.getElementById('resendBtn');
    var nicknameInput = document.getElementById('nicknameInput');

    var countdown = 0;
    formType.value = 'register';

    sendCodeForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var email = emailInput.value.trim();
        if (!email) { alert('请输入邮箱地址'); return; }

        sendCodeBtn.disabled = true;
        sendCodeBtn.textContent = '发送中...';

        fetch('/api/account/send_code', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'email=' + encodeURIComponent(email) + '&type=register&nickname=' + encodeURIComponent(nicknameInput.value)
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
        submitBtn.textContent = '注册中...';

        fetch('/api/account/verify_code', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'email=' + encodeURIComponent(email) + '&code=' + encodeURIComponent(code) + '&type=register&nickname=' + encodeURIComponent(nicknameInput.value)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code === 0) {
                window.location.href = data.data.redirect || '/account/team';
            } else {
                alert(data.msg || '验证失败');
                submitBtn.disabled = false;
                submitBtn.textContent = '注册并登录';
            }
        })
        .catch(function() {
            alert('网络错误');
            submitBtn.disabled = false;
            submitBtn.textContent = '注册并登录';
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
