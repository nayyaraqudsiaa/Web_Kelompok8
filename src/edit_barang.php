<?php
session_start();
require 'koneksi.php';

// 1. Cek Login
if (!isset($_SESSION['username']) || !isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

$id_user = $_SESSION['id_user'];
$id_barang = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Ambil data barang (Pastikan hanya bisa edit milik sendiri)
$stmt = $conn->prepare("SELECT * FROM tbl_barang WHERE id_barang = ? AND id_user = ?");
$stmt->bind_param("ii", $id_barang, $id_user);
$stmt->execute();
$barang = $stmt->get_result()->fetch_assoc();

if (!$barang) {
    die("Barang tidak ditemukan atau Anda tidak memiliki akses.");
}

// 3. Proses Update
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama_barang']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga     = (int)$_POST['harga'];
    $jumlah    = (int)$_POST['jumlah'];
    $gambar_db = $barang['gambar']; // Nama file lama di DB

    // Folder harus sama dengan jual_barang.php
    $folder = "../assets/img/"; 

    // Cek jika ada upload gambar baru
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === 0) {
        $namaAsli = $_FILES['gambar']['name'];
        $ext = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            // Beri nama unik agar tidak bentrok
            $namaFileBaru = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $namaAsli);
            
            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $folder . $namaFileBaru)) {
                // Hapus gambar lama jika ada untuk menghemat storage
                if (!empty($barang['gambar']) && file_exists($folder . $barang['gambar'])) {
                    unlink($folder . $barang['gambar']);
                }
                $gambar_db = $namaFileBaru;
            }
        } else {
            $error = "Format gambar tidak didukung.";
        }
    }

    if (empty($error)) {
        $upd = $conn->prepare("UPDATE tbl_barang SET nama_barang=?, deskripsi=?, harga=?, jumlah=?, gambar=? WHERE id_barang=? AND id_user=?");
        $upd->bind_param("ssiisii", $nama, $deskripsi, $harga, $jumlah, $gambar_db, $id_barang, $id_user);
        
        if ($upd->execute()) {
            header("Location: barang_saya.php?msg=updated");
            exit();
        } else {
            $error = "Gagal mengupdate database.";
        }
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Barang - Rekos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100 min-h-screen p-4 md:p-8">

    <div class="max-w-2xl mx-auto">
        <header class="mb-6 flex items-center justify-between">
            <a href="barang_saya.php" class="text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
            <h1 class="text-xl font-bold text-slate-800">Edit Detail Barang</h1>
        </header>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <form method="POST" enctype="multipart/form-data" class="p-6 md:p-8 space-y-5">
                
                <?php if($error): ?>
                    <div class="p-4 bg-red-50 text-red-600 rounded-xl border border-red-100 text-sm">
                        <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
                    </div>
                <?php endif; ?>

                <div class="flex flex-col items-center p-4 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
                    <label class="text-sm font-semibold text-slate-600 mb-3">Foto Barang Saat Ini</label>
                    <?php 
                        $path_tampil = "../assets/img/" . $barang['gambar'];
                        if (!empty($barang['gambar']) && file_exists($path_tampil)): 
                    ?>
                        <img src="<?= $path_tampil ?>" class="w-40 h-40 object-cover rounded-xl shadow-md border-4 border-white">
                    <?php else: ?>
                        <div class="w-40 h-40 bg-slate-200 rounded-xl flex items-center justify-center text-slate-400">
                            <i class="fas fa-image text-4xl"></i>
                        </div>
                        <p class="text-xs text-red-400 mt-2">File gambar tidak ditemukan di folder</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Ganti Foto (Opsional)</label>
                    <input type="file" name="gambar" accept="image/*" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Nama Barang</label>
                    <input type="text" name="nama_barang" required value="<?= htmlspecialchars($barang['nama_barang']) ?>" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-400 outline-none">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Harga (Rp)</label>
                        <input type="number" name="harga" required value="<?= $barang['harga'] ?>" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-400 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Stok</label>
                        <input type="number" name="jumlah" required value="<?= $barang['jumlah'] ?>" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-400 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Deskripsi</label>
                    <textarea name="deskripsi" rows="4" required class="w-full border border-slate-300 rounded-xl px-4 py-2.5 focus:ring-2 focus:ring-blue-400 outline-none"><?= htmlspecialchars($barang['deskripsi']) ?></textarea>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition shadow-lg shadow-blue-200">
                    <i class="fas fa-save mr-2"></i>Simpan Perubahan
                </button>
            </form>
        </div>
    </div>

</body>
</html>