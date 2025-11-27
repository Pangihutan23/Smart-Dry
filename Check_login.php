<?php
session_start();

// Ambil data dari form
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Cek login
if ($username === "admin" && $password === "admin123") {
    $_SESSION['username'] = $username;
    header("Location: index.php"); // redirect ke dashboard
    exit();
} else {
    echo "Username atau password salah!";
}
?>
