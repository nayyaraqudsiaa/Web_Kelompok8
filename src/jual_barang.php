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
    <title>Jual Barang - Rekos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc; /* slate-50 */
            margin: 0;
            padding-bottom: 40px;
        }

        .form-container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }

        .success {
            background: #e8fff0;
            color: #127a3f;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #bbf7d0;
        }

        .error {
            background: #ffeaea;
            color: #b42318;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fecaca;
        }

        label {
            display: block;
            margin-top: 14px;
            margin-bottom: 6px;
            font-weight: bold;
            color: #334155;
            font-size: 14px;
        }

        input, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-sizing: border-box;
            background-color: #f8fafc;
            transition: border-color 0.2s;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: #3b82f6;
            background-color: #fff;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        button {
            margin-top: 24px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        button:hover {
            background: #1d4ed8;
        }

        .actions {
            margin-top: 20px;
            text-align: center;
        }

        .actions a {
            text-decoration: none;
            color: #2563eb;
            font-weight: 600;
            font-size: 14px;
        }
        
        .actions a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <nav class="bg-blue-800 p-4 text-white mb-8 shadow-md">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <a href="dashboard.php" class="hover:text-blue-200 transition"><i class="fas fa-arrow-left mr-2"></i> Kembali</a>
            <span class="font-bold text-lg">Jual Barang Bekas</span>
            <div class="w-20"></div> </div>
    </nav>

    <div class="form-container">
        <?php if (!empty($success)): ?>
            <div class="success"><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error"><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label for="nama_barang">Nama Barang</label>
            <input type="text" id="nama_barang" name="nama_barang" placeholder="Contoh: Kipas Angin Cosmos" required>

            <label for="deskripsi">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" placeholder="Jelaskan kondisi barang..." required></textarea>

            <label for="harga">Harga (Rp)</label>
            <input type="number" id="harga" name="harga" min="1" placeholder="Contoh: 50000" required>

            <label for="jumlah">Jumlah Barang</label>
            <input type="number" id="jumlah" name="jumlah" min="1" placeholder="Contoh: 1" required>

            <label for="gambar">Upload Gambar Barang</label>
            <input type="file" id="gambar" name="gambar" accept=".jpg,.jpeg,.png,.webp" required style="background: white; padding: 10px;">

            <button type="submit"><i class="fas fa-upload mr-2"></i>Upload Barang</button>
        </form>

        <div class="actions">
            <a href="barang_saya.php">Lihat Daftar Barang Saya <i class="fas fa-arrow-right ml-1"></i></a>
        </div>
    </div>

</body>
</html>