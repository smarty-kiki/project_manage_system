@include('layout/app')

<div class="auth-page">
    <div class="auth-card">
        <h2>设置姓名</h2>
        @if (!empty($error))
            <div class="alert alert-error">{{ $error }}</div>
        @endif
        <p style="color:#666;text-align:center;margin-bottom:20px;font-size:14px;">请设置您的姓名，以便团队成员识别</p>
        <form method="post" action="/api/account/set_name" id="setNameForm">
            <div class="form-group">
                <label>姓名 <span style="color:red">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="请输入您的姓名" required id="nameInput" autofocus>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%" id="submitBtn">确认</button>
        </form>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('setNameForm');
    var nameInput = document.getElementById('nameInput');
    var submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var name = nameInput.value.trim();
        if (!name) { alert('请输入姓名'); return; }

        submitBtn.disabled = true;
        submitBtn.textContent = '保存中...';

        fetch('/api/account/set_name', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'name=' + encodeURIComponent(name)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.code === 0) {
                window.location.href = data.data.redirect || '/account/team';
            } else {
                alert(data.msg || '保存失败');
                submitBtn.disabled = false;
                submitBtn.textContent = '确认';
            }
        })
        .catch(function() {
            alert('网络错误');
            submitBtn.disabled = false;
            submitBtn.textContent = '确认';
        });
    });
})();
</script>
