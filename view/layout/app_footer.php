    </main>
</div>
<script>
function toggleTeamDropdown() {
    var dd = document.getElementById('teamDropdown');
    if (dd) dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
}
function switchTeam(teamId) {
    fetch('/api/team/switch', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'team_id=' + encodeURIComponent(teamId) + '&VAR_AJAX_SUBMIT=1'
    }).then(function(r) { return r.json(); }).then(function(d) {
        if (d.code === 0 && d.data.redirect) {
            location.href = d.data.redirect;
        }
    });
}
document.addEventListener('click', function(e) {
    var sw = document.getElementById('teamSwitcher');
    if (sw && !sw.contains(e.target)) {
        var dd = document.getElementById('teamDropdown');
        if (dd) dd.style.display = 'none';
    }
});
</script>
</body>
</html>
