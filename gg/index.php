<?php
$mainUrl = "http://localhost/gg/";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartDry Agro - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="navbar">
        <div class="logo">
            <img src="logo.png" alt="logo">
            <h2>SmartDry Agro</h2>
        </div>

        <ul>
            <li><a class="active">Dashboard</a></li>
            <li><a>Notifikasi</a></li>
            <li><a>Kontrol</a></li>
        </ul>
    </nav>

    <!-- =============== LOGIN FORM CENTER =============== -->
    <div class="login-wrapper">
        <div class="login-card">
            <h2>Masuk</h2>
            <p class="login-sub">Silakan login untuk melanjutkan</p>

            <form action="check_login.php" method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit" class="btn-login">Masuk</button>
            </form>
        </div>
    </div>

</body>
</html>
