<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != 'login' || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];

// Filter by penjual (dari tombol "Lihat Barang" di admin_penjual.php)
$filter_user = isset($_GET['id_user']) ? (int)$_GET['id_user'] : 0;
$keyword     = isset($_GET['q']) ? trim($_GET['q']) : '';

// Query barang
$where = "WHERE 1=1";
$params = [];
$types  = '';

if ($filter_user > 0) {
    $where .= " AND b.id_user = $filter_user";
}
if ($keyword !== '') {
    $where .= " AND (b.nama_barang LIKE ? OR b.deskripsi LIKE ?)";
    $search = "%$keyword%";
    $params = [$search, $search];
    $types  = 'ss';
}

$sql = "SELECT b.*, u.username FROM tbl_barang b JOIN tbl_user u ON b.id_user = u.id_user $where ORDER BY b.id_barang DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $barang = $stmt->get_result();
} else {
    $barang = mysqli_query($conn, $sql);
}

// Nama penjual yang difilter
$nama_filter = '';
if ($filter_user > 0) {
    $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM tbl_user WHERE id_user = $filter_user"));
    $nama_filter = $res['username'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Semua Barang - Rekos Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="bg-gray-100 min-h-screen">

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
            <a href="admin_dashboard.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                <i class="fas fa-home text-blue-300"></i> Dashboard
            </a>
            <a href="admin_penjual.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                <i class="fas fa-store text-blue-300"></i> Daftar Penjual
            </a>
            <a href="admin_barang.php" class="flex items-center gap-3 p-3 rounded-xl bg-blue-700 border border-blue-500">
                <i class="fas fa-box text-blue-300"></i> Semua Barang
            </a>
        </nav>
        <div class="absolute bottom-6 left-6 right-6">
            <a href="logout.php" class="flex items-center gap-3 p-3 rounded-xl bg-red-500/20 border border-red-400/30 text-red-200 hover:bg-red-500/30">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <main class="ml-64 p-8">
        <header class="bg-blue-800 rounded-2xl p-6 mb-8 shadow text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Semua Barang</h1>
                    <p class="text-slate-300">
                        <?= $nama_filter ? "Barang milik penjual: <strong>$nama_filter</strong>" : "Seluruh barang yang dijual di Rekos" ?>
                    </p>
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

        <!-- Filter & Search -->
        <div class="flex flex-col md:flex-row gap-3 mb-6 items-center justify-between">
            <?php if ($nama_filter): ?>
            <div class="flex items-center gap-2 bg-blue-100 text-blue-700 px-4 py-2 rounded-xl text-sm font-semibold">
                <i class="fas fa-filter"></i> Filter: <?= htmlspecialchars($nama_filter) ?>
                <a href="admin_barang.php" class="ml-2 text-red-500 hover:text-red-700">
                    <i class="fas fa-times"></i>
                </a>
            </div>
            <?php else: ?>
            <div></div>
            <?php endif; ?>

            <form action="admin_barang.php" method="GET" class="flex gap-2">
                <?php if ($filter_user): ?>
                <input type="hidden" name="id_user" value="<?= $filter_user ?>">
                <?php endif; ?>
                <input type="text" name="q" placeholder="Cari barang..."
                    value="<?= htmlspecialchars($keyword) ?>"
                    class="rounded-xl border border-slate-300 px-4 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm">
                <button type="submit" class="bg-blue-600 text-white px-5 rounded-xl hover:bg-blue-700 text-sm">
                    Cari
                </button>
            </form>
        </div>

        <!-- Tabel Barang -->
        <div class="bg-white rounded-2xl shadow p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-slate-500 text-sm border-b">
                            <th class="pb-3 font-medium">No</th>
                            <th class="pb-3 font-medium">Gambar</th>
                            <th class="pb-3 font-medium">Nama Barang</th>
                            <th class="pb-3 font-medium">Penjual</th>
                            <th class="pb-3 font-medium">Harga</th>
                            <th class="pb-3 font-medium">Stok</th>
                            <th class="pb-3 font-medium">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php if ($barang && mysqli_num_rows($barang) > 0):
                            $no = 1;
                            while ($b = mysqli_fetch_assoc($barang)): ?>
                        <tr>
                            <td class="py-4 text-sm text-slate-400"><?= $no++ ?></td>
                            <td class="py-4">
                                <?php
                                    $gambarPath = 'https://placehold.co/80x60?text=No+Img';
                                    if (!empty($b['gambar'])) {
                                        if (file_exists('../assets/uploads/' . $b['gambar'])) {
                                            $gambarPath = '../assets/uploads/' . htmlspecialchars($b['gambar']);
                                        } else {
                                            $gambarPath = '../assets/img/' . htmlspecialchars($b['gambar']);
                                        }
                                    }
                                ?>
                                <img src="<?= $gambarPath ?>" class="w-16 h-12 object-cover rounded-lg" alt="">
                            </td>
                            <td class="py-4 font-semibold"><?= htmlspecialchars($b['nama_barang']) ?></td>
                            <td class="py-4">
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-semibold">
                                    <?= htmlspecialchars($b['username']) ?>
                                </span>
                            </td>
                            <td class="py-4 text-blue-600 font-bold text-sm">
                                Rp <?= number_format($b['harga'], 0, ',', '.') ?>
                            </td>
                            <td class="py-4 text-sm"><?= (int)$b['jumlah'] ?> pcs</td>
                            <td class="py-4 text-sm text-slate-500 max-w-xs truncate">
                                <?= htmlspecialchars($b['deskripsi']) ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fas fa-box-open text-4xl mb-3 block"></i>
                                Belum ada barang yang dijual.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>