class SyncManager {
    constructor(offlineStorage, networkManager) {
        this.offlineStorage = offlineStorage;
        this.networkManager = networkManager;
        this.syncInProgress = false;
        this.syncInterval = null;
        
        // Listen for network status changes
        this.networkManager.onStatusChange((status) => {
            if (status === 'online') {
                this.startAutoSync();
            } else {
                this.stopAutoSync();
            }
        });
        
        // Start auto sync if online
        if (this.networkManager.isOnline) {
            this.startAutoSync();
        }
        
        // Listen for service worker messages
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data.type === 'SYNC_COMPLETE') {
                    this.showSyncNotification('Data synced successfully!');
                }
            });
        }
    }
    
    startAutoSync() {
        if (this.syncInterval) return;
        
        // Sync immediately
        this.syncOfflineData();
        
        // Then sync every 30 seconds
        this.syncInterval = setInterval(() => {
            this.syncOfflineData();
        }, 30000);
    }
    
    stopAutoSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
        }
    }
    
    async syncOfflineData() {
        if (this.syncInProgress || !this.networkManager.isOnline) {
            return;
        }
        
        this.syncInProgress = true;
        
        try {
            const offlineSales = await this.offlineStorage.getOfflineSales();
            const syncQueue = await this.offlineStorage.getSyncQueue();
            
            // Only sync if there's data to sync
            if (offlineSales.length === 0 && syncQueue.length === 0) {
                return; // Nothing to sync
            }
            
            let syncedSales = 0;
            let syncedItems = 0;
            
            // Sync offline sales
            for (const sale of offlineSales) {
                if (!sale.synced) {
                    const success = await this.syncSale(sale);
                    if (success) syncedSales++;
                }
            }
            
            // Process sync queue
            for (const item of syncQueue) {
                const success = await this.processSyncQueueItem(item);
                if (success) syncedItems++;
            }
            
            // Only show notification if something was actually synced
            if (syncedSales > 0 || syncedItems > 0) {
                this.showSyncNotification(`Synced ${syncedSales} sales and ${syncedItems} items`);
            }
            
        } catch (error) {
            console.error('Sync failed:', error);
            // Only show error if there was actually data to sync
            const hasDataToSync = await this.hasDataToSync();
            if (hasDataToSync) {
                this.showSyncNotification('Sync failed. Will retry later.', 'error');
            }
        } finally {
            this.syncInProgress = false;
        }
    }
    
    async syncSale(sale) {
        try {
            const response = await fetch('/sari/api/sync-sale', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(sale)
            });
            
            if (response.ok) {
                const result = await response.json();
                await this.offlineStorage.removeSyncedSale(sale.id);
                console.log('Sale synced:', result.transaction_number);
                return true;
            } else {
                console.error('Failed to sync sale:', response.statusText);
                return false;
            }
        } catch (error) {
            console.error('Error syncing sale:', error);
            return false;
        }
    }
    
    async processSyncQueueItem(item) {
        try {
            let endpoint = '';
            
            switch (item.type) {
                case 'product_update':
                    endpoint = '/sari/api/sync-product';
                    break;
                case 'inventory_update':
                    endpoint = '/sari/api/sync-inventory';
                    break;
                default:
                    console.log('Unknown sync type:', item.type);
                    return false;
            }
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(item.data)
            });
            
            if (response.ok) {
                await this.offlineStorage.removeFromSyncQueue(item.id);
                console.log('Queue item synced:', item.type);
                return true;
            }
            return false;
        } catch (error) {
            console.error('Error processing sync queue item:', error);
            return false;
        }
    }
    
    showSyncNotification(message, type = 'success') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'warning' : 'success'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <i class="fas fa-sync${type === 'error' ? '-slash' : ''} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
    
    async forceSyncNow() {
        if (!this.networkManager.isOnline) {
            this.showSyncNotification('Cannot sync while offline', 'error');
            return;
        }
        
        this.showSyncNotification('Starting sync...', 'info');
        await this.syncOfflineData();
    }
    
    async getOfflineDataCount() {
        const offlineSales = await this.offlineStorage.getOfflineSales();
        const syncQueue = await this.offlineStorage.getSyncQueue();
        
        return {
            sales: offlineSales.length,
            queue: syncQueue.length,
            total: offlineSales.length + syncQueue.length
        };
    }
    
    async hasDataToSync() {
        const counts = await this.getOfflineDataCount();
        return counts.total > 0;
    }
}

// Initialize sync manager when offline storage is ready
window.addEventListener('load', async () => {
    if (window.offlineStorage && window.networkManager) {
        window.syncManager = new SyncManager(window.offlineStorage, window.networkManager);
    }
});