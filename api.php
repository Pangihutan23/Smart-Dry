<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/notifications.php';

$method = $_SERVER['REQUEST_METHOD'];

/* ============================================================
   DATABASE CONNECTION
============================================================ */
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {

        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            error_log("DB Connection Failed: " . $conn->connect_error);
            return false;
        }

        $conn->set_charset("utf8mb4");
    }
    return $conn;
}

/* ============================================================
   GET SENSOR LATEST DATA
============================================================ */
if ($method === 'GET') {

    $action = $_GET['action'] ?? 'info';
    $conn = getDBConnection();

    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "DB connection failed"]);
        exit();
    }

    /* --------------------
       #1 — latest_data
    -------------------- */
    if ($action === 'latest_data') {

        $sql = "SELECT temperature, humidity, light_intensity, rainfall, distance,
                       roof_status, auto_mode, created_at
                FROM sensor_readings
                ORDER BY created_at DESC
                LIMIT 1";

        $result = $conn->query($sql);

        if ($result && $data = $result->fetch_assoc()) {
            $data['auto_mode'] = (int)$data['auto_mode'];
            echo json_encode($data);
        } else {
            echo json_encode([]);
        }
        exit();
    }

    /* --------------------
       #2 — notifications
    -------------------- */
    if ($action === 'notifications') {

        $notif = new NotificationSystem($conn);
        $filter = $_GET['filter'] ?? 'all';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

        switch ($filter) {
            case 'unread':
                $list = $notif->getUnreadNotifications($limit);
                break;
            case 'warning':
            case 'error':
            case 'info':
                $list = $notif->getNotificationsByType($filter, $limit);
                break;
            default:
                $list = $notif->getRecentNotifications($limit, 0);
        }

        echo json_encode(formatNotificationsForFrontend($list));
        exit();
    }

    /* --------------------
       #3 — logs
    -------------------- */
    // GET SYSTEM LOGS
if ($action === 'logs') {

    // gunakan LogSystem, bukan NotificationSystem
    $logSystem = new LogSystem($conn);

    $filter = $_GET['filter'] ?? 'all';
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;

    if ($filter === 'all') {
        $logs = $logSystem->getRecentLogs($limit, 0);
    } else {
        $logs = $logSystem->getLogsByType($filter, $limit);
    }

    echo json_encode(formatLogsForFrontend($logs));
    exit();
}

    /* --------------------
       #4 — set_command (kontrol atap dari web)
    -------------------- */
    if ($action === 'set_command') {
        $command  = $_GET['command'] ?? '';
        $deviceId = $_GET['device_id'] ?? 'ESP8266_SmartDry';

        if ($command === '') {
            echo json_encode([
                "status"  => "error",
                "message" => "Command tidak boleh kosong"
            ]);
            exit();
        }

        // Simpan perintah ke tabel control_commands
        $stmt = $conn->prepare(
            "INSERT INTO control_commands (device_id, command, processed, created_at)
             VALUES (?, ?, 0, NOW())"
        );
        $stmt->bind_param("ss", $deviceId, $command);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            "status"  => "success",
            "message" => "Perintah disimpan",
            "command" => $command
        ]);

        // IMPORT NotificationSystem & LogSystem
        require_once "notifications.php";

        // Buat objek Notifikasi & Log
        $notif = new NotificationSystem();
        $log   = new LogSystem();

        // Tentukan pesan
        $messages = [
            "open"     => ["Atap Dibuka", "Atap sedang dibuka melalui kontrol manual"],
            "close"    => ["Atap Ditutup", "Atap ditutup oleh operator"],
            "half"     => ["Atap Setengah Terbuka", "Posisi atap diubah ke setengah"],
            "auto_on"  => ["Mode Otomatis Aktif", "Atap sekarang dikendalikan otomatis"],
            "auto_off" => ["Mode Manual Aktif", "Atap sekarang dikendalikan manual"]
        ];

        if (isset($messages[$command])) {
            list($title, $desc) = $messages[$command];

            // Simpan notifikasi
            $notif->addNotification($title, $desc, "info");

            // Simpan log
            $log->addLog("roof", $command, $desc);
        }
        exit();
    }

    /* --------------------
       #5 — get_command (dibaca ESP8266)
    -------------------- */
    if ($action === 'get_command') {
        $deviceId = $_GET['device_id'] ?? 'ESP8266_SmartDry';

        $stmt = $conn->prepare(
            "SELECT id, command
             FROM control_commands
             WHERE processed = 0 AND device_id = ?
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->bind_param("s", $deviceId);
        $stmt->execute();
        $stmt->bind_result($id, $command);

        $hasRow = $stmt->fetch();
        $stmt->close();

        // Override header untuk endpoint ini → plain text
        header('Content-Type: text/plain');

        if ($hasRow) {
            // Tandai sebagai sudah diproses
            $upd = $conn->prepare("UPDATE control_commands SET processed = 1 WHERE id = ?");
            $upd->bind_param("i", $id);
            $upd->execute();
            $upd->close();

            echo $command; // contoh: open / close / auto_on / auto_off
        } else {
            echo ""; // tidak ada perintah pending
        }

        exit();
    }

    /* --------------------
       #4 — API info
    -------------------- */
    echo json_encode([
        "status" => "success",
        "message" => "SmartDry API Running",
        "time" => date('Y-m-d H:i:s')
    ]);
    exit();
}

/* ============================================================
   POST — RECEIVE DATA FROM ESP8266
============================================================ */

if ($method === 'POST') {

    $input = file_get_contents("php://input");
    $json = json_decode($input, true);

    if (!$json) {
        echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
        exit();
    }

    $conn = getDBConnection();
    if (!$conn) {
        echo json_encode(["status" => "error", "message" => "DB connection failed"]);
        exit();
    }

    /* Insert to DB */
    $stmt = $conn->prepare(
        "INSERT INTO sensor_readings 
        (temperature, humidity, light_intensity, rainfall,
         distance, roof_status, auto_mode)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "dddddsi",
        $json['temperature'],
        $json['humidity'],
        $json['light_intensity'],
        $json['rainfall'],
        $json['distance'],
        $json['roof_status'],
        $json['auto_mode']
    );

    $stmt->execute();
    
    require_once "notifications.php";
    $notif = new NotificationSystem();
    $log   = new LogSystem();

    // Ambil status atap sebelumnya (data sebelum row baru ini)
    $stmtPrev = $conn->prepare("
        SELECT roof_status 
        FROM sensor_readings
        ORDER BY id DESC
        LIMIT 1 OFFSET 1
    ");
    $stmtPrev->execute();
    $stmtPrev->bind_result($prevRoofStatus);
    $stmtPrev->fetch();
    $stmtPrev->close();

    $currentRoof = $json['roof_status'];

    // Jika berubah → kirim notifikasi otomatis
    if ($prevRoofStatus !== $currentRoof) {

        if ($currentRoof === "closed") {
            $notif->addNotification(
                "Atap Ditutup Otomatis",
                "Atap ditutup otomatis karena terdeteksi hujan.",
                "warning"
            );
            $log->addLog("roof", "auto_close", "Atap ditutup otomatis karena hujan.");
        }

        if ($currentRoof === "half_open") {
            $notif->addNotification(
                "Atap Setengah Terbuka",
                "Atap disetel otomatis ke posisi setengah karena kondisi mendung.",
                "info"
            );
            $log->addLog("roof", "auto_half", "Atap setengah terbuka otomatis (mendung).");
        }

        if ($currentRoof === "open") {
            $notif->addNotification(
                "Atap Dibuka Otomatis",
                "Atap dibuka penuh karena cuaca cerah.",
                "success"
            );
            $log->addLog("roof", "auto_open", "Atap dibuka otomatis karena cerah.");
        }

        // Update last_roof_status untuk row terbaru
        $stmtUpdate = $conn->prepare("
            UPDATE sensor_readings 
            SET last_roof_status = ?
            WHERE id = (
                SELECT id FROM (
                    SELECT id FROM sensor_readings ORDER BY id DESC LIMIT 1
                ) AS tmp
            )
        ");
        $stmtUpdate->bind_param("s", $prevRoofStatus);
        $stmtUpdate->execute();
        $stmtUpdate->close();
    }

        
        $stmt->close();

        echo json_encode([
            "status" => "success",
            "message" => "Data received",
            "received" => $json
        ]);
        exit();
    }

http_response_code(405);
echo json_encode(["status" => "error", "message" => "Method not allowed"]);
exit();
?>
