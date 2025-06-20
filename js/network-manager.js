class NetworkManager {
    constructor() {
        this.isOnline = navigator.onLine;
        this.statusChangeCallbacks = [];
        
        // Listen for network status changes
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.notifyStatusChange('online');
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.notifyStatusChange('offline');
        });
    }
    
    onStatusChange(callback) {
        this.statusChangeCallbacks.push(callback);
    }
    
    notifyStatusChange(status) {
        this.statusChangeCallbacks.forEach(callback => {
            try {
                callback(status);
            } catch (error) {
                console.error('Error in network status callback:', error);
            }
        });
    }
    
    async testConnection() {
        try {
            const response = await fetch('/sari/api/ping', {
                method: 'HEAD',
                cache: 'no-cache'
            });
            return response.ok;
        } catch (error) {
            return false;
        }
    }
}

// Initialize network manager
window.addEventListener('load', () => {
    window.networkManager = new NetworkManager();
});