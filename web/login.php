<?php
session_start();
require_once "/var/www/config.php";

$USERNAME = "admin";
$PASSWORD = "mika";

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'];
    $pass = $_POST['password'];
    if ($user === $USERNAME && $pass === $PASSWORD) {
        $_SESSION['logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Minecraft Panel Login</title>
<style>
html, body {
    margin: 0;
    padding: 0;
    height: 100%;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg,#1f1c2c,#928dab);
    display: flex;
    justify-content: center;
    align-items: center;
}

.login-box {
    background: rgba(0,0,0,0.75);
    padding: 30px 25px;
    border-radius: 16px;
    width: 90%;
    max-width: 360px;
    text-align: center;
    color: #fff;
    box-sizing: border-box;
}

.login-box h2 {
    margin-bottom: 20px;
    font-size: 22px;
}

input {
    width: 100%;
    padding: 12px 15px;
    margin: 10px 0;
    border-radius: 10px;
    border: none;
    font-size: 16px;
    box-sizing: border-box;
}

button {
    width: 100%;
    padding: 12px 15px;
    border: none;
    border-radius: 10px;
    background: #5d3a9b;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    margin-top: 10px;
}

button:hover {
    background: #7c53c0;
}

.error {
    background: #ff5252;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 10px;
    font-size: 14px;
    text-align: center;
}

/* Responsive tweak for small screens */
@media (max-width: 400px) {
    .login-box {
        padding: 20px;
    }
    input, button {
        font-size: 15px;
        padding: 10px;
    }
}
</style>
</head>

<body>
<div class="login-box">
    <h2>Minecraft Panel</h2>

    <?php if($error): ?>
        <div class="error"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>

    <form method="post">
        <input name="username" placeholder="Username" required>
        <input name="password" type="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
