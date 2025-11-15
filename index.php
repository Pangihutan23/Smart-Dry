<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartDry Agro - Sistem Monitoring Cerdas</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-tractor"></i>
                    <div class="logo-text">
                        <h1>SmartDry Agro</h1>
                        <p>IoT Pengeringan Gabah</p>
                    </div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#dashboard" class="nav-item active" data-page="dashboard">
                    <i class="fas fa-chart-line nav-icon"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
                <a href="#control" class="nav-item" data-page="control">
                    <i class="fas fa-cogs nav-icon"></i>
                    <span class="nav-text">Kontrol Manual</span>
                </a>
                <a href="#notifications" class="nav-item" data-page="notifications">
                    <i class="fas fa-bell nav-icon"></i>
                    <span class="nav-text">Notifikasi</span>
                    <span class="nav-badge" id="notification-badge">3</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-details">
                        <div class="user-name">Administrator</div>
                        <div class="user-role">System Admin</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div class="header-content">
                    <div class="header-text">
                        <h1>SmartDry Agro System</h1>
                        <p>Sistem Monitoring dan Kontrol Pengeringan Gabah Berbasis IoT</p>
                    </div>
                    <div class="header-status">
                        <div class="connection-status">
                            <div class="status-dot connected" id="status-dot"></div>
                            <span id="status-text">System Online</span>
                        </div>
                        <div class="current-time" id="current-time"></div>
                    </div>
                </div>
            </div>

            <!-- Page Content -->
            <div class="page-content">
                <!-- Dashboard Page -->
                <div id="dashboard-page" class="page active">
                    <div class="page-header">
                        <h2><i class="fas fa-tachometer-alt"></i> Dashboard Sistem</h2>
                        <p>Monitoring real-time kondisi pengeringan gabah</p>
                    </div>

                    <div class="dashboard-grid">
                        <!-- Sensor Overview -->
                        <div class="sensor-section">
                            <h3 class="section-title">Status Sensor</h3>
                            <div class="sensor-overview">
                                <div class="sensor-card">
                                    <div class="sensor-header">
                                        <i class="fas fa-cloud-rain sensor-icon rainfall"></i>
                                        <div class="sensor-info">
                                            <div class="sensor-title">Sensor Hujan</div>
                                            <div class="sensor-value" id="rainfall-value">0.0 <span class="sensor-unit">mm</span></div>
                                        </div>
                                    </div>
                                    <div class="sensor-status normal">Normal</div>
                                </div>
                                
                                <div class="sensor-card">
                                    <div class="sensor-header">
                                        <i class="fas fa-sun sensor-icon light"></i>
                                        <div class="sensor-info">
                                            <div class="sensor-title">Intensitas Cahaya</div>
                                            <div class="sensor-value" id="light-value">850 <span class="sensor-unit">lux</span></div>
                                        </div>
                                    </div>
                                    <div class="sensor-status normal">Optimal</div>
                                </div>
                                
                                <div class="sensor-card">
                                    <div class="sensor-header">
                                        <i class="fas fa-thermometer-half sensor-icon temp"></i>
                                        <div class="sensor-info">
                                            <div class="sensor-title">Suhu & Kelembapan</div>
                                            <div class="sensor-value" id="temp-humid-value">32°C / 65%</div>
                                        </div>
                                    </div>
                                    <div class="sensor-status normal">Stabil</div>
                                </div>
                                
                                <div class="sensor-card">
                                    <div class="sensor-header">
                                        <i class="fas fa-weight-hanging sensor-icon level"></i>
                                        <div class="sensor-info">
                                            <div class="sensor-title">Level Gabah</div>
                                            <div class="sensor-value" id="distance-value">75 <span class="sensor-unit">%</span></div>
                                        </div>
                                    </div>
                                    <div class="sensor-status warning">Perlu Monitoring</div>
                                </div>
                            </div>
                        </div>

                        <!-- System Status -->
                        <div class="status-section">
                            <div class="status-card">
                                <h3 class="section-title">Status Sistem</h3>
                                <div class="status-overview">
                                    <div class="status-item">
                                        <div class="status-label">Atap Pengering</div>
                                        <div class="status-value closed">Tertutup</div>
                                    </div>
                                    <div class="status-item">
                                        <div class="status-label">Mode Operasi</div>
                                        <div class="status-value auto">Otomatis</div>
                                    </div>
                                    <div class="status-item">
                                        <div class="status-label">Ventilasi</div>
                                        <div class="status-value active">Aktif (75%)</div>
                                    </div>
                                    <div class="status-item">
                                        <div class="status-label">Pemanas</div>
                                        <div class="status-value inactive">Nonaktif</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="stats-card">
                                <h3 class="section-title">Statistik Hari Ini</h3>
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-door-open"></i>
                                        </div>
                                        <div class="stat-info">
                                            <div class="stat-value">2x</div>
                                            <div class="stat-label">Atap Dibuka</div>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-cloud-rain"></i>
                                        </div>
                                        <div class="stat-info">
                                            <div class="stat-value">1x</div>
                                            <div class="stat-label">Kejadian Hujan</div>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                        <div class="stat-info">
                                            <div class="stat-value">8.5h</div>
                                            <div class="stat-label">Waktu Pengeringan</div>
                                        </div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-icon">
                                            <i class="fas fa-bell"></i>
                                        </div>
                                        <div class="stat-info">
                                            <div class="stat-value">3</div>
                                            <div class="stat-label">Notifikasi</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Section -->
                    <div class="charts-section">
                        <div class="chart-container">
                            <div class="chart-header">
                                <h3>Grafik Monitoring Suhu & Kelembapan</h3>
                                <div class="chart-legend">
                                    <div class="legend-item">
                                        <div class="legend-color temp"></div>
                                        <span>Suhu (°C)</span>
                                    </div>
                                    <div class="legend-item">
                                        <div class="legend-color humidity"></div>
                                        <span>Kelembapan (%)</span>
                                    </div>
                                </div>
                            </div>
                            <canvas id="sensorChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Control Page -->
                <div id="control-page" class="page">
                    <div class="page-header">
                        <h2><i class="fas fa-cogs"></i> Kontrol Manual Sistem</h2>
                        <p>Kontrol manual perangkat pengeringan gabah</p>
                    </div>

                    <div class="control-grid">
                        <div class="control-card">
                            <div class="control-header">
                                <i class="fas fa-home control-icon"></i>
                                <h3>Kontrol Atap</h3>
                            </div>
                            <div class="control-body">
                                <div class="control-status">
                                    <div class="status-indicator closed">
                                        <i class="fas fa-door-closed"></i>
                                        <span>Status: Tertutup</span>
                                    </div>
                                </div>
                                <div class="control-actions">
                                    <button class="control-btn primary" id="open-roof-btn">
                                        <i class="fas fa-door-open"></i>
                                        Buka Atap
                                    </button>
                                    <button class="control-btn" id="close-roof-btn">
                                        <i class="fas fa-door-closed"></i>
                                        Tutup Atap
                                    </button>
                                </div>
                                <div class="control-toggle">
                                    <label class="toggle-label">Mode Otomatis</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="control-card">
                            <div class="control-header">
                                <i class="fas fa-fan control-icon"></i>
                                <h3>Kontrol Ventilasi</h3>
                            </div>
                            <div class="control-body">
                                <div class="control-value">75%</div>
                                <input type="range" min="0" max="100" value="75" class="control-slider" id="ventilation-slider">
                                <div class="slider-labels">
                                    <span>Tutup</span>
                                    <span>Sedang</span>
                                    <span>Buka Penuh</span>
                                </div>
                                <div class="control-toggle">
                                    <label class="toggle-label">Aktifkan Ventilasi</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="control-card">
                            <div class="control-header">
                                <i class="fas fa-fire control-icon"></i>
                                <h3>Kontrol Pemanas</h3>
                            </div>
                            <div class="control-body">
                                <div class="control-value">OFF</div>
                                <input type="range" min="0" max="100" value="0" class="control-slider" id="heater-slider">
                                <div class="slider-labels">
                                    <span>OFF</span>
                                    <span>50%</span>
                                    <span>MAX</span>
                                </div>
                                <div class="control-toggle">
                                    <label class="toggle-label">Aktifkan Pemanas</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="control-card">
                            <div class="control-header">
                                <i class="fas fa-bell control-icon"></i>
                                <h3>Pengaturan Notifikasi</h3>
                            </div>
                            <div class="control-body">
                                <div class="notification-settings">
                                    <div class="setting-item">
                                        <label class="setting-label">Notifikasi Hujan</label>
                                        <label class="toggle-switch small">
                                            <input type="checkbox" checked>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="setting-item">
                                        <label class="setting-label">Notifikasi Suhu</label>
                                        <label class="toggle-switch small">
                                            <input type="checkbox" checked>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="setting-item">
                                        <label class="setting-label">Notifikasi Level Gabah</label>
                                        <label class="toggle-switch small">
                                            <input type="checkbox" checked>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="setting-item">
                                        <label class="setting-label">Notifikasi Sistem</label>
                                        <label class="toggle-switch small">
                                            <input type="checkbox" checked>
                                            <span class="toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <button class="control-btn secondary" id="test-notification-btn">
                                    <i class="fas fa-bell"></i>
                                    Test Notifikasi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications Page -->
                <div id="notifications-page" class="page">
                    <div class="page-header">
                        <h2><i class="fas fa-bell"></i> Manajemen Notifikasi</h2>
                        <p>Kelola notifikasi dan log sistem pengeringan</p>
                    </div>

                    <div class="notifications-container">
                        <!-- Notification Stats -->
                        <div class="notification-stats">
                            <div class="stat-card">
                                <div class="stat-icon total">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value">12</div>
                                    <div class="stat-label">Total Notifikasi</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon unread">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value">3</div>
                                    <div class="stat-label">Belum Dibaca</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value">2</div>
                                    <div class="stat-label">Peringatan</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon error">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value">1</div>
                                    <div class="stat-label">Error</div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Actions -->
                        <div class="notification-actions">
                            <button class="action-btn primary" onclick="markAllAsRead()">
                                <i class="fas fa-check-double"></i>
                                Tandai Semua Dibaca
                            </button>
                            <button class="action-btn" onclick="clearAllNotifications()">
                                <i class="fas fa-trash"></i>
                                Hapus Semua
                            </button>
                            <div class="filter-options">
                                <select id="notification-filter">
                                    <option value="all">Semua Notifikasi</option>
                                    <option value="unread">Belum Dibaca</option>
                                    <option value="warning">Peringatan</option>
                                    <option value="error">Error</option>
                                    <option value="info">Informasi</option>
                                </select>
                            </div>
                        </div>

                        <!-- Notifications List -->
                        <div class="notifications-list">
                            <div class="notification-item unread">
                                <div class="notification-icon warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-message">Sensor hujan mendeteksi curah hujan 5mm - Atap ditutup otomatis</div>
                                    <div class="notification-time">5 menit yang lalu</div>
                                </div>
                                <div class="notification-actions">
                                    <button class="icon-btn mark-read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="icon-btn delete">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="notification-item">
                                <div class="notification-icon info">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-message">Level gabah mencapai 75% - Sistem beroperasi normal</div>
                                    <div class="notification-time">1 jam yang lalu</div>
                                </div>
                                <div class="notification-actions">
                                    <button class="icon-btn mark-read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="icon-btn delete">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="notification-item unread error">
                                <div class="notification-icon error">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-message">Gagal terhubung ke sensor suhu - Periksa koneksi hardware</div>
                                    <div class="notification-time">2 jam yang lalu</div>
                                </div>
                                <div class="notification-actions">
                                    <button class="icon-btn mark-read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="icon-btn delete">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="notification-item">
                                <div class="notification-icon success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-message">Sistem ventilasi dinaikkan ke 75% karena kelembapan tinggi</div>
                                    <div class="notification-time">3 jam yang lalu</div>
                                </div>
                                <div class="notification-actions">
                                    <button class="icon-btn mark-read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="icon-btn delete">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="notification-item unread">
                                <div class="notification-icon warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-message">Peringatan: Suhu mencapai 38°C - Ventilasi ditingkatkan</div>
                                    <div class="notification-time">5 jam yang lalu</div>
                                </div>
                                <div class="notification-actions">
                                    <button class="icon-btn mark-read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="icon-btn delete">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>