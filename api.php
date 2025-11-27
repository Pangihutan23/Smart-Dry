<?php
// api.php - VERSI DIPERBAIKI
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'notifications.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if ($data === null) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid JSON data"]);
        exit;
    }
    
    // Validasi data yang diperlukan
    if (!isset($data['temperature']) || !isset($data['humidity'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit;
    }
    
    // Simpan ke database
    if (saveToDatabase($data)) {
        // Cek threshold dan buat notifikasi
        $notificationSystem = new NotificationSystem(getDBConnection());
        checkSensorThresholds($data, $notificationSystem);
        
        echo json_encode(["status" => "success", "message" => "Data saved successfully"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to save data"]);
    }
} 
else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Handle GET requests untuk frontend
    $action = $_GET['action'] ?? '';
    
    switch($action) {
        case 'latest_data':
            echo json_encode(getLatestSensorData());
            break;
        case 'notifications':
            $filter = $_GET['filter'] ?? 'all';
            $limit = $_GET['limit'] ?? 10;
            echo json_encode(getFilteredNotifications($filter, $limit));
            break;
        case 'logs':
            $filter = $_GET['filter'] ?? 'all';
            $limit = $_GET['limit'] ?? 50;
            echo json_encode(getFilteredLogs($filter, $limit));
            break;
        default:
            echo json_encode(["status" => "error", "message" => "Invalid action"]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
}

function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $host = "localhost";
        $username = "root";
        $password = "";
        $database = "smartdry_agro";
        
        $conn = new mysqli($host, $username, $password, $database);
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            return false;
        }
    }
    return $conn;
}

function saveToDatabase($data) {
    $conn = getDBConnection();
    if (!$conn) return false;

    try {
        $temperature = floatval($data['temperature']);
        $humidity = floatval($data['humidity']);
        $light_intensity = isset($data['light_intensity']) ? floatval($data['light_intensity']) : 0;
        $rainfall = isset($data['rainfall']) ? floatval($data['rainfall']) : 0;
        $distance = isset($data['distance']) ? floatval($data['distance']) : 0;
        $roof_status = isset($data['roof_status']) ? $data['roof_status'] : 'unknown';
        $auto_mode = isset($data['auto_mode']) ? ($data['auto_mode'] ? 1 : 0) : 1;

        $stmt = $conn->prepare(
            "INSERT INTO sensor_readings (temperature, humidity, light_intensity, rainfall, distance, roof_status, auto_mode) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("dddddss", $temperature, $humidity, $light_intensity, $rainfall, $distance, $roof_status, $auto_mode);
        $result = $stmt->execute();
        
        $stmt->close();
        return $result;
        
    } catch (Exception $e) {
        error_log("Database error: " . $e->getMessage());
        return false;
    }
}

function getLatestSensorData() {
    $conn = getDBConnection();
    if (!$conn) return [];
    
    $result = $conn->query(
        "SELECT * FROM sensor_readings 
         ORDER BY created_at DESC 
         LIMIT 1"
    );
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return [];
}

function getFilteredNotifications($filter = 'all', $limit = 10) {
    $conn = getDBConnection();
    if (!$conn) return [];
    
    $notificationSystem = new NotificationSystem($conn);
    
    switch($filter) {
        case 'unread':
            $notifications = $notificationSystem->getUnreadNotifications($limit);
            break;
        case 'warning':
            $notifications = $notificationSystem->getNotificationsByType('warning', $limit);
            break;
        case 'error':
            $notifications = $notificationSystem->getNotificationsByType('error', $limit);
            break;
        default:
            $notifications = $notificationSystem->getRecentNotifications($limit);
    }
    
    return formatNotificationsForFrontend($notifications);
}

function getFilteredLogs($filter = 'all', $limit = 50) {
    $conn = getDBConnection();
    if (!$conn) return [];
    
    $logSystem = new LogSystem($conn);
    
    if ($filter === 'all') {
        $logs = $logSystem->getRecentLogs($limit);
    } else {
        $logs = $logSystem->getLogsByType($filter, $limit);
    }
    
    return formatLogsForFrontend($logs);
}
?>