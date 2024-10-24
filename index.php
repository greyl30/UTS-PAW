<?php
    session_start();
    require 'db_connect.php';

    // Cek login
    if (!isset($_SESSION['login'])) {
        header('Location: login.php');
        exit;
    }

    $currentUser = $_SESSION['login'];

    // Ambil daftar PlayStation dari database
    $stmt = $conn->query("SELECT * FROM ps_list");
    $psList = $stmt->fetch_all(MYSQLI_ASSOC);

    // Ambil data sewa dari database
    $stmt = $conn->prepare("SELECT ps_list.*, sewa.photo FROM sewa JOIN ps_list ON sewa.ps_id = ps_list.id WHERE sewa.user_id = (SELECT id FROM user WHERE username = ?)");
    $stmt->bind_param('s', $currentUser);
    $stmt->execute();
    $dataSewa = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Proses sewa
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['close'])) {
            unset($_SESSION['currentPsId']);
            header("Location: index.php");
            exit;
        }

        if (isset($_POST['psId'])) {
            $psId = $_POST['psId'];
            $stmt = $conn->prepare("SELECT * FROM ps_list WHERE id = ? AND status = 'Tersedia'");
            $stmt->bind_param('i', $psId);
            $stmt->execute();
            $ps = $stmt->get_result()->fetch_assoc();

            if ($ps) {
                $_SESSION['currentPsId'] = $psId;
            }
        }

        // Upload
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0 && isset($_SESSION['currentPsId'])) {
            $targetDir = "up_gambar/";
            $targetFile = $targetDir . basename($_FILES["photo"]["name"]);
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFile)) {
                $stmt = $conn->prepare("INSERT INTO sewa (user_id, ps_id, photo) VALUES ((SELECT id FROM user WHERE username = ?), ?, ?)");
                $stmt->bind_param('sis', $currentUser, $_SESSION['currentPsId'], $targetFile);
                $stmt->execute();

                $stmt = $conn->prepare("UPDATE ps_list SET status = 'Booked' WHERE id = ?");
                $stmt->bind_param('i', $_SESSION['currentPsId']);
                $stmt->execute();

                unset($_SESSION['currentPsId']);
                header("Location: index.php");
                exit;
            }
        }

        // Delete
        if (isset($_POST['delete'])) {
            $deleteId = $_POST['deleteId'];
            $stmt = $conn->prepare("DELETE FROM sewa WHERE ps_id = ? AND user_id = (SELECT id FROM user WHERE username = ?)");
            $stmt->bind_param('is', $deleteId, $currentUser);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE ps_list SET status = 'Tersedia' WHERE id = ?");
            $stmt->bind_param('i', $deleteId);
            $stmt->execute();

            header("Location: index.php");
            exit;
        }

        // Update
        if (isset($_POST['update'])) {
            $updateId = $_POST['updateId'];
    
            // Cek apakah foto baru diupload
            if (isset($_FILES['newPhoto']) && $_FILES['newPhoto']['error'] == 0) {
                $targetDir = "up_gambar/"; // Ubah sesuai folder Anda
                $targetFile = $targetDir . basename($_FILES["newPhoto"]["name"]);
                if (move_uploaded_file($_FILES["newPhoto"]["tmp_name"], $targetFile)) {
                    // Update data sewa dengan foto baru
                    $stmt = $conn->prepare("UPDATE sewa SET photo = ? WHERE ps_id = ? AND user_id = (SELECT id FROM user WHERE username = ?)");
                    $stmt->bind_param('sis', $targetFile, $updateId, $currentUser);
                    $stmt->execute();
    
                    header("Location: index.php"); // Redirect untuk menyegarkan halaman
                    exit; 
                } else {
                    echo "Error uploading file.";
                }
            }
        }

        // Logout
        if (isset($_POST['logout'])) {
            session_destroy();
            header('Location: login.php');
            exit;
        }
    }

    // Handle detail popup
    $detailPs = null;
    if (isset($_GET['id'])) {
        $psId = $_GET['id'];

        $stmt = $conn->prepare("SELECT * FROM ps_list WHERE id = ?");
        $stmt->bind_param('i', $psId);
        $stmt->execute();
        $detailPs = $stmt->get_result()->fetch_assoc();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sewa PS</title>
    <link rel="stylesheet" href="sindex.css">
</head>
<body>
    <header>
        <h1>Game Page</h1>
        <form method="POST" action="">
            <button type="submit" name="logout">Logout</button>
        </form>
    </header>
    <h2>Selamat Datang, <?= htmlspecialchars($currentUser) ?></h2>

    <h2>Daftar PlayStation</h2>
    <ul>
        <?php foreach ($psList as $ps): ?>
            <li>
                <?= htmlspecialchars($ps['name']) ?> - <?= htmlspecialchars($ps['status']) ?>
                <div>
                    <?php if ($ps['status'] == 'Tersedia'): ?>
                        <form method="GET" action="">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($ps['id']) ?>">
                            <button type="submit">Detail</button>
                        </form>
                    <?php else: ?>
                        <span>Tidak tersedia</span>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <h2>Hasil Sewa</h2>
    <ul>
        <?php if (!empty($dataSewa)): ?>
            <?php foreach ($dataSewa as $item): ?>
                <li>
                    <?= htmlspecialchars($item['name']) ?>
                    <?php if (!empty($item['photo'])): ?>
                        <img src="<?= htmlspecialchars($item['photo']) ?>" alt="Bukti sewa">
                    <?php endif; ?>
                    <div>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="deleteId" value="<?= htmlspecialchars($item['id']) ?>">
                            <button type="submit" name="delete">Delete</button>
                        </form>
                        <form method="post" enctype="multipart/form-data" style="display:inline;">
                            <input type="hidden" name="updateId" value="<?= htmlspecialchars($item['id']) ?>">
                            <input type="file" name="newPhoto" required>
                            <button type="submit" name="update">Update</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Tidak ada hasil sewa.</p>
        <?php endif; ?>
    </ul>

    <?php if ($detailPs): ?>
        <div class="overlay" style="display:block;"></div>
        <div class="popup" style="display:block;">
            <span class="close" onclick="document.querySelector('.popup').style.display='none'; document.querySelector('.overlay').style.display='none';">×</span>
            <h2>Detail PlayStation</h2>
            <p><strong>Nama :</strong> <?= htmlspecialchars($detailPs['name']) ?></p>
            <p><strong>Deskripsi :</strong> <?= htmlspecialchars($detailPs['description']) ?></p>
            <p><strong>Status :</strong> <?= htmlspecialchars($detailPs['status']) ?></p>
            <p><strong>Syarat :</strong> <?= htmlspecialchars($detailPs['syarat']) ?></p> 
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="psId" value="<?= htmlspecialchars($detailPs['id']) ?>">
                <label for="photo">Upload Foto KTM :</label>
                <input type="file" name="photo" required>
                <button type="submit">Sewa</button>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>
