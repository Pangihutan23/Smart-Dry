// js/script.js - Enhanced dengan Log Riwayat dan Notifikasi Klik

class SmartDryApp {
    constructor() {
        this.sensorChart = null;
        this.websocket = null;
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectInterval = 3000;
        
        this.chartData = {
            labels: [],
            temperatures: [],
            lightIntensities: []
        };

        this.notifications = [];
        this.logHistory = [];
        this.unreadCount = 0;
        this.currentPage = 'dashboard';
        this.currentFilter = 'all';
        this.currentLogTab = 'all';
        this.roofStatus = 'closed'; // closed, open, moving
        
        this.init();
    }

    init() {
        this.initializeChart();
        this.initWebSocket();
        this.setupEventListeners();
        this.setupNavigation();
        this.setupControlListeners();
        this.setupFilterListeners();
        this.setupLogTabs();
        this.setupQuickActions();
        this.setupHashNavigation();
        this.setupNotificationClickHandlers();
        this.loadSampleData();
        
        this.updateConnectionStatus(false, 'Connecting...');
        this.handleHashChange();
        
        // Update time every second
        setInterval(() => this.updateCurrentTime(), 1000);
        this.updateCurrentTime();
    }

    updateCurrentTime() {
        const now = new Date();
        const timeString = now.toLocaleString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
        
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            timeElement.textContent = timeString;
        }
    }

    setupHashNavigation() {
        // Handle hash changes in URL
        window.addEventListener('hashchange', () => {
            this.handleHashChange();
        });
    }

    handleHashChange() {
        const hash = window.location.hash.substring(1); // Remove # symbol
        const validPages = ['dashboard', 'notifications', 'control', 'logs'];
        
        if (validPages.includes(hash)) {
            this.showPage(hash);
            
            // Update active nav item
            document.querySelectorAll('.nav-item').forEach(nav => {
                nav.classList.remove('active');
                if (nav.getAttribute('href') === `#${hash}`) {
                    nav.classList.add('active');
                }
            });
        }
    }

    setupNotificationClickHandlers() {
        // Handle klik pada quick notification badge
        document.addEventListener('click', (e) => {
            // Quick notification badge
            if (e.target.closest('.quick-notification-badge')) {
                this.showPage('notifications');
                return;
            }
            
            // Notification alert
            if (e.target.closest('.notification-alert.clickable')) {
                this.showPage('notifications');
                return;
            }
            
            // Notification item yang bisa diklik
            if (e.target.closest('.notification-item.clickable')) {
                const notificationItem = e.target.closest('.notification-item');
                this.handleNotificationClick(notificationItem);
                return;
            }

            // Notification preview items di dashboard
            if (e.target.closest('.notification-preview-item')) {
                this.showPage('notifications');
                return;
            }

            // Stat cards di notifications page
            if (e.target.closest('.stat-card.clickable')) {
                const statCard = e.target.closest('.stat-card');
                const filter = statCard.dataset.filter;
                this.applyNotificationFilter(filter);
                return;
            }

            // Status items yang bisa diklik di dashboard
            if (e.target.closest('.status-item.clickable')) {
                const statusItem = e.target.closest('.status-item');
                const action = statusItem.dataset.action;
                this.handleStatusItemClick(action);
                return;
            }

            // Sensor cards yang bisa diklik
            if (e.target.closest('.sensor-card')) {
                const sensorCard = e.target.closest('.sensor-card');
                this.handleSensorCardClick(sensorCard);
                return;
            }
        });
    }

    handleNotificationClick(notificationElement) {
        const notificationId = notificationElement.dataset.id;
        const notification = this.notifications.find(n => n.id === notificationId);
        
        if (notification) {
            // Tandai sebagai sudah dibaca
            if (!notification.isRead) {
                this.markNotificationAsRead(notificationId);
            }
            
            // Pindah ke halaman notifikasi
            this.showPage('notifications');
            
            // Highlight notifikasi yang diklik
            this.highlightNotification(notificationId);
        }
    }

    highlightNotification(notificationId) {
        // Hapus highlight sebelumnya
        document.querySelectorAll('.notification-item.highlighted').forEach(item => {
            item.classList.remove('highlighted');
        });
        
        // Tambahkan highlight ke notifikasi yang diklik
        const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
        if (notificationElement) {
            notificationElement.classList.add('highlighted');
            
            // Scroll ke notifikasi
            setTimeout(() => {
                notificationElement.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            }, 500);
            
            // Hapus highlight setelah 3 detik
            setTimeout(() => {
                notificationElement.classList.remove('highlighted');
            }, 3000);
        }
    }

    handleStatusItemClick(action) {
        switch (action) {
            case 'toggle-roof':
                this.toggleRoofStatus();
                break;
            case 'toggle-mode':
                this.toggleOperationMode();
                break;
            case 'adjust-ventilation':
                this.showVentilationControl();
                break;
            case 'toggle-heater':
                this.toggleHeater();
                break;
        }
    }

    handleSensorCardClick(sensorCard) {
        const sensorType = sensorCard.dataset.sensor;
        this.showSensorDetails(sensorType);
    }

    toggleRoofStatus() {
        const newStatus = this.roofStatus === 'closed' ? 'open' : 'closed';
        this.controlRoof(newStatus);
    }

    toggleOperationMode() {
        const currentMode = document.getElementById('operation-mode').textContent;
        const newMode = currentMode === 'Otomatis' ? 'Manual' : 'Otomatis';
        
        document.getElementById('operation-mode').textContent = newMode;
        document.getElementById('operation-mode').className = `status-value ${newMode === 'Otomatis' ? 'auto' : 'active'}`;
        
        this.showToast(`Mode operasi diubah ke: ${newMode}`, 'success');
        
        // Log the change
        this.addLog('system', 'mode_change', `Mode operasi diubah ke ${newMode}`, newMode.toLowerCase());
    }

    showVentilationControl() {
        this.showPage('control');
        this.showToast('Buka tab Kontrol Ventilasi untuk mengatur', 'info');
    }

    toggleHeater() {
        const currentStatus = document.getElementById('heater-status').textContent;
        const newStatus = currentStatus === 'Nonaktif' ? 'Aktif' : 'Nonaktif';
        
        document.getElementById('heater-status').textContent = newStatus;
        document.getElementById('heater-status').className = `status-value ${newStatus === 'Aktif' ? 'active' : 'inactive'}`;
        
        this.showToast(`Pemanas ${newStatus.toLowerCase()}`, newStatus === 'Aktif' ? 'success' : 'info');
        
        // Log the change
        this.addLog('system', 'heater_toggle', `Pemanas ${newStatus.toLowerCase()}`, newStatus.toLowerCase());
    }

    showSensorDetails(sensorType) {
        const sensorNames = {
            'rainfall': 'Sensor Hujan',
            'light': 'Sensor Cahaya',
            'temperature': 'Sensor Suhu',
            'level': 'Sensor Level Gabah'
        };
        
        this.showToast(`Membuka detail ${sensorNames[sensorType]}`, 'info');
        // In a real app, this would open a detailed sensor view
    }

    loadSampleData() {
        // Sample sensor data
        this.sensorData = {
            rainfall: { value: 0.0, unit: 'mm', status: 'normal' },
            light: { value: 850, unit: 'lux', status: 'normal' },
            temperature: { value: 32, unit: '°C', status: 'normal' },
            humidity: { value: 65, unit: '%', status: 'normal' },
            level: { value: 75, unit: '%', status: 'warning' }
        };

        // Sample notifications
        this.notifications = [
            {
                id: '1',
                type: 'warning',
                message: '🚨 Suhu Tinggi: 36°C (Batas: 35°C)',
                timestamp: new Date(Date.now() - 5 * 60000).toISOString().replace('T', ' ').substring(0, 19),
                isRead: false,
                priority: 'high',
                clickable: true
            },
            {
                id: '2',
                type: 'warning',
                message: '💧 Kelembapan Tinggi: 85% (Batas: 80%)',
                timestamp: new Date(Date.now() - 10 * 60000).toISOString().replace('T', ' ').substring(0, 19),
                isRead: false,
                priority: 'high',
                clickable: true
            },
            {
                id: '3',
                type: 'info',
                message: '🌧️ Hujan sedang: 10mm',
                timestamp: new Date(Date.now() - 30 * 60000).toISOString().replace('T', ' ').substring(0, 19),
                isRead: true,
                priority: 'medium',
                clickable: true
            },
            {
                id: '4',
                type: 'warning',
                message: '🌑 Cahaya Rendah: 50 lux',
                timestamp: new Date(Date.now() - 2 * 3600000).toISOString().replace('T', ' ').substring(0, 19),
                isRead: false,
                priority: 'medium',
                clickable: true
            },
            {
                id: '5',
                type: 'success',
                message: '🟢 Sistem aktif - ' + new Date().toLocaleString('id-ID'),
                timestamp: new Date(Date.now() - 6 * 3600000).toISOString().replace('T', ' ').substring(0, 19),
                isRead: true,
                priority: 'low',
                clickable: true
            }
        ];

        // Sample log history
        this.logHistory = [
            {
                id: 'log1',
                type: 'roof',
                action: 'open',
                message: 'Atap terbuka otomatis - kondisi cerah',
                timestamp: new Date(Date.now() - 4 * 3600000).toISOString().replace('T', ' ').substring(0, 19),
                status: 'open'
            },
            {
                id: 'log2',
                type: 'roof',
                action: 'close',
                message: 'Atap tertutup otomatis - terdeteksi hujan',
                timestamp: new Date(Date.now() - 3 * 3600000).toISOString().replace('T', ' ').substring(0, 19),
                status: 'closed'
            },
            {
                id: 'log3',
                type: 'system',
                action: 'start',
                message: 'Sistem pengeringan diaktifkan',
                timestamp: new Date(Date.now() - 8 * 3600000).toISOString().replace('T', ' ').substring(0, 19),
                status: 'active'
            },
            {
                id: 'log4',
                type: 'rain',
                action: 'detected',
                message: 'Hujan terdeteksi: 10mm',
                timestamp: new Date(Date.now() - 3 * 3600000).toISOString().replace('T', ' ').substring(0, 19),
                status: 'rain'
            },
            {
                id: 'log5',
                type: 'roof',
                action: 'open',
                message: 'Atap dibuka manual oleh operator',
                timestamp: new Date(Date.now() - 2 * 3600000).toISOString().replace('T', ' ').substring(0, 19),
                status: 'open'
            }
        ];
        
        this.updateSensorCards();
        this.updateNotificationStats();
        this.updateRoofStatus();
        this.updateQuickNotificationBadge();
        this.updateNotificationPreview();
        this.updateLogStatistics();
        this.applyLogFilter();
    }

    updateSensorCards() {
        const sensorOverview = document.querySelector('.sensor-overview');
        if (!sensorOverview) return;

        const sensors = [
            {
                type: 'rainfall',
                icon: 'fa-cloud-rain',
                title: 'Sensor Hujan',
                value: this.sensorData.rainfall.value,
                unit: this.sensorData.rainfall.unit,
                status: this.sensorData.rainfall.status
            },
            {
                type: 'light',
                icon: 'fa-sun',
                title: 'Intensitas Cahaya',
                value: this.sensorData.light.value,
                unit: this.sensorData.light.unit,
                status: this.sensorData.light.status
            },
            {
                type: 'temperature',
                icon: 'fa-thermometer-half',
                title: 'Suhu & Kelembapan',
                value: `${this.sensorData.temperature.value}°C / ${this.sensorData.humidity.value}%`,
                unit: '',
                status: this.sensorData.temperature.status
            },
            {
                type: 'level',
                icon: 'fa-weight-hanging',
                title: 'Level Gabah',
                value: this.sensorData.level.value,
                unit: this.sensorData.level.unit,
                status: this.sensorData.level.status
            }
        ];

        const sensorsHTML = sensors.map(sensor => `
            <div class="sensor-card clickable" data-sensor="${sensor.type}">
                <div class="sensor-header">
                    <i class="fas ${sensor.icon} sensor-icon ${sensor.type}"></i>
                    <div class="sensor-info">
                        <div class="sensor-title">${sensor.title}</div>
                        <div class="sensor-value">${sensor.value} <span class="sensor-unit">${sensor.unit}</span></div>
                    </div>
                </div>
                <div class="sensor-status ${sensor.status}">
                    ${this.getStatusText(sensor.status)}
                </div>
            </div>
        `).join('');

        sensorOverview.innerHTML = sensorsHTML;
    }

    getStatusText(status) {
        const statusTexts = {
            'normal': 'Normal',
            'warning': 'Perlu Monitoring',
            'critical': 'Kritis'
        };
        return statusTexts[status] || status;
    }

    setupNavigation() {
        const navItems = document.querySelectorAll('.nav-item');
        
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const page = item.dataset.page;
                
                // Add click animation
                item.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    item.style.transform = '';
                }, 150);
                
                this.showPage(page);
                
                // Update active nav item
                navItems.forEach(nav => nav.classList.remove('active'));
                item.classList.add('active');
            });
        });
    }

    showPage(page) {
        // Hide all pages with fade out
        document.querySelectorAll('.page').forEach(p => {
            if (p.classList.contains('active')) {
                p.style.animation = 'slideInUp 0.5s ease reverse';
                setTimeout(() => {
                    p.classList.remove('active');
                    p.style.animation = '';
                }, 250);
            }
        });
        
        // Show selected page with fade in
        setTimeout(() => {
            const targetPage = document.getElementById(`${page}-page`);
            if (targetPage) {
                targetPage.classList.add('active');
                
                // Update URL hash
                window.location.hash = page;
                
                // Load page-specific content
                if (page === 'notifications') {
                    this.loadNotifications();
                    this.applyNotificationFilter();
                } else if (page === 'control') {
                    this.loadControlPanel();
                } else if (page === 'dashboard') {
                    this.updateDashboardStats();
                    this.updateNotificationPreview();
                } else if (page === 'logs') {
                    this.applyLogFilter();
                    this.updateLogStatistics();
                }
            }
        }, 250);
        
        this.currentPage = page;
    }

    setupLogTabs() {
        const logTabs = document.querySelectorAll('.log-tab');
        
        logTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const filter = tab.dataset.filter;
                
                // Update active tab
                logTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                
                this.currentLogTab = filter;
                this.applyLogFilter();
            });
        });
    }

    applyLogFilter() {
        const logItemsContainer = document.getElementById('log-items');
        let filteredLogs = [...this.logHistory];
        
        switch (this.currentLogTab) {
            case 'roof':
                filteredLogs = filteredLogs.filter(log => log.type === 'roof');
                break;
            case 'system':
                filteredLogs = filteredLogs.filter(log => log.type === 'system');
                break;
            case 'rain':
                filteredLogs = filteredLogs.filter(log => log.type === 'rain');
                break;
            case 'sensor':
                filteredLogs = filteredLogs.filter(log => log.type === 'sensor');
                break;
            // 'all' shows all logs
        }
        
        this.renderLogHistory(filteredLogs);
    }

    renderLogHistory(logs) {
        const container = document.getElementById('log-items');
        
        if (logs.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div style="font-size: 3em; margin-bottom: 20px;">📝</div>
                    <h3>Tidak ada log</h3>
                    <p>Tidak ada aktivitas yang tercatat.</p>
                </div>
            `;
            return;
        }

        const logsHTML = logs.map(log => `
            <div class="log-item" data-id="${log.id}">
                <div class="log-icon ${log.status}">
                    ${this.getLogIcon(log.type, log.action)}
                </div>
                <div class="log-content">
                    <div class="log-message">${log.message}</div>
                    <div class="log-time">⏰ ${this.formatTime(log.timestamp)}</div>
                </div>
                <div class="log-status ${log.status}">
                    ${this.getStatusText(log.status)}
                </div>
            </div>
        `).join('');

        container.innerHTML = logsHTML;
    }

    getLogIcon(type, action) {
        const icons = {
            'roof': {
                'open': '🔓',
                'close': '🔒',
                'moving': '⚙️'
            },
            'system': {
                'start': '🟢',
                'stop': '🔴',
                'error': '❌',
                'mode_change': '🔄',
                'heater_toggle': '🔥'
            },
            'rain': {
                'detected': '🌧️',
                'stopped': '🌤️'
            },
            'sensor': {
                'reading': '📊',
                'alert': '⚠️'
            }
        };
        
        return icons[type]?.[action] || '📝';
    }

    updateLogStatistics() {
        const roofOpens = this.logHistory.filter(log => 
            log.type === 'roof' && log.action === 'open'
        ).length;
        
        const roofCloses = this.logHistory.filter(log => 
            log.type === 'roof' && log.action === 'close'
        ).length;
        
        const rainEvents = this.logHistory.filter(log => 
            log.type === 'rain'
        ).length;
        
        const autoActions = this.logHistory.filter(log => 
            log.message.includes('otomatis')
        ).length;

        document.getElementById('roof-opens').textContent = roofOpens;
        document.getElementById('roof-closes').textContent = roofCloses;
        document.getElementById('rain-events').textContent = rainEvents;
        document.getElementById('auto-actions').textContent = autoActions;
    }

    updateRoofStatus() {
        const roofIndicator = document.querySelector('.roof-indicator');
        const roofStatusText = document.querySelector('.roof-status-text');
        const roofControlStatus = document.getElementById('roof-control-status');
        const lastAction = this.logHistory.find(log => log.type === 'roof');
        
        if (lastAction) {
            this.roofStatus = lastAction.status;
            if (roofIndicator) roofIndicator.className = `roof-indicator ${this.roofStatus}`;
            if (roofStatusText) roofStatusText.textContent = this.getRoofStatusText(this.roofStatus);
            if (roofControlStatus) {
                roofControlStatus.className = `status-indicator ${this.roofStatus}`;
                roofControlStatus.innerHTML = `
                    <i class="fas fa-door-${this.roofStatus === 'open' ? 'open' : 'closed'}"></i>
                    <span>Status: ${this.getRoofStatusText(this.roofStatus)}</span>
                `;
            }
        }
    }

    getRoofStatusText(status) {
        const statusTexts = {
            'open': 'Terbuka',
            'closed': 'Tertutup',
            'moving': 'Sedang Bergerak'
        };
        
        return statusTexts[status] || 'Status Tidak Diketahui';
    }

    // Method untuk mengontrol atap
    controlRoof(action) {
        this.showToast(`Mengirim perintah: ${action === 'open' ? 'Buka Atap' : 'Tutup Atap'}`, 'info');
        
        // Simulate roof movement
        this.roofStatus = 'moving';
        this.updateRoofStatus();
        
        // Add to log history
        const newLog = {
            id: 'log' + Date.now(),
            type: 'roof',
            action: action,
            message: action === 'open' ? 
                'Atap dibuka manual oleh operator' : 
                'Atap ditutup manual oleh operator',
            timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19),
            status: action
        };
        
        this.logHistory.unshift(newLog);
        this.applyLogFilter();
        this.updateRoofStatus();
        this.updateLogStatistics();
        
        // Simulate completion after 3 seconds
        setTimeout(() => {
            this.roofStatus = action;
            this.updateRoofStatus();
            this.showToast(
                action === 'open' ? 'Atap berhasil terbuka' : 'Atap berhasil tertutup', 
                'success'
            );
        }, 3000);
    }

    // Enhanced notification methods dengan priority
    addNotification(message, type = 'info', priority = 'medium', timestamp = null, isRead = false, clickable = true) {
        const notification = {
            id: 'notif' + Date.now(),
            type: type,
            message: message,
            priority: priority,
            timestamp: timestamp || new Date().toISOString().replace('T', ' ').substring(0, 19),
            isRead: isRead,
            clickable: clickable
        };
        
        this.notifications.unshift(notification);
        this.applyNotificationFilter();
        this.updateNotificationStats();
        
        // Show notification alert
        if (!isRead) {
            this.showNotificationAlert(message, type, clickable);
        }
        
        // Update quick notification badge
        this.updateQuickNotificationBadge();
        
        // Update notification preview di dashboard
        this.updateNotificationPreview();
        
        return notification.id;
    }

    // Enhanced notification rendering dengan priority
    renderFilteredNotifications(notifications) {
        const container = document.getElementById('notifications-list-full');
        
        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <div style="font-size: 4em; margin-bottom: 20px;">🔍</div>
                    <h3>Tidak ada notifikasi</h3>
                    <p>Tidak ditemukan notifikasi dengan filter yang dipilih.</p>
                </div>
            `;
            return;
        }

        const notificationsHTML = notifications.map(notif => `
            <div class="notification-item ${notif.type} ${notif.isRead ? 'read' : 'unread'} ${notif.clickable ? 'clickable' : ''}" 
                 data-id="${notif.id}">
                <div class="notification-content">
                    <div class="notification-message">${notif.message}</div>
                    <div class="notification-priority">
                        <span class="priority-badge priority-${notif.priority}">
                            ${this.getPriorityText(notif.priority)}
                        </span>
                    </div>
                    <div class="notification-time">⏰ ${this.formatTime(notif.timestamp)}</div>
                </div>
                <div class="notification-actions-small">
                    ${!notif.isRead ? 
                        `<button class="mark-read-btn" onclick="event.stopPropagation(); smartDryApp.markNotificationAsRead('${notif.id}')">
                            ✓ Tandai Baca
                         </button>` : ''
                    }
                    <button class="remove-btn" onclick="event.stopPropagation(); smartDryApp.deleteNotification('${notif.id}')">
                        🗑️ Hapus
                    </button>
                </div>
            </div>
        `).join('');

        container.innerHTML = notificationsHTML;
    }

    getPriorityText(priority) {
        const priorityTexts = {
            'high': 'Tinggi',
            'medium': 'Sedang',
            'low': 'Rendah'
        };
        
        return priorityTexts[priority] || priority;
    }

    // Update method showNotificationAlert untuk yang bisa diklik
    showNotificationAlert(message, type = 'info', clickable = true) {
        const alert = document.createElement('div');
        alert.className = `notification-alert ${type} ${clickable ? 'clickable' : ''}`;
        alert.innerHTML = `
            <div class="alert-content">
                <strong>${type === 'warning' ? '⚠️' : type === 'error' ? '❌' : 'ℹ️'} Notifikasi Baru</strong>
                <div>${message}</div>
                ${clickable ? '<div class="alert-hint">Klik untuk melihat detail →</div>' : ''}
            </div>
        `;
        
        alert.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            color: var(--dark);
            padding: 15px 20px;
            border-radius: var(--radius);
            box-shadow: var(--shadow-hover);
            z-index: 10000;
            max-width: 400px;
            animation: slideInRight 0.3s ease;
            border-left: 4px solid ${this.getAlertColor(type)};
            cursor: ${clickable ? 'pointer' : 'default'};
        `;

        document.body.appendChild(alert);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentNode) {
                alert.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }
        }, 5000);

        // Click handler untuk alert
        if (clickable) {
            alert.addEventListener('click', () => {
                this.showPage('notifications');
                alert.remove();
            });
        }
    }

    getAlertColor(type) {
        const colors = {
            'warning': 'var(--secondary)',
            'error': '#F44336',
            'success': 'var(--primary)',
            'info': '#2196F3'
        };
        return colors[type] || '#2196F3';
    }

    // Method untuk update quick notification badge
    updateQuickNotificationBadge() {
        let badge = document.getElementById('quick-notification-badge');
        const unreadCount = this.notifications.filter(n => !n.isRead).length;
        
        if (unreadCount > 0) {
            if (!badge) {
                badge = document.createElement('div');
                badge.id = 'quick-notification-badge';
                badge.className = 'quick-notification-badge';
                badge.innerHTML = `
                    🔔
                    <div class="badge-count">${unreadCount}</div>
                `;
                document.body.appendChild(badge);
                
                // Add click event to badge
                badge.addEventListener('click', () => {
                    this.showPage('notifications');
                });
            } else {
                const badgeCount = badge.querySelector('.badge-count');
                badgeCount.textContent = unreadCount;
            }
            badge.classList.remove('hidden');
        } else if (badge) {
            badge.classList.add('hidden');
        }
    }

    // Update notification preview di dashboard
    updateNotificationPreview() {
        const previewContainer = document.getElementById('notification-preview-list');
        if (!previewContainer) return;

        const recentNotifications = this.notifications.slice(0, 3); // Ambil 3 notifikasi terbaru
        
        if (recentNotifications.length === 0) {
            previewContainer.innerHTML = '<p class="no-notifications">Tidak ada notifikasi</p>';
            return;
        }

        const previewHTML = recentNotifications.map(notif => `
            <div class="notification-preview-item ${notif.type} ${notif.clickable ? 'clickable' : ''}" data-id="${notif.id}">
                <div class="preview-message">${notif.message}</div>
                <div class="preview-time">${this.formatTime(notif.timestamp)}</div>
            </div>
        `).join('');

        previewContainer.innerHTML = previewHTML;
    }

    // Quick actions untuk notifikasi page
    setupQuickActions() {
        // Refresh sensors button
        document.getElementById('refresh-sensors')?.addEventListener('click', () => {
            this.refreshSensors();
        });

        // Quick roof controls
        document.getElementById('quick-open-roof')?.addEventListener('click', () => {
            this.controlRoof('open');
        });
        
        document.getElementById('quick-close-roof')?.addEventListener('click', () => {
            this.controlRoof('close');
        });
        
        // Emergency stop
        document.getElementById('emergency-stop')?.addEventListener('click', () => {
            this.emergencyStop();
        });

        // Test notification
        document.getElementById('test-notification-btn')?.addEventListener('click', () => {
            this.addNotification(
                '🧪 Notifikasi percobaan - Sistem berfungsi normal',
                'info',
                'low',
                null,
                false,
                true
            );
        });

        // Notification actions
        document.getElementById('mark-all-read-btn')?.addEventListener('click', () => {
            this.markAllAsRead();
        });
        
        document.getElementById('clear-all-btn')?.addEventListener('click', () => {
            this.clearAllNotifications();
        });
    }

    refreshSensors() {
        const refreshBtn = document.getElementById('refresh-sensors');
        if (refreshBtn) {
            refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memuat...';
            refreshBtn.disabled = true;
        }

        // Simulate API call
        setTimeout(() => {
            // Update sensor data with random values for demo
            this.sensorData.temperature.value = 32 + Math.floor(Math.random() * 6);
            this.sensorData.humidity.value = 60 + Math.floor(Math.random() * 20);
            this.sensorData.light.value = 500 + Math.floor(Math.random() * 600);
            this.sensorData.rainfall.value = (Math.random() * 2).toFixed(1);
            
            this.updateSensorCards();
            
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                refreshBtn.disabled = false;
            }
            
            this.showToast('Data sensor diperbarui', 'success');
        }, 1500);
    }

    emergencyStop() {
        if (confirm('Apakah Anda yakin ingin menghentikan sistem secara darurat? Semua operasi akan dihentikan.')) {
            this.showToast('🛑 Sistem dihentikan secara darurat!', 'error');
            
            // Reset all controls
            this.roofStatus = 'closed';
            this.updateRoofStatus();
            
            // Add emergency log
            this.addLog('system', 'emergency_stop', 'Sistem dihentikan secara darurat oleh operator', 'inactive');
            
            // Show confirmation
            setTimeout(() => {
                if (confirm('Sistem telah dihentikan. Tekan OK untuk melanjutkan operasi normal.')) {
                    this.showToast('Sistem dilanjutkan kembali', 'success');
                    this.addLog('system', 'resume', 'Sistem dilanjutkan kembali setelah emergency stop', 'active');
                }
            }, 1000);
        }
    }

    setupFilterListeners() {
        const filterButtons = document.querySelectorAll('.filter-btn');
        const filterSelect = document.getElementById('notification-filter');
        
        filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.dataset.filter;
                this.applyNotificationFilter(filter);
            });
        });

        if (filterSelect) {
            filterSelect.addEventListener('change', (e) => {
                this.applyNotificationFilter(e.target.value);
            });
        }
    }

    applyNotificationFilter(filter = this.currentFilter) {
        this.currentFilter = filter;
        
        let filteredNotifications = [...this.notifications];
        
        switch (filter) {
            case 'unread':
                filteredNotifications = filteredNotifications.filter(n => !n.isRead);
                break;
            case 'warning':
                filteredNotifications = filteredNotifications.filter(n => n.type === 'warning');
                break;
            case 'error':
                filteredNotifications = filteredNotifications.filter(n => n.type === 'error');
                break;
            case 'info':
                filteredNotifications = filteredNotifications.filter(n => n.type === 'info');
                break;
            // 'all' shows all notifications
        }
        
        // Update filter UI
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.filter === filter) {
                btn.classList.add('active');
            }
        });
        
        const filterSelect = document.getElementById('notification-filter');
        if (filterSelect) {
            filterSelect.value = filter;
        }
        
        this.renderFilteredNotifications(filteredNotifications);
    }

    loadNotifications() {
        const notificationsList = document.getElementById('notifications-list-full');
        if (!notificationsList) return;
        
        notificationsList.innerHTML = '<div class="loading">Memuat notifikasi...</div>';
        
        // Simulate API delay
        setTimeout(() => {
            this.applyNotificationFilter();
        }, 800);
    }

    markNotificationAsRead(notificationId) {
        const notification = this.notifications.find(n => n.id === notificationId);
        if (notification) {
            notification.isRead = true;
            this.applyNotificationFilter();
            this.updateNotificationStats();
            this.updateQuickNotificationBadge();
            this.updateNotificationPreview();
            this.showToast('Notifikasi ditandai sudah dibaca', 'success');
        }
    }

    markAllAsRead() {
        this.notifications.forEach(notif => notif.isRead = true);
        this.applyNotificationFilter();
        this.updateNotificationStats();
        this.updateQuickNotificationBadge();
        this.updateNotificationPreview();
        this.showToast('Semua notifikasi ditandai sudah dibaca', 'success');
    }

    deleteNotification(notificationId) {
        const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
        if (notificationElement) {
            notificationElement.style.animation = 'slideInUp 0.3s ease reverse';
            setTimeout(() => {
                this.notifications = this.notifications.filter(n => n.id !== notificationId);
                this.applyNotificationFilter();
                this.updateNotificationStats();
                this.updateQuickNotificationBadge();
                this.updateNotificationPreview();
                this.showToast('Notifikasi dihapus', 'success');
            }, 300);
        }
    }

    clearAllNotifications() {
        if (confirm('Apakah Anda yakin ingin menghapus semua notifikasi?')) {
            this.notifications = [];
            this.applyNotificationFilter();
            this.updateNotificationStats();
            this.updateQuickNotificationBadge();
            this.updateNotificationPreview();
            this.showToast('Semua notifikasi dihapus', 'success');
        }
    }

    updateNotificationStats() {
        const totalCount = this.notifications.length;
        const unreadCount = this.notifications.filter(n => !n.isRead).length;
        const warningCount = this.notifications.filter(n => n.type === 'warning').length;
        const errorCount = this.notifications.filter(n => n.type === 'error').length;
        
        document.getElementById('total-notifications').textContent = totalCount;
        document.getElementById('unread-notifications').textContent = unreadCount;
        document.getElementById('warning-notifications').textContent = warningCount;
        document.getElementById('error-notifications').textContent = errorCount;
        
        this.unreadCount = unreadCount;
        this.updateNotificationBadge();
    }

    updateNotificationBadge() {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            badge.textContent = this.unreadCount;
            badge.style.display = this.unreadCount > 0 ? 'flex' : 'none';
            
            // Add bounce animation when count changes
            if (this.unreadCount > 0) {
                badge.style.animation = 'bounce 0.5s ease';
                setTimeout(() => badge.style.animation = '', 500);
            }
        }
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);

        if (diffMins < 1) {
            return 'Baru saja';
        } else if (diffMins < 60) {
            return `${diffMins} menit yang lalu`;
        } else if (diffHours < 24) {
            return `${diffHours} jam yang lalu`;
        } else if (diffDays < 7) {
            return `${diffDays} hari yang lalu`;
        } else {
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-message">${message}</span>
                <button class="toast-close" onclick="this.parentElement.parentElement.remove()">×</button>
            </div>
        `;

        document.body.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'slideInUp 0.3s ease reverse';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }

    addLog(type, action, message, status = null) {
        const log = {
            id: 'log' + Date.now(),
            type: type,
            action: action,
            message: message,
            timestamp: new Date().toISOString().replace('T', ' ').substring(0, 19),
            status: status
        };
        
        this.logHistory.unshift(log);
        
        if (this.currentPage === 'logs') {
            this.applyLogFilter();
            this.updateLogStatistics();
        }
        
        return log.id;
    }

    updateDashboardStats() {
        // Update quick stats
        document.getElementById('active-notifications').textContent = 
            this.notifications.filter(n => !n.isRead).length;
        document.getElementById('avg-temperature').textContent = '36°C';
        document.getElementById('system-status').textContent = 'Aktif';
        document.getElementById('system-uptime').textContent = '99.8%';
    }

    loadControlPanel() {
        // Initialize control panel with current values
        this.updateControlPanel();
    }

    updateControlPanel() {
        // Update control values based on current sensor data
        const currentData = this.getCurrentSensorData();
        
        // Update temperature control
        document.getElementById('temperature-value').textContent = 
            `${currentData.temperature}°C`;
        document.getElementById('temperature-slider').value = currentData.temperature;
        
        // Update humidity control
        document.getElementById('humidity-value').textContent = 
            `${currentData.humidity}%`;
        document.getElementById('humidity-slider').value = currentData.humidity;
        
        // Update light control
        document.getElementById('light-value-control').textContent = 
            `${currentData.light_intensity} lux`;
        document.getElementById('light-slider').value = currentData.light_intensity;
    }

    getCurrentSensorData() {
        // Return current sensor data (in real app, this would come from WebSocket)
        return {
            temperature: 36,
            humidity: 85,
            light_intensity: 50,
            rainfall: 10,
            distance: 55
        };
    }

    setupControlListeners() {
        // Temperature control
        const tempSlider = document.getElementById('temperature-slider');
        if (tempSlider) {
            tempSlider.addEventListener('input', (e) => {
                const value = e.target.value;
                document.getElementById('temperature-value').textContent = `${value}°C`;
            });
        }

        // Humidity control
        const humiditySlider = document.getElementById('humidity-slider');
        if (humiditySlider) {
            humiditySlider.addEventListener('input', (e) => {
                const value = e.target.value;
                document.getElementById('humidity-value').textContent = `${value}%`;
            });
        }

        // Light control
        const lightSlider = document.getElementById('light-slider');
        if (lightSlider) {
            lightSlider.addEventListener('input', (e) => {
                const value = e.target.value;
                document.getElementById('light-value-control').textContent = `${value} lux`;
            });
        }

        // Ventilation control
        const ventilationSlider = document.getElementById('ventilation-slider');
        if (ventilationSlider) {
            ventilationSlider.addEventListener('input', (e) => {
                const value = e.target.value;
                document.getElementById('ventilation-value').textContent = `${value}%`;
                
                // Update status in dashboard
                const ventilationStatus = document.getElementById('ventilation-status');
                if (ventilationStatus) {
                    ventilationStatus.textContent = `Aktif (${value}%)`;
                }
            });
        }

        // Heater control
        const heaterSlider = document.getElementById('heater-slider');
        if (heaterSlider) {
            heaterSlider.addEventListener('input', (e) => {
                const value = e.target.value;
                const heaterValue = document.getElementById('heater-value');
                if (heaterValue) {
                    heaterValue.textContent = value > 0 ? `${value}%` : 'OFF';
                    heaterValue.style.color = value > 0 ? 'var(--primary)' : '#F44336';
                }
            });
        }

        // Toggle switches
        document.querySelectorAll('.toggle-switch input').forEach(switchEl => {
            switchEl.addEventListener('change', (e) => {
                const controlName = e.target.id.replace('-toggle', '');
                const isEnabled = e.target.checked;
                
                this.showToast(
                    `${this.getControlLabel(controlName)} ${isEnabled ? 'diaktifkan' : 'dinonaktifkan'}`,
                    isEnabled ? 'success' : 'info'
                );

                // Special handling for auto roof toggle
                if (controlName === 'auto-roof') {
                    const modeElement = document.getElementById('operation-mode');
                    if (modeElement) {
                        modeElement.textContent = isEnabled ? 'Otomatis' : 'Manual';
                        modeElement.className = `status-value ${isEnabled ? 'auto' : 'active'}`;
                    }
                }
            });
        });
    }

    getControlLabel(controlName) {
        const labels = {
            'system': 'Sistem',
            'heater': 'Pemanas',
            'ventilation': 'Ventilasi',
            'lighting': 'Pencahayaan',
            'drying': 'Pengeringan',
            'emergency': 'Mode Darurat',
            'auto-roof': 'Mode Otomatis Atap'
        };
        return labels[controlName] || controlName;
    }

    initializeChart() {
        const ctx = document.getElementById('sensorChart');
        if (!ctx) return;

        // Generate sample data for the chart
        this.generateChartData();

        this.sensorChart = new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: this.chartData.labels,
                datasets: [
                    {
                        label: 'Suhu (°C)',
                        data: this.chartData.temperatures,
                        borderColor: '#ff6384',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Cahaya (lux)',
                        data: this.chartData.lightIntensities,
                        borderColor: '#36a2eb',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    },
                    x: {
                        grid: {
                            color: 'rgba(0,0,0,0.1)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                }
            }
        });
    }

    generateChartData() {
        const now = new Date();
        this.chartData.labels = [];
        this.chartData.temperatures = [];
        this.chartData.lightIntensities = [];

        // Generate data for the last 24 hours
        for (let i = 23; i >= 0; i--) {
            const time = new Date(now);
            time.setHours(now.getHours() - i);
            this.chartData.labels.push(time.getHours() + ':00');
            
            // Generate realistic data
            const baseTemp = 30;
            const tempVariation = Math.sin(i * Math.PI / 12) * 6;
            this.chartData.temperatures.push(Math.round(baseTemp + tempVariation));
            
            const baseLight = 500;
            const lightVariation = Math.sin(i * Math.PI / 12) * 400;
            this.chartData.lightIntensities.push(Math.max(0, Math.round(baseLight + lightVariation)));
        }
    }

    initWebSocket() {
        // WebSocket implementation would go here
        console.log('WebSocket initialization placeholder');
        
        // Simulate connection after a delay
        setTimeout(() => {
            this.updateConnectionStatus(true, 'Connected');
        }, 2000);
    }

    updateConnectionStatus(connected, message) {
        const statusDot = document.getElementById('status-dot');
        const statusText = document.getElementById('status-text');
        
        if (statusDot && statusText) {
            if (connected) {
                statusDot.classList.add('connected');
                statusText.textContent = message;
                statusText.style.color = '#00C851';
            } else {
                statusDot.classList.remove('connected');
                statusText.textContent = message;
                statusText.style.color = '#ff4444';
            }
        }
    }

    setupEventListeners() {
        // Additional event listeners can be added here
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.smartDryApp = new SmartDryApp();
});

// Global functions for HTML onclick handlers
function markAllAsRead() {
    if (window.smartDryApp) {
        window.smartDryApp.markAllAsRead();
    }
}

function clearAllNotifications() {
    if (window.smartDryApp) {
        window.smartDryApp.clearAllNotifications();
    }
}