</div> <!-- Close main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    AOS.init({
        duration: 500,
        once: true,
        offset: 30
    });
    
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    }
    
    if(localStorage.getItem('sidebarCollapsed') === 'true') {
        document.getElementById('sidebar')?.classList.add('collapsed');
        document.getElementById('mainContent')?.classList.add('expanded');
    }
    
    function showNotification(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = 'notification-toast';
        const icon = type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation';
        toast.innerHTML = `<i class="fa-regular ${icon}"></i><span>${message}</span>`;
        document.body.appendChild(toast);
        setTimeout(() => toast.classList.add('show'), 100);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    function confirmDelete(url) {
        if(confirm('⚠️ Are you sure you want to delete this? This action cannot be undone!')) {
            window.location.href = url;
        }
    }
    
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s';
            setTimeout(() => alert.remove(), 300);
        }, 4000);
    });
</script>
</body>
</html>