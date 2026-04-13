<?php
session_start();
require 'koneksi.php';

// Proteksi Halaman
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

$id_user  = $_SESSION['id_user'];
$username = $_SESSION['username'];

// Ambil role ASLI dari database
$query_user = $conn->prepare("SELECT email, no_telp, alamat, role FROM tbl_user WHERE id_user = ?");
$query_user->bind_param("i", $id_user);
$query_user->execute();
$user_data  = $query_user->get_result()->fetch_assoc();
$role_asli  = $user_data['role']; // role permanen di DB

// Role aktif = dari session (bisa di-switch)
$role = $_SESSION['role'] ?? $role_asli;

// ════════════════════════════════════════════
// PROSES: Switch role (hanya jika role_asli = penjual)
// ════════════════════════════════════════════
if (isset($_POST['switch_role']) && $role_asli === 'penjual') {
    if ($role === 'penjual') {
        $_SESSION['role'] = 'pembeli';
    } else {
        $_SESSION['role'] = 'penjual';
    }
    header("Location: profil.php");
    exit;
}

$role = $_SESSION['role'] ?? $role_asli;

// ════════════════════════════════════════════
// Riwayat transaksi berdasarkan role AKTIF
// ════════════════════════════════════════════
if ($role == 'pembeli') {
    $query_histori = $conn->prepare("
        SELECT t.tanggal, b.nama_barang, b.harga, t.status 
        FROM tbl_transaksi t 
        JOIN tbl_barang b ON t.id_barang = b.id_barang 
        WHERE t.id_user = ? 
        ORDER BY t.tanggal DESC
    ");
} else {
    $query_histori = $conn->prepare("
        SELECT t.tanggal, b.nama_barang, b.harga, t.status 
        FROM tbl_transaksi t 
        JOIN tbl_barang b ON t.id_barang = b.id_barang 
        WHERE b.id_user = ? 
        ORDER BY t.tanggal DESC
    ");
}
$query_histori->bind_param("i", $id_user);
$query_histori->execute();
$riwayat = $query_histori->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Rekos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="bg-slate-50 min-h-screen pb-10">

    <nav class="bg-blue-800 p-4 text-white mb-6">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <a href="dashboard.php" class="hover:text-blue-200"><i class="fas fa-arrow-left mr-2"></i> Kembali</a>
            <span class="font-bold">Profil Pengguna</span>
            <div class="w-10"></div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 space-y-6">

        <!-- Kartu Profil -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="h-24 bg-gradient-to-r from-blue-600 to-blue-400"></div>
            <div class="px-6 pb-6 text-center">
                <div class="relative -mt-12 mb-4">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($username) ?>&size=128&background=3b82f6&color=fff"
                         alt="Avatar"
                         class="w-24 h-24 rounded-full mx-auto border-4 border-white shadow-md">
                </div>
                <h2 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($username) ?></h2>

                <!-- Badge role aktif -->
                <div class="flex items-center justify-center gap-2 mb-4">
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold
                        <?= $role === 'penjual' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' ?>">
                        <i class="fas <?= $role === 'penjual' ? 'fa-store' : 'fa-shopping-bag' ?> text-xs"></i>
                        <?= ucfirst($role) ?> Rekos
                    </span>
                    <?php if ($role_asli === 'penjual'): ?>
                    <span class="text-xs text-slate-400">(Akun terdaftar sebagai Penjual)</span>
                    <?php endif; ?>
                </div>

                <div class="flex items-center justify-center gap-3 flex-wrap">
                    <!-- Tombol Edit Profil -->
                    <a href="edit_profil.php" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2 rounded-full font-semibold transition">
                        <i class="fas fa-user-edit"></i> Edit Profil
                    </a>

                    <!-- Tombol Switch Role (hanya muncul jika role_asli = penjual) -->
                    <?php if ($role_asli === 'penjual'): ?>
                    <form method="POST">
                        <input type="hidden" name="switch_role" value="1">
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-full font-semibold transition
                            <?= $role === 'penjual'
                                ? 'bg-green-100 hover:bg-green-200 text-green-700'
                                : 'bg-blue-100 hover:bg-blue-200 text-blue-700' ?>">
                            <i class="fas <?= $role === 'penjual' ? 'fa-shopping-bag' : 'fa-store' ?>"></i>
                            <?= $role === 'penjual' ? 'Beralih ke Mode Pembeli' : 'Beralih ke Mode Penjual' ?>
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <!-- Info mode aktif -->
                <?php if ($role_asli === 'penjual'): ?>
                <p class="mt-3 text-xs text-slate-400">
                    <?= $role === 'penjual'
                        ? 'Mode Penjual aktif — kamu bisa upload dan kelola barang jualan.'
                        : 'Mode Pembeli aktif — kamu bisa mencari dan membeli barang.' ?>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Riwayat Transaksi -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-slate-800">
                    <i class="fas fa-history mr-2 text-blue-500"></i>
                    <?= ($role == 'pembeli') ? 'Riwayat Pembelian Saya' : 'Riwayat Penjualan Barang' ?>
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-slate-400 text-sm border-b">
                            <th class="pb-3 font-medium">Tanggal</th>
                            <th class="pb-3 font-medium">Nama Barang</th>
                            <th class="pb-3 font-medium">Harga</th>
                            <th class="pb-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php if ($riwayat->num_rows > 0): ?>
                            <?php while($row = $riwayat->fetch_assoc()): ?>
                                <tr>
                                    <td class="py-4 text-sm"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                    <td class="py-4 font-medium"><?= htmlspecialchars($row['nama_barang']) ?></td>
                                    <td class="py-4 text-sm text-blue-600 font-semibold">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                    <td class="py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium
                                            <?= strtolower($row['status']) == 'selesai'
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-yellow-100 text-yellow-700' ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="py-10 text-center text-slate-400 italic text-sm">
                                    Belum ada transaksi ditemukan.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</body>
</html>