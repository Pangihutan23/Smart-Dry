<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$username = $_POST['username'];
$password = $_POST['password'];

if ($username === "admin" && $password === "admin123") {
    echo "Login berhasil!";
} else {
    echo "Username atau password salah!";
}
?>
