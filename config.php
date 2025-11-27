<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smartdry_agro');
define('SENSOR_THRESHOLDS', [
    'temperature' => ['warning' => 35, 'critical' => 40],
    'humidity' => ['warning' => 80, 'critical' => 90],
    'light' => ['warning' => 100, 'critical' => 50],
    'rainfall' => ['warning' => 5, 'critical' => 10]
]);
?>