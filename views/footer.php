    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        // Load dashboard stats
        if (document.getElementById('today-sales')) {
            loadDashboardStats();
        }
        
        function loadDashboardStats() {
            fetch('api/dashboard_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('today-sales').textContent = '₱' + parseFloat(data.today_sales).toFixed(2);
                        document.getElementById('low-stock').textContent = data.low_stock_count;
                    }
                })
                .catch(error => {
                    // Stats loading failed - fail silently for better UX
                });
        }
    </script>
</body>
</html>