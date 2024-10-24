<?php
    session_start();
    require 'db_connect.php';

    // Cek login
    if (isset($_SESSION['login'])) {
        header('location: index.php');
        exit;
    }

    $action = isset($_GET['action']) ? $_GET['action'] : 'login';
    $message = ''; 

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Register
        if ($action == 'register') {
            $stmt = $conn->prepare("INSERT INTO user (username, password) VALUES (?, ?)");
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("ss", $username, $hashedPassword);
            $stmt->execute();
            $message = "Register berhasil. Silakan Login";
            $stmt->close();
        }

        // Login
        if ($action == 'login') {
            $stmt = $conn->prepare("SELECT * FROM user WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['login'] = $username;
                header('location: index.php');
                exit;
            } else {
                $message = "Mohon register terlebih dahulu.";
            }
            $stmt->close();
        }
    }
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="slogin.css"> 

</head>
<body>
    <div class="container">
        <h1><?php echo ucfirst($action);?></h1>
        <form method="post">
            <input type="text" name="username" placeholder="Username" required> <br>
            <input type="password" name="password" placeholder="Password" required>
            <br>
            <button class="button" type="submit"><?php echo ucfirst($action); ?></button>
            <?php if (!empty($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
        </form>

        <div class="link">
            <?php if ($action == 'login'): ?>
                <p>Belum punya akun? <a href="?action=register">Register di sini</a></p>
            <?php else: ?>
                <p>Sudah punya akun? <a href="?action=login">Login di sini</a></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
