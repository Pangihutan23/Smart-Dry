<?php
// websocket_server.php - Updated with notifications
require 'vendor/autoload.php';
require 'notifications.php'; // Include notification system

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class SensorWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $db;
    protected $notificationSystem;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->connectDB();
        $this->notificationSystem = new NotificationSystem($this->db);
        echo "WebSocket Server initialized with notification system\n";
    }

    private function connectDB() {
        $host = "localhost";
        $username = "root";
        $password = "";
        $database = "smartdry_agro";

        $this->db = new mysqli($host, $username, $password, $database);
        if ($this->db->connect_error) {
            echo "Database connection failed: " . $this->db->connect_error . "\n";
        } else {
            echo "Database connected successfully\n";
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New client connected! ({$conn->resourceId})\n";
        echo "Total clients: " . count($this->clients) . "\n";
        
        // Send latest data to new client
        $this->sendLatestData($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        echo "Received message from client {$from->resourceId}: {$msg}\n";
        
        try {
            $data = json_decode($msg, true);
            
            if (isset($data['type']) && $data['type'] === 'sensor_data') {
                $this->handleSensorData($data);
            }
        } catch (Exception $e) {
            echo "Error processing message: " . $e->getMessage() . "\n";
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Client {$conn->resourceId} has disconnected\n";
        echo "Total clients: " . count($this->clients) . "\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error with client {$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }

    private function handleSensorData($data) {
        echo "Processing sensor data...\n";
        
        // Save sensor data to database
        $this->saveSensorData($data);
        
        // Check thresholds and create notifications
        $notifications = checkSensorThresholds($data, $this->notificationSystem);
        
        if (!empty($notifications)) {
            echo "Generated notifications: " . implode(", ", $notifications) . "\n";
        }
        
        // Broadcast to all connected clients
        $this->broadcastSensorData($data);
        
        // Also broadcast notifications if any
        if (!empty($notifications)) {
            $this->broadcastNotifications($notifications);
        }
    }

    private function saveSensorData($data) {
        $temperature = isset($data['temperature']) ? floatval($data['temperature']) : null;
        $humidity = isset($data['humidity']) ? floatval($data['humidity']) : null;
        $light_intensity = isset($data['light_intensity']) ? floatval($data['light_intensity']) : null;
        $rainfall = isset($data['rainfall']) ? floatval($data['rainfall']) : null;
        $distance = isset($data['distance']) ? floatval($data['distance']) : null;

        $stmt = $this->db->prepare(
            "INSERT INTO sensor_readings (temperature, humidity, light_intensity, rainfall, distance) 
             VALUES (?, ?, ?, ?, ?)"
        );
        
        if ($stmt) {
            $stmt->bind_param("ddddd", $temperature, $humidity, $light_intensity, $rainfall, $distance);
            if ($stmt->execute()) {
                echo "Data saved to database: Temp={$temperature}°C, Humidity={$humidity}%\n";
            } else {
                echo "Failed to save data: " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            echo "Failed to prepare statement: " . $this->db->error . "\n";
        }
    }

    private function broadcastNotifications($notifications) {
        $response = [
            'type' => 'notifications',
            'notifications' => $notifications,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $message = json_encode($response);
        
        foreach ($this->clients as $client) {
            $client->send($message);
        }
        
        echo "Notifications broadcasted: " . count($notifications) . " items\n";
    }

    private function sendLatestData(ConnectionInterface $conn) {
        $latest_data = $this->getLatestData();
        $chart_data = $this->getChartData();
        $recent_notifications = $this->notificationSystem->getRecentNotifications(5);

        $response = [
            'type' => 'initial_data',
            'latest_data' => $latest_data,
            'chart_data' => $chart_data,
            'notifications' => $recent_notifications
        ];

        $conn->send(json_encode($response));
        echo "Sent initial data with notifications to client {$conn->resourceId}\n";
    }

    private function getLatestData() {
        $query = "SELECT * FROM sensor_readings ORDER BY created_at DESC LIMIT 1";
        $result = $this->db->query($query);
        
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        // Return default data if no data in database
        return [
            'temperature' => 0,
            'humidity' => 0,
            'light_intensity' => 0,
            'rainfall' => 0,
            'distance' => 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }

    private function getChartData() {
        $chart_query = "SELECT temperature, light_intensity, created_at 
                       FROM sensor_readings 
                       WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) 
                       ORDER BY created_at ASC";
        $chart_result = $this->db->query($chart_query);
        
        $chart_data = [];
        if ($chart_result) {
            while ($row = $chart_result->fetch_assoc()) {
                $chart_data[] = $row;
            }
        }
        
        return $chart_data;
    }

    private function broadcastSensorData($data) {
        $response = [
            'type' => 'sensor_update',
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $message = json_encode($response);
        $clientCount = count($this->clients);
        
        echo "Broadcasting to {$clientCount} clients\n";
        
        foreach ($this->clients as $client) {
            $client->send($message);
        }
        
        echo "Broadcast completed\n";
    }
}

// Check if required port is available
$port = 8000;
$address = '0.0.0.0'; // Listen on all interfaces

echo "Starting SmartDry Agro WebSocket Server...\n";
echo "Server will run on: {$address}:{$port}\n";
echo "Press Ctrl+C to stop the server\n";

try {
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new SensorWebSocket()
            )
        ),
        $port,
        $address
    );

    echo "WebSocket server is now running on {$address}:{$port}\n";
    echo "You can access the website and connect to this server\n";
    
    $server->run();
} catch (Exception $e) {
    echo "Failed to start server: " . $e->getMessage() . "\n";
    echo "Possible reasons:\n";
    echo "1. Port {$port} is already in use\n";
    echo "2. You don't have permission to use port {$port}\n";
    echo "3. Another server is running on the same port\n";
    
    // Suggest alternative port
    echo "Try running on different port: php websocket_server.php 8000\n";
}
?>