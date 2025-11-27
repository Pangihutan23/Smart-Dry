<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // kalau belum login → ke login
    exit();
}
?>

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
                <a href="#logs" class="nav-item" data-page="logs">
                    <i class="fas fa-history nav-icon"></i>
                    <span class="nav-text">Riwayat Sistem</span>
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
                <div class="logout-btn">
                    <a href="logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
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
                            <h3 class="section-title">
                                <i class="fas fa-microchip"></i> Status Sensor
                                <button class="refresh-btn" id="refresh-sensors">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </h3>
                            <div class="sensor-overview">
                                <!-- Sensor cards will be populated by JavaScript -->
                            </div>

                            <!-- Quick Actions -->
                            <div class="quick-actions">
                                <h3 class="section-title">
                                    <i class="fas fa-bolt"></i> Aksi Cepat
                                </h3>
                                <div class="action-buttons">
                                    <button class="action-btn primary" id="quick-open-roof">
                                        <i class="fas fa-door-open"></i>
                                        Buka Atap
                                    </button>
                                    <button class="action-btn secondary" id="quick-close-roof">
                                        <i class="fas fa-door-closed"></i>
                                        Tutup Atap
                                    </button>
                                    <button class="action-btn" id="emergency-stop">
                                        <i class="fas fa-stop-circle"></i>
                                        Stop Darurat
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- System Status & Notifications -->
                        <div class="status-section">
                            <!-- System Status -->
                            <div class="status-card">
                                <h3 class="section-title">
                                    <i class="fas fa-info-circle"></i> Status Sistem
                                    <span class="status-badge active" id="system-status-badge">AKTIF</span>
                                </h3>
                                <div class="status-overview">
                                    <div class="status-item clickable" data-action="toggle-roof">
                                        <div class="status-label">
                                            <i class="fas fa-home"></i>
                                            Atap Pengering
                                        </div>
                                        <div class="status-value closed" id="roof-status">Tertutup</div>
                                    </div>
                                    <div class="status-item clickable" data-action="toggle-mode">
                                        <div class="status-label">
                                            <i class="fas fa-robot"></i>
                                            Mode Operasi
                                        </div>
                                        <div class="status-value auto" id="operation-mode">Otomatis</div>
                                    </div>
                                    <div class="status-item clickable" data-action="adjust-ventilation">
                                        <div class="status-label">
                                            <i class="fas fa-fan"></i>
                                            Ventilasi
                                        </div>
                                        <div class="status-value active" id="ventilation-status">Aktif (75%)</div>
                                    </div>
                                    <div class="status-item clickable" data-action="toggle-heater">
                                        <div class="status-label">
                                            <i class="fas fa-fire"></i>
                                            Pemanas
                                        </div>
                                        <div class="status-value inactive" id="heater-status">Nonaktif</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Notifications Preview -->
                            <div class="notifications-preview">
                                <h3 class="section-title">
                                    <i class="fas fa-bell"></i> Notifikasi Terbaru
                                    <span class="view-all" onclick="smartDryApp.showPage('notifications')">Lihat Semua</span>
                                </h3>
                                <div class="notification-preview-list" id="notification-preview-list">
                                    <!-- Notifications will be populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Section -->
                    <div class="charts-section">
                        <div class="chart-container">
                            <div class="chart-header">
                                <h3>Grafik Monitoring Sensor</h3>
                                <div class="chart-controls">
                                    <select id="chart-time-range">
                                        <option value="1h">1 Jam Terakhir</option>
                                        <option value="6h">6 Jam Terakhir</option>
                                        <option value="24h">24 Jam Terakhir</option>
                                        <option value="7d">7 Hari Terakhir</option>
                                    </select>
                                    <button class="chart-export-btn">
                                        <i class="fas fa-download"></i> Export
                                    </button>
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
                        <!-- Roof Control -->
                        <div class="control-card">
                            <div class="control-header">
                                <i class="fas fa-home control-icon"></i>
                                <h3>Kontrol Atap</h3>
                            </div>
                            <div class="control-body">
                                <div class="control-status">
                                    <div class="status-indicator closed" id="roof-control-status">
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
                                        <input type="checkbox" id="auto-roof-toggle" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Ventilation Control -->
                        <div class="control-card">
                            <div class="control-header">
                                <i class="fas fa-fan control-icon"></i>
                                <h3>Kontrol Ventilasi</h3>
                            </div>
                            <div class="control-body">
                                <div class="control-value" id="ventilation-value">75%</div>
                                <input type="range" min="0" max="100" value="75" class="control-slider" id="ventilation-slider">
                                <div class="slider-labels">
                                    <span>Tutup</span>
                                    <span>Sedang</span>
                                    <span>Buka Penuh</span>
                                </div>
                                <div class="control-toggle">
                                    <label class="toggle-label">Aktifkan Ventilasi</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="ventilation-toggle" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Heater Control -->
                        <div class="control-card">
                            <div class="control-header">
                                <i class="fas fa-fire control-icon"></i>
                                <h3>Kontrol Pemanas</h3>
                            </div>
                            <div class="control-body">
                                <div class="control-value" id="heater-value">OFF</div>
                                <input type="range" min="0" max="100" value="0" class="control-slider" id="heater-slider">
                                <div class="slider-labels">
                                    <span>OFF</span>
                                    <span>50%</span>
                                    <span>MAX</span>
                                </div>
                                <div class="control-toggle">
                                    <label class="toggle-label">Aktifkan Pemanas</label>
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="heater-toggle">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Settings -->
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
                            <div class="stat-card clickable" data-filter="all">
                                <div class="stat-icon total">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value" id="total-notifications">12</div>
                                    <div class="stat-label">Total Notifikasi</div>
                                </div>
                            </div>
                            <div class="stat-card clickable" data-filter="unread">
                                <div class="stat-icon unread">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value" id="unread-notifications">3</div>
                                    <div class="stat-label">Belum Dibaca</div>
                                </div>
                            </div>
                            <div class="stat-card clickable" data-filter="warning">
                                <div class="stat-icon warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value" id="warning-notifications">2</div>
                                    <div class="stat-label">Peringatan</div>
                                </div>
                            </div>
                            <div class="stat-card clickable" data-filter="error">
                                <div class="stat-icon error">
                                    <i class="fas fa-times-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value" id="error-notifications">1</div>
                                    <div class="stat-label">Error</div>
                                </div>
                            </div>
                        </div>

                        <!-- Notification Actions -->
                        <div class="notification-actions">
                            <button class="action-btn primary" id="mark-all-read-btn">
                                <i class="fas fa-check-double"></i>
                                Tandai Semua Dibaca
                            </button>
                            <button class="action-btn" id="clear-all-btn">
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
                        <div class="notifications-list" id="notifications-list-full">
                            <!-- Notifications will be populated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Logs Page -->
                <div id="logs-page" class="page">
                    <div class="page-header">
                        <h2><i class="fas fa-history"></i> Riwayat Sistem</h2>
                        <p>Log aktivitas dan riwayat sistem pengeringan</p>
                    </div>

                    <div class="logs-container">
                        <!-- Log Statistics -->
                        <div class="log-stats">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-door-open"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value" id="roof-opens">0</div>
                                    <div class="stat-label">Atap Dibuka</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-door-closed"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value" id="roof-closes">0</div>
                                    <div class="stat-label">Atap Ditutup</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-cloud-rain"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value" id="rain-events">0</div>
                                    <div class="stat-label">Kejadian Hujan</div>
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-robot"></i>
                                </div>
                                <div class="stat-info">
                                    <div class="stat-value" id="auto-actions">0</div>
                                    <div class="stat-label">Aksi Otomatis</div>
                                </div>
                            </div>
                        </div>

                        <!-- Log Tabs -->
                        <div class="log-tabs">
                            <button class="log-tab active" data-filter="all">Semua Aktivitas</button>
                            <button class="log-tab" data-filter="roof">Atap</button>
                            <button class="log-tab" data-filter="system">Sistem</button>
                            <button class="log-tab" data-filter="rain">Hujan</button>
                            <button class="log-tab" data-filter="sensor">Sensor</button>
                        </div>

                        <!-- Log List -->
                        <div class="log-list" id="log-items">
                            <!-- Log items will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Notification Badge -->
    <div id="quick-notification-badge" class="quick-notification-badge hidden">
        🔔
        <div class="badge-count">0</div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>