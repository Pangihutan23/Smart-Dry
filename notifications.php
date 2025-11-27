<?php

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
}

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
}

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
    
    return $notifications;
}

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
?>