@php $is_auth = true; @endphp
@include('layout/app')

<div class="auth-card">
    <h2>设置姓名</h2>
    <div id="errorMsg" class="alert alert-error" style="display:none;"></div>
    <div id="successMsg" class="alert alert-success" style="display:none;"></div>
    <p style="color:#666;text-align:center;margin-bottom:20px;font-size:14px;">请设置您的姓名，以便团队成员识别</p>
    <form id="setNameForm" onsubmit="handleSetName();return false;">
        <div class="form-group">
            <label>姓名 <span style="color:red">*</span></label>
            <input type="text" name="name" class="form-control" placeholder="请输入您的姓名" required id="nameInput" autofocus>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%" id="submitBtn">确认</button>
    </form>
</div>

<script>
(function() {
    var nameInput = document.getElementById('nameInput');
    var submitBtn = document.getElementById('submitBtn');
    var errorMsg = document.getElementById('errorMsg');
    var successMsg = document.getElementById('successMsg');

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

    nameInput.addEventListener('input', clearMessages);

    window.handleSetName = function() {
        var name = nameInput.value.trim();
        if (!name) {
            showError('请输入姓名');
            return;
        }

        clearMessages();
        submitBtn.disabled = true;
        submitBtn.textContent = '保存中...';

        fetch('/api/account/set_name', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: 'name=' + encodeURIComponent(name)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code === 0) {
                window.location.href = data.data.redirect || '/account/team';
            } else {
                showError(data.msg || '保存失败');
                submitBtn.disabled = false;
                submitBtn.textContent = '确认';
            }
        })
        .catch(function() {
            showError('网络错误');
            submitBtn.disabled = false;
            submitBtn.textContent = '确认';
        });
    };
})();
</script>
</body>
</html>
