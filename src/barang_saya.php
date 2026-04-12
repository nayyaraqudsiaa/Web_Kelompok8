<?php
session_start();
require 'koneksi.php';

// cek login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// hanya penjual yang boleh akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'penjual') {
    header("Location: jual_barang.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// ambil hanya barang milik user yang login
$stmt = $conn->prepare("SELECT * FROM tbl_barang WHERE id_user = ? ORDER BY id_barang DESC");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Saya</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
            padding: 20px;
        }

        h1 {
            margin-bottom: 20px;
        }

        .top-link {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            background: #2d5cff;
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
        }

        .container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding-bottom: 15px;
        }

        .card img {
            width: 100%;
            height: 220px;
            object-fit: cover;
            display: block;
        }

        .card-content {
            padding: 15px;
        }

        .card h3 {
            margin: 0 0 10px;
        }

        .harga {
            color: #2d5cff;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .kosong {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
    </style>
</head>
<body>

    <a class="top-link" href="dashboard.php">← Kembali ke Dashboard</a>
    <a class="top-link" href="jual_barang.php">+ Jual Barang</a>

    <h1>Barang Saya</h1>

    <?php if ($result->num_rows > 0): ?>
        <div class="container">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <img src="../assets/uploads/<?= htmlspecialchars($row['gambar']) ?>" alt="Gambar Barang">
                    <div class="card-content">
                        <h3><?= htmlspecialchars($row['nama_barang']) ?></h3>
                        <div class="harga">Rp <?= number_format($row['harga'], 0, ',', '.') ?></div>
                        <p><strong>Jumlah:</strong> <?= htmlspecialchars($row['jumlah']) ?></p>
                        <p><?= htmlspecialchars($row['deskripsi']) ?></p>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="kosong">
            <p>Belum ada barang yang kamu upload.</p>
        </div>
    <?php endif; ?>

</body>
</html>