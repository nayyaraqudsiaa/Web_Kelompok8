<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['status']) || $_SESSION['status'] != 'login' || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit;
}

$username = $_SESSION['username'];
$pesan_sukses = '';
$pesan_error  = '';

// Proses hapus penjual
if (isset($_POST['hapus_penjual'])) {
    $id_hapus = (int)$_POST['id_user'];

    // Hapus barang penjual dulu, lalu usernya
    $conn->query("DELETE FROM tbl_transaksi WHERE id_barang IN (SELECT id_barang FROM tbl_barang WHERE id_user = $id_hapus)");
    $conn->query("DELETE FROM tbl_pesan WHERE id_barang IN (SELECT id_barang FROM tbl_barang WHERE id_user = $id_hapus)");
    $conn->query("DELETE FROM tbl_barang WHERE id_user = $id_hapus");
    $del = $conn->query("DELETE FROM tbl_user WHERE id_user = $id_hapus AND role = 'Penjual'");

    if ($del) {
        $pesan_sukses = "Penjual berhasil dihapus beserta seluruh barangnya.";
    } else {
        $pesan_error = "Gagal menghapus penjual.";
    }
}

// Ambil daftar penjual
$penjual = mysqli_query($conn, "
    SELECT u.*, COUNT(b.id_barang) as total_barang 
    FROM tbl_user u 
    LEFT JOIN tbl_barang b ON u.id_user = b.id_user 
    WHERE u.role = 'Penjual' 
    GROUP BY u.id_user 
    ORDER BY u.id_user DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Penjual - Rekos Admin</title>
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
            <a href="admin_penjual.php" class="flex items-center gap-3 p-3 rounded-xl bg-blue-700 border border-blue-500">
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

    <main class="ml-64 p-8">
        <header class="bg-blue-800 rounded-2xl p-6 mb-8 shadow text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Daftar Penjual</h1>
                    <p class="text-slate-300">Kelola semua penjual yang terdaftar di Rekos</p>
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

        <?php if ($pesan_sukses): ?>
        <div class="mb-6 bg-green-100 border border-green-300 text-green-700 px-5 py-3 rounded-xl flex items-center gap-2">
            <i class="fas fa-check-circle"></i> <?= $pesan_sukses ?>
        </div>
        <?php endif; ?>

        <?php if ($pesan_error): ?>
        <div class="mb-6 bg-red-100 border border-red-300 text-red-700 px-5 py-3 rounded-xl flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i> <?= $pesan_error ?>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow p-6">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-slate-500 text-sm border-b">
                            <th class="pb-3 font-medium">No</th>
                            <th class="pb-3 font-medium">Username</th>
                            <th class="pb-3 font-medium">Email</th>
                            <th class="pb-3 font-medium">No. Telepon</th>
                            <th class="pb-3 font-medium">Alamat</th>
                            <th class="pb-3 font-medium">Total Barang</th>
                            <th class="pb-3 font-medium">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php if ($penjual && mysqli_num_rows($penjual) > 0):
                            $no = 1;
                            while ($p = mysqli_fetch_assoc($penjual)): ?>
                        <tr>
                            <td class="py-4 text-sm text-slate-400"><?= $no++ ?></td>
                            <td class="py-4">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($p['username']) ?>&background=3b82f6&color=fff&size=40"
                                         class="w-9 h-9 rounded-full" alt="">
                                    <span class="font-semibold"><?= htmlspecialchars($p['username']) ?></span>
                                </div>
                            </td>
                            <td class="py-4 text-sm"><?= htmlspecialchars($p['email']) ?></td>
                            <td class="py-4 text-sm"><?= htmlspecialchars($p['no_telp'] ?: '-') ?></td>
                            <td class="py-4 text-sm max-w-xs truncate"><?= htmlspecialchars($p['alamat'] ?: '-') ?></td>
                            <td class="py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                    <?= $p['total_barang'] ?> barang
                                </span>
                            </td>
                            <td class="py-4">
                                <div class="flex items-center gap-2">
                                    <a href="admin_barang.php?id_user=<?= $p['id_user'] ?>"
                                       class="px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg text-xs font-semibold transition">
                                        <i class="fas fa-eye mr-1"></i> Lihat Barang
                                    </a>
                                    <form method="POST" onsubmit="return confirm('Yakin hapus penjual <?= htmlspecialchars($p['username']) ?> beserta semua barangnya?')">
                                        <input type="hidden" name="id_user" value="<?= $p['id_user'] ?>">
                                        <button type="submit" name="hapus_penjual"
                                            class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-xs font-semibold transition">
                                            <i class="fas fa-trash mr-1"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i class="fas fa-store-slash text-4xl mb-3 block"></i>
                                Belum ada penjual yang terdaftar.
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