<?php
    session_start();
    if (isset($_SESSION['login'])) {
        header('Location: sewa.php');
        exit; 
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sewa PS</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial;
            text-align: center;
            color: #495982; 
            background: linear-gradient(to bottom, #c0c4d4, #d1d9e5); /* Gradasi warna latar belakang */
        }

        p {
            font-size: 24px;
            margin: 10px 0 20px 0;
        }

        .button {
            padding: 10px 20px;
            font-size: 16px; 
            color: white;
            background-color: #495982;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .button:hover {
            background-color: #353e57;
        }
    </style>
</head>
<body>
    <div>
        <p>Sewa PlayStation dan Main Game Favoritmu!</p>
        <br>
        <a href="login.php" class="button">Login</a>
    </div>
</body>
</html>
