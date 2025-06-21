    <?php if (isset($_SESSION['user_id'])): ?>
        </div> <!-- End main content wrapper -->
    </div> <!-- End flex container -->
    <?php endif; ?>

    <script>
        // Dropdown toggle function
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }
        
        // Sidebar toggle function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (sidebar.classList.contains('-translate-x-full')) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const button = event.target.closest('button');
            if (!button || !button.onclick) {
                dropdown.classList.add('hidden');
            }
        });

        // Close sidebar on window resize if desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 1024) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebarOverlay');
                if (sidebar) {
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.add('hidden');
                }
            }
        });
    </script>
    <script>
        // Load dashboard stats
        if (document.getElementById('today-sales')) {
            loadDashboardStats();
        }
        
        async function loadDashboardStats() {
            try {
                const response = await fetch('/sari/api/dashboard-stats');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('today-sales').textContent = '₱' + parseFloat(data.today_sales).toFixed(2);
                    document.getElementById('low-stock').textContent = data.low_stock_count;
                }
            } catch (error) {
                console.log('Failed to load dashboard stats:', error);
            }
        }
    </script>
</body>
</html>