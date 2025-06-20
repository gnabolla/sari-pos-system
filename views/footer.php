    <!-- Offline Status Indicator -->
    <div id="offline-indicator" class="position-fixed bottom-0 start-50 translate-middle-x mb-3" style="display: none; z-index: 9999;">
        <div class="alert alert-warning d-flex align-items-center">
            <i class="bi bi-wifi-off me-2"></i>
            <span>You're offline. Data will sync when connection is restored.</span>
            <button id="sync-now-btn" class="btn btn-sm btn-outline-primary ms-3" style="display: none;">
                <i class="bi bi-arrow-repeat me-1"></i>Sync Now
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/sari/assets/js/app.js"></script>
    <script>
        // PWA Service Worker Registration
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sari/sw.js')
                    .then(registration => {
                        console.log('SW registered: ', registration);
                    })
                    .catch(registrationError => {
                        console.log('SW registration failed: ', registrationError);
                    });
            });
        }

        // Network Status Monitoring
        function updateNetworkStatus() {
            const indicator = document.getElementById('offline-indicator');
            const syncBtn = document.getElementById('sync-now-btn');
            
            if (!navigator.onLine) {
                indicator.style.display = 'block';
                syncBtn.style.display = 'none';
            } else {
                indicator.style.display = 'none';
                syncBtn.style.display = 'inline-block';
            }
        }

        // Listen for network changes
        window.addEventListener('online', updateNetworkStatus);
        window.addEventListener('offline', updateNetworkStatus);
        
        // Initial status check
        updateNetworkStatus();

        // Sync button handler
        document.getElementById('sync-now-btn').addEventListener('click', () => {
            if (window.syncManager) {
                window.syncManager.forceSyncNow();
            }
        });

        // Load dashboard stats (with offline fallback)
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
                    
                    // Cache the stats for offline use
                    if (window.offlineStorage) {
                        await window.offlineStorage.saveSetting('dashboard_stats', data);
                    }
                }
            } catch (error) {
                // Try to load cached stats if offline
                if (window.offlineStorage) {
                    try {
                        const cachedStats = await window.offlineStorage.getSetting('dashboard_stats');
                        if (cachedStats && cachedStats.success) {
                            document.getElementById('today-sales').textContent = '₱' + parseFloat(cachedStats.today_sales).toFixed(2);
                            document.getElementById('low-stock').textContent = cachedStats.low_stock_count;
                        }
                    } catch (cacheError) {
                        console.log('Failed to load cached stats');
                    }
                }
            }
        }

        // PWA Install Prompt
        let deferredPrompt;
        
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            
            // Show install button (you can customize this)
            const installBtn = document.createElement('button');
            installBtn.className = 'btn btn-success btn-sm position-fixed top-0 end-0 m-3';
            installBtn.innerHTML = '<i class="bi bi-download me-1"></i>Install App';
            installBtn.style.zIndex = '9999';
            
            installBtn.addEventListener('click', () => {
                deferredPrompt.prompt();
                deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        console.log('User accepted the install prompt');
                        installBtn.remove();
                    }
                    deferredPrompt = null;
                });
            });
            
            document.body.appendChild(installBtn);
        });
    </script>
</body>
</html>