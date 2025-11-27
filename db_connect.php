<?php
$host = "127.0.0.1";
$username = "root";
$password = "";
$database = "smartdry_agro";
$port = 3306; // ganti sesuai port MySQL kamu

$db = new mysqli($host, $username, $password, $database, $port);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
?>
