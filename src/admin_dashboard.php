<?php
session_start();
require 'koneksi.php';

// Proteksi: hanya admin
if (!isset($_SESSION['status']) || $_SESSION['status'] != 'login' || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// Statistik
$total_penjual  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_user WHERE role = 'Penjual'"))['total'];
$total_pembeli  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_user WHERE role = 'Pembeli'"))['total'];
$total_barang   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_barang"))['total'];
$total_transaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tbl_transaksi"))['total'];

// Barang terbaru
$barang_terbaru = mysqli_query($conn, "
    SELECT b.*, u.username 
    FROM tbl_barang b 
    JOIN tbl_user u ON b.id_user = u.id_user 
    ORDER BY b.id_barang DESC 
    LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rekos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 w-64 h-full bg-blue-900 shadow-xl text-white">
        <div class="p-6 border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-blue-600 rounded-xl">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <h2 class="font-bold text-lg">Rekos Admin</h2>
                    <p class="text-xs text-slate-300">Panel Administrator</p>
                </div>
            </div>
        </div>
        <nav class="p-6 space-y-2">
            <a href="admin_dashboard.php" class="flex items-center gap-3 p-3 rounded-xl bg-blue-700 border border-blue-500">
                <i class="fas fa-home text-blue-300"></i> Dashboard
            </a>
            <a href="admin_penjual.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                <i class="fas fa-store text-blue-300"></i> Daftar Penjual
            </a>
            <a href="admin_barang.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                <i class="fas fa-box text-blue-300"></i> Semua Barang
            </a>
        </nav>
        <div class="absolute bottom-6 left-6 right-6">
            <a href="logout.php" class="flex items-center gap-3 p-3 rounded-xl bg-red-500/20 border border-red-400/30 text-red-200 hover:bg-red-500/30">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main -->
    <main class="ml-64 p-8">
        <header class="bg-blue-800 rounded-2xl p-6 mb-8 shadow text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Dashboard Admin</h1>
                    <p class="text-slate-300">Selamat datang, <?= htmlspecialchars($username) ?>!</p>
                </div>
                <div class="flex items-center gap-3 bg-white/10 px-4 py-2 rounded-xl">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div>
                        <p class="font-semibold"><?= htmlspecialchars($username) ?></p>
                        <p class="text-xs text-slate-400">Administrator</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Statistik -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow p-6 flex items-center gap-4">
                <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-store text-blue-600 text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Total Penjual</p>
                    <p class="text-3xl font-bold text-slate-800"><?= $total_penjual ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 flex items-center gap-4">
                <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-green-600 text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Total Pembeli</p>
                    <p class="text-3xl font-bold text-slate-800"><?= $total_pembeli ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 flex items-center gap-4">
                <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-box text-purple-600 text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Total Barang</p>
                    <p class="text-3xl font-bold text-slate-800"><?= $total_barang ?></p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow p-6 flex items-center gap-4">
                <div class="w-14 h-14 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-receipt text-yellow-600 text-2xl"></i>
                </div>
                <div>
                    <p class="text-sm text-slate-500">Total Transaksi</p>
                    <p class="text-3xl font-bold text-slate-800"><?= $total_transaksi ?></p>
                </div>
            </div>
        </div>

        <!-- Barang Terbaru -->
        <div class="bg-white rounded-2xl shadow p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-slate-800">
                    <i class="fas fa-clock text-blue-500 mr-2"></i> Barang Terbaru Dijual
                </h2>
                <a href="admin_barang.php" class="text-sm text-blue-600 hover:underline">Lihat Semua →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                <?php if ($barang_terbaru && mysqli_num_rows($barang_terbaru) > 0): ?>
                    <?php while ($b = mysqli_fetch_assoc($barang_terbaru)): ?>
                        <div class="border border-slate-200 rounded-xl overflow-hidden hover:shadow transition">
                            <?php
                                $gambarPath = 'https://placehold.co/400x200?text=No+Image';
                                if (!empty($b['gambar'])) {
                                    if (file_exists('../assets/uploads/' . $b['gambar'])) {
                                        $gambarPath = '../assets/uploads/' . htmlspecialchars($b['gambar']);
                                    } else {
                                        $gambarPath = '../assets/img/' . htmlspecialchars($b['gambar']);
                                    }
                                }
                            ?>
                            <img src="<?= $gambarPath ?>" class="w-full h-36 object-cover" alt="">
                            <div class="p-3">
                                <p class="font-semibold text-slate-800 line-clamp-1"><?= htmlspecialchars($b['nama_barang']) ?></p>
                                <p class="text-blue-600 font-bold text-sm">Rp <?= number_format($b['harga'], 0, ',', '.') ?></p>
                                <p class="text-xs text-slate-400 mt-1">
                                    <i class="fas fa-user mr-1"></i> <?= htmlspecialchars($b['username']) ?>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-10 text-slate-400">
                        <i class="fas fa-box-open text-4xl mb-3"></i>
                        <p>Belum ada barang yang dijual.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>