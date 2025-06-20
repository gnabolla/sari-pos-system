class OfflineStorage {
    constructor() {
        this.dbName = 'SariPOSDB';
        this.dbVersion = 1;
        this.db = null;
        this.init();
    }

    async init() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(this.dbName, this.dbVersion);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Offline sales store
                if (!db.objectStoreNames.contains('offlineSales')) {
                    const salesStore = db.createObjectStore('offlineSales', { keyPath: 'id' });
                    salesStore.createIndex('timestamp', 'timestamp', { unique: false });
                    salesStore.createIndex('tenantId', 'tenant_id', { unique: false });
                }
                
                // Products cache store
                if (!db.objectStoreNames.contains('products')) {
                    const productsStore = db.createObjectStore('products', { keyPath: 'id' });
                    productsStore.createIndex('tenantId', 'tenant_id', { unique: false });
                    productsStore.createIndex('barcode', 'barcode', { unique: false });
                }
                
                // Settings cache store
                if (!db.objectStoreNames.contains('settings')) {
                    db.createObjectStore('settings', { keyPath: 'key' });
                }
                
                // Sync queue store
                if (!db.objectStoreNames.contains('syncQueue')) {
                    const syncStore = db.createObjectStore('syncQueue', { keyPath: 'id', autoIncrement: true });
                    syncStore.createIndex('timestamp', 'timestamp', { unique: false });
                    syncStore.createIndex('type', 'type', { unique: false });
                }
            };
        });
    }

    // Save sale offline
    async saveSaleOffline(saleData) {
        const transaction = this.db.transaction(['offlineSales'], 'readwrite');
        const store = transaction.objectStore('offlineSales');
        
        const offlineSale = {
            ...saleData,
            id: 'offline_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
            timestamp: new Date().toISOString(),
            synced: false
        };
        
        return new Promise((resolve, reject) => {
            const request = store.add(offlineSale);
            request.onsuccess = () => resolve(offlineSale);
            request.onerror = () => reject(request.error);
        });
    }

    // Get all offline sales
    async getOfflineSales() {
        const transaction = this.db.transaction(['offlineSales'], 'readonly');
        const store = transaction.objectStore('offlineSales');
        
        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Remove synced sale
    async removeSyncedSale(saleId) {
        const transaction = this.db.transaction(['offlineSales'], 'readwrite');
        const store = transaction.objectStore('offlineSales');
        
        return new Promise((resolve, reject) => {
            const request = store.delete(saleId);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    // Cache products for offline use
    async cacheProducts(products, tenantId) {
        const transaction = this.db.transaction(['products'], 'readwrite');
        const store = transaction.objectStore('products');
        
        // Clear existing products for this tenant
        const index = store.index('tenantId');
        const range = IDBKeyRange.only(tenantId);
        
        return new Promise((resolve, reject) => {
            index.openCursor(range).onsuccess = (event) => {
                const cursor = event.target.result;
                if (cursor) {
                    cursor.delete();
                    cursor.continue();
                } else {
                    // Add new products
                    products.forEach(product => {
                        store.add({ ...product, tenant_id: tenantId });
                    });
                    resolve();
                }
            };
        });
    }

    // Get cached products
    async getCachedProducts(tenantId) {
        const transaction = this.db.transaction(['products'], 'readonly');
        const store = transaction.objectStore('products');
        const index = store.index('tenantId');
        
        return new Promise((resolve, reject) => {
            const request = index.getAll(tenantId);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Find product by barcode
    async findProductByBarcode(barcode) {
        const transaction = this.db.transaction(['products'], 'readonly');
        const store = transaction.objectStore('products');
        const index = store.index('barcode');
        
        return new Promise((resolve, reject) => {
            const request = index.get(barcode);
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Add to sync queue
    async addToSyncQueue(type, data) {
        const transaction = this.db.transaction(['syncQueue'], 'readwrite');
        const store = transaction.objectStore('syncQueue');
        
        const queueItem = {
            type,
            data,
            timestamp: new Date().toISOString(),
            attempts: 0
        };
        
        return new Promise((resolve, reject) => {
            const request = store.add(queueItem);
            request.onsuccess = () => resolve(queueItem);
            request.onerror = () => reject(request.error);
        });
    }

    // Get sync queue
    async getSyncQueue() {
        const transaction = this.db.transaction(['syncQueue'], 'readonly');
        const store = transaction.objectStore('syncQueue');
        
        return new Promise((resolve, reject) => {
            const request = store.getAll();
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    // Remove from sync queue
    async removeFromSyncQueue(id) {
        const transaction = this.db.transaction(['syncQueue'], 'readwrite');
        const store = transaction.objectStore('syncQueue');
        
        return new Promise((resolve, reject) => {
            const request = store.delete(id);
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    // Save setting
    async saveSetting(key, value) {
        const transaction = this.db.transaction(['settings'], 'readwrite');
        const store = transaction.objectStore('settings');
        
        return new Promise((resolve, reject) => {
            const request = store.put({ key, value });
            request.onsuccess = () => resolve();
            request.onerror = () => reject(request.error);
        });
    }

    // Get setting
    async getSetting(key) {
        const transaction = this.db.transaction(['settings'], 'readonly');
        const store = transaction.objectStore('settings');
        
        return new Promise((resolve, reject) => {
            const request = store.get(key);
            request.onsuccess = () => resolve(request.result ? request.result.value : null);
            request.onerror = () => reject(request.error);
        });
    }
}

// Network status manager
class NetworkManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.callbacks = [];
        
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.notifyCallbacks('online');
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.notifyCallbacks('offline');
        });
    }
    
    onStatusChange(callback) {
        this.callbacks.push(callback);
    }
    
    notifyCallbacks(status) {
        this.callbacks.forEach(callback => callback(status));
    }
}

// Initialize global instances
window.offlineStorage = new OfflineStorage();
window.networkManager = new NetworkManager();