<script>
function toggleSidebar() {
    document.getElementById('admSidebar').classList.toggle('open');
}
// Tutup sidebar klik di luar (mobile)
document.addEventListener('click', function(e) {
    var sb = document.getElementById('admSidebar');
    if (sb && sb.classList.contains('open') &&
        !sb.contains(e.target) &&
        !e.target.closest('#btnMenuToggle')) {
        sb.classList.remove('open');
    }
});
</script>
</body>
</html>
