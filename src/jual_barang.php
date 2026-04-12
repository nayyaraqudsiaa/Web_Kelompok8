<?php
session_start();
require 'koneksi.php';

// cek login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// kalau role belum penjual, otomatis ubah jadi penjual
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'penjual') {
    $updateRole = $conn->prepare("UPDATE tbl_user SET role = 'penjual' WHERE id_user = ?");
    $updateRole->bind_param("i", $id_user);
    $updateRole->execute();
    $_SESSION['role'] = 'penjual';
}

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_barang = trim($_POST['nama_barang']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga = (int) $_POST['harga'];
    $jumlah = (int) $_POST['jumlah'];

    if (empty($nama_barang) || empty($deskripsi) || $harga <= 0 || $jumlah <= 0) {
        $error = "Semua field wajib diisi dengan benar.";
    } elseif (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== 0) {
        $error = "Gambar barang wajib diupload.";
    } else {
        $folder = "../assets/uploads/";

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $namaAsli = $_FILES['gambar']['name'];
        $tmpFile = $_FILES['gambar']['tmp_name'];
        $ukuranFile = $_FILES['gambar']['size'];

        $ext = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $error = "Format gambar harus jpg, jpeg, png, atau webp.";
        } elseif ($ukuranFile > 2 * 1024 * 1024) {
            $error = "Ukuran gambar maksimal 2 MB.";
        } else {
            $namaFileBaru = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $namaAsli);

            if (move_uploaded_file($tmpFile, $folder . $namaFileBaru)) {
                $stmt = $conn->prepare("INSERT INTO tbl_barang (nama_barang, deskripsi, harga, jumlah, gambar, id_user) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiisi", $nama_barang, $deskripsi, $harga, $jumlah, $namaFileBaru, $id_user);

                if ($stmt->execute()) {
                    $success = "Barang berhasil ditambahkan.";
                } else {
                    $error = "Gagal menyimpan data barang ke database.";
                }
            } else {
                $error = "Gagal upload gambar.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jual Barang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6fb;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        h1 {
            margin-top: 0;
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

        .success {
            background: #e8fff0;
            color: #127a3f;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .error {
            background: #ffeaea;
            color: #b42318;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-top: 14px;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        button {
            margin-top: 20px;
            background: #2d5cff;
            color: white;
            border: none;
            padding: 12px 18px;
            border-radius: 8px;
            cursor: pointer;
        }

        button:hover {
            background: #1f49d8;
        }

        .actions {
            margin-top: 18px;
        }

        .actions a {
            margin-right: 10px;
            text-decoration: none;
            color: #2d5cff;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <a class="top-link" href="dashboard.php">← Kembali ke Dashboard</a>

    <div class="container">
        <h1>Jual Barang</h1>

        <?php if (!empty($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label for="nama_barang">Nama Barang</label>
            <input type="text" id="nama_barang" name="nama_barang" required>

            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" required></textarea>

            <label for="harga">Harga</label>
            <input type="number" id="harga" name="harga" min="1" required>

            <label for="jumlah">Jumlah</label>
            <input type="number" id="jumlah" name="jumlah" min="1" required>

            <label for="gambar">Gambar</label>
            <input type="file" id="gambar" name="gambar" accept=".jpg,.jpeg,.png,.webp" required>

            <button type="submit">Upload Barang</button>
        </form>

        <div class="actions">
            <a href="barang_saya.php">Lihat Barang Saya</a>
        </div>
    </div>

</body>
</html>