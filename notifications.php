<?php
// notifications.php
class NotificationSystem {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function addNotification($type, $message, $sensor_data = null, $priority = 'medium') {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO notifications (type, message, sensor_data, priority, created_at) 
                 VALUES (?, ?, ?, ?, NOW())"
            );
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $sensor_json = $sensor_data ? json_encode($sensor_data, JSON_UNESCAPED_UNICODE) : null;
            $stmt->bind_param("ssss", $type, $message, $sensor_json, $priority);
            
            $result = $stmt->execute();
            $notification_id = $stmt->insert_id;
            $stmt->close();
            
            return $notification_id;
        } catch (Exception $e) {
            error_log("Error adding notification: " . $e->getMessage());
            return false;
        }
    }
    
    public function getRecentNotifications($limit = 10, $offset = 0) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM notifications 
                 ORDER BY created_at DESC 
                 LIMIT ? OFFSET ?"
            );
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $notifications = $result->fetch_all(MYSQLI_ASSOC);
            
            $stmt->close();
            return $notifications;
        } catch (Exception $e) {
            error_log("Error getting recent notifications: " . $e->getMessage());
            return [];
        }
    }
    
    public function getUnreadNotifications($limit = 10) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM notifications 
                 WHERE is_read = 0 
                 ORDER BY created_at DESC 
                 LIMIT ?"
            );
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $notifications = $result->fetch_all(MYSQLI_ASSOC);
            
            $stmt->close();
            return $notifications;
        } catch (Exception $e) {
            error_log("Error getting unread notifications: " . $e->getMessage());
            return [];
        }
    }
    
    public function markAsRead($notification_id) {
        try {
            $stmt = $this->db->prepare(
                "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?"
            );
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param("i", $notification_id);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }
    
    public function markAllAsRead() {
        try {
            $stmt = $this->db->prepare(
                "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE is_read = 0"
            );
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Error marking all notifications as read: " . $e->getMessage());
            return false;
        }
    }
    
    public function getNotificationCount($unread_only = false) {
        try {
            $query = "SELECT COUNT(*) as total FROM notifications";
            if ($unread_only) {
                $query .= " WHERE is_read = 0";
            }
            
            $result = $this->db->query($query);
            if (!$result) {
                throw new Exception("Query failed: " . $this->db->error);
            }
            
            $count = $result->fetch_assoc();
            return $count['total'];
        } catch (Exception $e) {
            error_log("Error getting notification count: " . $e->getMessage());
            return 0;
        }
    }
    
    public function deleteOldNotifications($days = 30) {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
            );
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param("i", $days);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Error deleting old notifications: " . $e->getMessage());
            return false;
        }
    }
    
    public function getNotificationsByType($type, $limit = 50) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM notifications 
                 WHERE type = ? 
                 ORDER BY created_at DESC 
                 LIMIT ?"
            );
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param("si", $type, $limit);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $notifications = $result->fetch_all(MYSQLI_ASSOC);
            
            $stmt->close();
            return $notifications;
        } catch (Exception $e) {
            error_log("Error getting notifications by type: " . $e->getMessage());
            return [];
        }
    }
    
    public function deleteNotification($notification_id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM notifications WHERE id = ?");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param("i", $notification_id);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Error deleting notification: " . $e->getMessage());
            return false;
        }
    }
    
    public function clearAllNotifications() {
        try {
            $stmt = $this->db->prepare("DELETE FROM notifications");
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Error clearing all notifications: " . $e->getMessage());
            return false;
        }
    }
    
    public function getNotificationStats() {
        try {
            $stats = [];
            
            // Total notifications
            $stats['total'] = $this->getNotificationCount(false);
            
            // Unread notifications
            $stats['unread'] = $this->getNotificationCount(true);
            
            // Notifications by type
            $types = ['info', 'warning', 'error', 'success'];
            $stats['by_type'] = [];
            
            foreach ($types as $type) {
                $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM notifications WHERE type = ?");
                if ($stmt) {
                    $stmt->bind_param("s", $type);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stats['by_type'][$type] = $result->fetch_assoc()['count'];
                    $stmt->close();
                }
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error getting notification stats: " . $e->getMessage());
            return ['total' => 0, 'unread' => 0, 'by_type' => []];
        }
    }
}

// Class untuk log riwayat
class LogSystem {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function addLog($type, $action, $message, $status = null, $user_id = null) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO system_logs (type, action, message, status, user_id, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param("ssssi", $type, $action, $message, $status, $user_id);
            $result = $stmt->execute();
            $log_id = $stmt->insert_id;
            $stmt->close();
            
            return $log_id;
        } catch (Exception $e) {
            error_log("Error adding log: " . $e->getMessage());
            return false;
        }
    }
    
    public function getRecentLogs($limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM system_logs 
                 ORDER BY created_at DESC 
                 LIMIT ? OFFSET ?"
            );
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            
            $stmt->close();
            return $logs;
        } catch (Exception $e) {
            error_log("Error getting recent logs: " . $e->getMessage());
            return [];
        }
    }
    
    public function getLogsByType($type, $limit = 50) {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM system_logs 
                 WHERE type = ? 
                 ORDER BY created_at DESC 
                 LIMIT ?"
            );
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param("si", $type, $limit);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            
            $stmt->close();
            return $logs;
        } catch (Exception $e) {
            error_log("Error getting logs by type: " . $e->getMessage());
            return [];
        }
    }
    
    public function getRoofStatistics($days = 7) {
        try {
            $stats = [];
            
            // Count roof opens
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM system_logs 
                 WHERE type = 'roof' AND action = 'open' 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"
            );
            
            if ($stmt) {
                $stmt->bind_param("i", $days);
                $stmt->execute();
                $result = $stmt->get_result();
                $stats['opens'] = $result->fetch_assoc()['count'];
                $stmt->close();
            }
            
            // Count roof closes
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM system_logs 
                 WHERE type = 'roof' AND action = 'close' 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"
            );
            
            if ($stmt) {
                $stmt->bind_param("i", $days);
                $stmt->execute();
                $result = $stmt->get_result();
                $stats['closes'] = $result->fetch_assoc()['count'];
                $stmt->close();
            }
            
            // Count rain events
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) as count FROM system_logs 
                 WHERE type = 'rain' 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"
            );
            
            if ($stmt) {
                $stmt->bind_param("i", $days);
                $stmt->execute();
                $result = $stmt->get_result();
                $stats['rain_events'] = $result->fetch_assoc()['count'];
                $stmt->close();
            }
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error getting roof statistics: " . $e->getMessage());
            return ['opens' => 0, 'closes' => 0, 'rain_events' => 0];
        }
    }
    
    public function deleteOldLogs($days = 90) {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
            );
            
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }
            
            $stmt->bind_param("i", $days);
            $result = $stmt->execute();
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("Error deleting old logs: " . $e->getMessage());
            return false;
        }
    }
}

// Function untuk memeriksa ambang batas sensor dan membuat notifikasi
function checkSensorThresholds($data, $notificationSystem) {
    $notifications = [];
    
    // Validasi data yang diperlukan
    if (!isset($data['temperature']) || !isset($data['humidity'])) {
        return $notifications;
    }
    
    // Temperature thresholds
    if ($data['temperature'] > 35) {
        $notificationSystem->addNotification(
            'warning', 
            "🚨 Suhu Tinggi: {$data['temperature']}°C (Batas: 35°C)", 
            $data,
            'high'
        );
        $notifications[] = "Suhu melebihi batas normal: {$data['temperature']}°C";
    } else if ($data['temperature'] < 15) {
        $notificationSystem->addNotification(
            'warning', 
            "❄️ Suhu Rendah: {$data['temperature']}°C (Batas: 15°C)", 
            $data,
            'high'
        );
        $notifications[] = "Suhu di bawah batas normal: {$data['temperature']}°C";
    } else if ($data['temperature'] >= 25 && $data['temperature'] <= 30) {
        $notificationSystem->addNotification(
            'info', 
            "✅ Suhu Optimal: {$data['temperature']}°C", 
            $data,
            'low'
        );
        $notifications[] = "Suhu dalam kondisi optimal";
    }
    
    // Humidity thresholds
    if ($data['humidity'] > 80) {
        $notificationSystem->addNotification(
            'warning', 
            "💧 Kelembapan Tinggi: {$data['humidity']}% (Batas: 80%)", 
            $data,
            'high'
        );
        $notifications[] = "Kelembapan terlalu tinggi: {$data['humidity']}%";
    } else if ($data['humidity'] < 30) {
        $notificationSystem->addNotification(
            'warning', 
            "🏜️ Kelembapan Rendah: {$data['humidity']}% (Batas: 30%)", 
            $data,
            'medium'
        );
        $notifications[] = "Kelembapan terlalu rendah: {$data['humidity']}%";
    } else if ($data['humidity'] >= 40 && $data['humidity'] <= 60) {
        $notificationSystem->addNotification(
            'info', 
            "✅ Kelembapan Optimal: {$data['humidity']}%", 
            $data,
            'low'
        );
        $notifications[] = "Kelembapan dalam kondisi optimal";
    }
    
    // Rainfall detection
    if (isset($data['rainfall']) && $data['rainfall'] > 0) {
        $rainLevel = "";
        $priority = 'medium';
        
        if ($data['rainfall'] < 5) {
            $rainLevel = "ringan";
            $priority = 'low';
        } else if ($data['rainfall'] < 20) {
            $rainLevel = "sedang";
            $priority = 'medium';
        } else {
            $rainLevel = "lebat";
            $priority = 'high';
        }
        
        $notificationSystem->addNotification(
            'info', 
            "🌧️ Hujan {$rainLevel}: {$data['rainfall']}mm", 
            $data,
            $priority
        );
        $notifications[] = "Hujan {$rainLevel} terdeteksi: {$data['rainfall']}mm";
    }
    
    // Light intensity monitoring
    if (isset($data['light_intensity'])) {
        if ($data['light_intensity'] < 100) {
            $notificationSystem->addNotification(
                'warning', 
                "🌑 Cahaya Rendah: {$data['light_intensity']} lux", 
                $data,
                'medium'
            );
            $notifications[] = "Intensitas cahaya rendah";
        } else if ($data['light_intensity'] > 10000) {
            $notificationSystem->addNotification(
                'warning', 
                "☀️ Cahaya Tinggi: {$data['light_intensity']} lux", 
                $data,
                'medium'
            );
            $notifications[] = "Intensitas cahaya sangat tinggi";
        }
    }
    
    // Distance threshold (for grain level)
    if (isset($data['distance']) && $data['distance'] > 0) {
        if ($data['distance'] > 50) {
            $notificationSystem->addNotification(
                'warning', 
                "📦 Level Gabah Rendah: {$data['distance']}%", 
                $data,
                'medium'
            );
            $notifications[] = "Level gabah perlu ditambah: {$data['distance']}%";
        } else if ($data['distance'] < 10) {
            $notificationSystem->addNotification(
                'info', 
                "📦 Level Gabah Penuh: {$data['distance']}%", 
                $data,
                'low'
            );
            $notifications[] = "Level gabah hampir penuh";
        }
    }
    
    // System status notification (first data of the day)
    static $lastDate = null;
    $currentDate = date('Y-m-d');
    if ($lastDate !== $currentDate) {
        $lastDate = $currentDate;
        $notificationSystem->addNotification(
            'info', 
            "🟢 Sistem aktif - " . date('d/m/Y H:i:s'), 
            $data,
            'low'
        );
    }
    
    return $notifications;
}

// Function untuk menangani perubahan status atap
function handleRoofStatusChange($logSystem, $action, $reason = 'manual', $user_id = null) {
    $message = "";
    $status = "";
    
    if ($action === 'open') {
        $message = "Atap dibuka " . ($reason === 'manual' ? 'manual oleh operator' : 'otomatis - ' . $reason);
        $status = 'open';
    } else {
        $message = "Atap ditutup " . ($reason === 'manual' ? 'manual oleh operator' : 'otomatis - ' . $reason);
        $status = 'closed';
    }
    
    return $logSystem->addLog('roof', $action, $message, $status, $user_id);
}

// Function untuk log event hujan
function logRainEvent($logSystem, $intensity, $action = 'detected', $user_id = null) {
    $intensityText = "";
    if ($intensity < 5) {
        $intensityText = "ringan";
    } else if ($intensity < 20) {
        $intensityText = "sedang";
    } else {
        $intensityText = "lebat";
    }
    
    $message = "Hujan {$intensityText} terdeteksi: {$intensity}mm";
    return $logSystem->addLog('rain', $action, $message, 'rain', $user_id);
}

// Function untuk memformat notifikasi agar mudah ditampilkan di frontend
function formatNotificationsForFrontend($notifications) {
    $formatted = [];
    foreach ($notifications as $notif) {
        $formatted[] = [
            'id' => $notif['id'],
            'type' => $notif['type'],
            'message' => $notif['message'],
            'priority' => $notif['priority'] ?? 'medium',
            'timestamp' => $notif['created_at'],
            'is_read' => (bool)$notif['is_read'],
            'sensor_data' => $notif['sensor_data'] ? json_decode($notif['sensor_data'], true) : null,
            'read_at' => $notif['read_at'] ?? null
        ];
    }
    return $formatted;
}

// Function untuk memformat log agar mudah ditampilkan di frontend
function formatLogsForFrontend($logs) {
    $formatted = [];
    foreach ($logs as $log) {
        $formatted[] = [
            'id' => $log['id'],
            'type' => $log['type'],
            'action' => $log['action'],
            'message' => $log['message'],
            'status' => $log['status'],
            'timestamp' => $log['created_at'],
            'user_id' => $log['user_id']
        ];
    }
    return $formatted;
}

// Function untuk membersihkan notifikasi lama (auto-cleanup)
function cleanupOldNotifications($notificationSystem, $days = 7) {
    return $notificationSystem->deleteOldNotifications($days);
}

// Function untuk mengambil notifikasi dengan filter
function getFilteredNotifications($notificationSystem, $filter = 'all', $limit = 50) {
    $filter = strtolower($filter);
    
    switch ($filter) {
        case 'unread':
            return $notificationSystem->getUnreadNotifications($limit);
        case 'warning':
            return $notificationSystem->getNotificationsByType('warning', $limit);
        case 'error':
            return $notificationSystem->getNotificationsByType('error', $limit);
        case 'info':
            return $notificationSystem->getNotificationsByType('info', $limit);
        case 'success':
            return $notificationSystem->getNotificationsByType('success', $limit);
        default:
            return $notificationSystem->getRecentNotifications($limit);
    }
}

// Function untuk menghapus notifikasi berdasarkan ID
function deleteNotification($notificationSystem, $notification_id) {
    return $notificationSystem->deleteNotification($notification_id);
}

// Function untuk mengambil statistik notifikasi
function getNotificationStats($notificationSystem) {
    return $notificationSystem->getNotificationStats();
}

// API Handler untuk notifikasi
class NotificationAPI {
    private $notificationSystem;
    private $logSystem;
    
    public function __construct($db) {
        $this->notificationSystem = new NotificationSystem($db);
        $this->logSystem = new LogSystem($db);
    }
    
    public function handleRequest($method, $input = null) {
        header('Content-Type: application/json');
        
        try {
            switch ($method) {
                case 'GET':
                    $this->handleGetRequest($_GET);
                    break;
                    
                case 'POST':
                    $input = $input ?: json_decode(file_get_contents('php://input'), true);
                    $this->handlePostRequest($input);
                    break;
                    
                case 'PUT':
                    $input = $input ?: json_decode(file_get_contents('php://input'), true);
                    $this->handlePutRequest($input);
                    break;
                    
                case 'DELETE':
                    $input = $input ?: json_decode(file_get_contents('php://input'), true);
                    $this->handleDeleteRequest($input);
                    break;
                    
                default:
                    $this->sendResponse(['success' => false, 'error' => 'Method not allowed'], 405);
            }
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            $this->sendResponse(['success' => false, 'error' => 'Internal server error'], 500);
        }
    }
    
    private function handleGetRequest($params) {
        $action = $params['action'] ?? 'notifications';
        
        switch ($action) {
            case 'notifications':
                $filter = $params['filter'] ?? 'all';
                $limit = min($params['limit'] ?? 10, 100); // Limit max 100
                $offset = $params['offset'] ?? 0;
                
                $notifications = getFilteredNotifications($this->notificationSystem, $filter, $limit);
                $stats = getNotificationStats($this->notificationSystem);
                
                $this->sendResponse([
                    'success' => true,
                    'data' => formatNotificationsForFrontend($notifications),
                    'stats' => $stats,
                    'pagination' => [
                        'limit' => $limit,
                        'offset' => $offset,
                        'total' => $stats['total']
                    ]
                ]);
                break;
                
            case 'logs':
                $filter = $params['filter'] ?? 'all';
                $limit = min($params['limit'] ?? 50, 100);
                $offset = $params['offset'] ?? 0;
                
                if ($filter === 'all') {
                    $logs = $this->logSystem->getRecentLogs($limit, $offset);
                } else {
                    $logs = $this->logSystem->getLogsByType($filter, $limit);
                }
                
                $stats = $this->logSystem->getRoofStatistics();
                
                $this->sendResponse([
                    'success' => true,
                    'data' => formatLogsForFrontend($logs),
                    'stats' => $stats
                ]);
                break;
                
            case 'stats':
                $stats = getNotificationStats($this->notificationSystem);
                $this->sendResponse(['success' => true, 'data' => $stats]);
                break;
                
            default:
                $this->sendResponse(['success' => false, 'error' => 'Invalid action'], 400);
        }
    }
    
    private function handlePostRequest($input) {
        $action = $input['action'] ?? 'add_notification';
        
        switch ($action) {
            case 'add_notification':
                $type = $input['type'] ?? 'info';
                $message = $input['message'] ?? '';
                $priority = $input['priority'] ?? 'medium';
                $sensor_data = $input['sensor_data'] ?? null;
                
                if (empty($message)) {
                    $this->sendResponse(['success' => false, 'error' => 'Message is required'], 400);
                    return;
                }
                
                $id = $this->notificationSystem->addNotification($type, $message, $sensor_data, $priority);
                
                if ($id) {
                    $this->sendResponse([
                        'success' => true,
                        'id' => $id,
                        'message' => 'Notification added successfully'
                    ], 201);
                } else {
                    $this->sendResponse(['success' => false, 'error' => 'Failed to add notification'], 500);
                }
                break;
                
            case 'check_sensors':
                $sensor_data = $input['sensor_data'] ?? [];
                $notifications = checkSensorThresholds($sensor_data, $this->notificationSystem);
                
                $this->sendResponse([
                    'success' => true,
                    'notifications_generated' => count($notifications),
                    'details' => $notifications
                ]);
                break;
                
            case 'add_log':
                $type = $input['type'] ?? 'system';
                $action = $input['log_action'] ?? 'unknown';
                $message = $input['message'] ?? '';
                $status = $input['status'] ?? null;
                $user_id = $input['user_id'] ?? null;
                
                if (empty($message)) {
                    $this->sendResponse(['success' => false, 'error' => 'Message is required'], 400);
                    return;
                }
                
                $id = $this->logSystem->addLog($type, $action, $message, $status, $user_id);
                
                if ($id) {
                    $this->sendResponse([
                        'success' => true,
                        'id' => $id,
                        'message' => 'Log added successfully'
                    ], 201);
                } else {
                    $this->sendResponse(['success' => false, 'error' => 'Failed to add log'], 500);
                }
                break;
                
            default:
                $this->sendResponse(['success' => false, 'error' => 'Invalid action'], 400);
        }
    }
    
    private function handlePutRequest($input) {
        $action = $input['action'] ?? '';
        $id = $input['id'] ?? null;
        
        if (!$id && $action !== 'mark_all_read') {
            $this->sendResponse(['success' => false, 'error' => 'ID is required'], 400);
            return;
        }
        
        switch ($action) {
            case 'mark_read':
                $result = $this->notificationSystem->markAsRead($id);
                $this->sendResponse([
                    'success' => $result,
                    'message' => $result ? 'Notification marked as read' : 'Failed to mark notification'
                ]);
                break;
                
            case 'mark_all_read':
                $result = $this->notificationSystem->markAllAsRead();
                $this->sendResponse([
                    'success' => $result,
                    'message' => $result ? 'All notifications marked as read' : 'Failed to mark notifications'
                ]);
                break;
                
            default:
                $this->sendResponse(['success' => false, 'error' => 'Invalid action'], 400);
        }
    }
    
    private function handleDeleteRequest($input) {
        $id = $input['id'] ?? null;
        
        if (!$id) {
            $this->sendResponse(['success' => false, 'error' => 'ID is required'], 400);
            return;
        }
        
        if ($id === 'all') {
            $result = $this->notificationSystem->clearAllNotifications();
            $this->sendResponse([
                'success' => $result,
                'message' => $result ? 'All notifications cleared' : 'Failed to clear notifications'
            ]);
        } else {
            $result = $this->notificationSystem->deleteNotification($id);
            $this->sendResponse([
                'success' => $result,
                'message' => $result ? 'Notification deleted' : 'Failed to delete notification'
            ]);
        }
    }
    
    private function sendResponse($data, $status_code = 200) {
        http_response_code($status_code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
?>