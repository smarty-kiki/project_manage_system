@php $is_auth = true; @endphp
@include('layout/app')

<div class="auth-card">
    <h2>项目管理</h2>
        <div id="errorMsg" class="alert alert-error" style="display:none;"></div>
        <div id="successMsg" class="alert alert-success" style="display:none;"></div>

        <form id="sendCodeForm">
            <div class="form-group">
                <label>邮箱地址</label>
                <input type="email" name="email" class="form-control" placeholder="请输入邮箱" required id="emailInput">
            </div>
            <button type="button" class="btn btn-primary" style="width:100%" id="sendCodeBtn" onclick="handleSendCode()">获取验证码</button>
        </form>

        <form id="verifyCodeForm" class="mt-16">
            <div class="form-group">
                <label>验证码</label>
                <input type="text" name="code" class="form-control" placeholder="请输入6位验证码" required id="codeInput" maxlength="6">
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%" id="submitBtn" onclick="handleVerifyCode()">进入</button>
        </form>

        <div class="auth-footer">
            <p style="color:#999;font-size:13px;">输入邮箱并验证即可自动创建账号或登录</p>
        </div>
    </div>

<script>
(function() {
    var emailInput = document.getElementById('emailInput');
    var codeInput = document.getElementById('codeInput');
    var sendCodeBtn = document.getElementById('sendCodeBtn');
    var submitBtn = document.getElementById('submitBtn');
    var errorMsg = document.getElementById('errorMsg');
    var successMsg = document.getElementById('successMsg');

    var countdown = 0;

    function showError(msg) {
        errorMsg.textContent = msg;
        errorMsg.style.display = 'block';
        successMsg.style.display = 'none';
    }

    function showSuccess(msg) {
        successMsg.textContent = msg;
        successMsg.style.display = 'block';
        errorMsg.style.display = 'none';
    }

    function clearMessages() {
        errorMsg.style.display = 'none';
        successMsg.style.display = 'none';
    }

    emailInput.addEventListener('input', clearMessages);
    codeInput.addEventListener('input', clearMessages);

    window.handleSendCode = function() {
        var email = emailInput.value.trim();
        if (!email) {
            showError('请输入邮箱地址');
            return;
        }

        clearMessages();
        sendCodeBtn.disabled = true;
        sendCodeBtn.textContent = '发送中...';

        fetch('/api/account/send_code', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: 'email=' + encodeURIComponent(email)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code === 0) {
                showSuccess(data.data.message || '验证码已发送');
                startCountdown();
            } else {
                showError(data.msg || '发送失败');
                sendCodeBtn.disabled = false;
                sendCodeBtn.textContent = '获取验证码';
            }
        })
        .catch(function() {
            showError('网络错误');
            sendCodeBtn.disabled = false;
            sendCodeBtn.textContent = '获取验证码';
        });
    };

    window.handleVerifyCode = function() {
        var email = emailInput.value.trim();
        var code = codeInput.value.trim();
        if (!email || !code) {
            showError('请填写邮箱和验证码');
            return;
        }

        clearMessages();
        submitBtn.disabled = true;
        submitBtn.textContent = '验证中...';

        fetch('/api/account/verify_code', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: 'email=' + encodeURIComponent(email) + '&code=' + encodeURIComponent(code)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code === 0) {
                window.location.href = data.data.redirect || '/account/team';
            } else {
                showError(data.msg || '验证失败');
                submitBtn.disabled = false;
                submitBtn.textContent = '进入';
            }
        })
        .catch(function() {
            showError('网络错误');
            submitBtn.disabled = false;
            submitBtn.textContent = '进入';
        });
    };

    function startCountdown() {
        countdown = 60;
        sendCodeBtn.disabled = true;
        sendCodeBtn.textContent = countdown + '秒后可重发';
        var timer = setInterval(function() {
            countdown--;
            if (countdown <= 0) {
                clearInterval(timer);
                sendCodeBtn.disabled = false;
                sendCodeBtn.textContent = '获取验证码';
            } else {
                sendCodeBtn.textContent = countdown + '秒后可重发';
            }
        }, 1000);
    }
})();
</script>
</body>
</html>
