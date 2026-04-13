<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$id_pembeli = $_SESSION['id_user'];
$id_barang  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_barang === 0) {
    header("Location: dashboard.php");
    exit();
}

// Ambil data barang
$stmt = mysqli_prepare($conn, "SELECT b.*, u.username as nama_penjual, u.id_user as id_penjual FROM tbl_barang b LEFT JOIN tbl_user u ON b.id_user = u.id_user WHERE b.id_barang = ?");
mysqli_stmt_bind_param($stmt, "i", $id_barang);
mysqli_stmt_execute($stmt);
$barang = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$barang || $barang['id_penjual'] == $id_pembeli) {
    header("Location: dashboard.php");
    exit();
}

$id_penjual  = (int)$barang['id_penjual'];
$sudah_bayar = false;
$pesan_error = '';

// ── Proses Bayar ─────────────────────────────────────────────────────────────
if (isset($_POST['bayar'])) {
    mysqli_begin_transaction($conn);
    try {
        // Update status barang jadi dibooking
        $upd = mysqli_prepare($conn, "UPDATE tbl_barang SET id_pembeli = ?, waktu_beli = NOW(), status_barang = 'dibooking' WHERE id_barang = ? AND status_barang = 'tersedia'");
        mysqli_stmt_bind_param($upd, "ii", $id_pembeli, $id_barang);
        mysqli_stmt_execute($upd);

        // Insert transaksi
        $harga = $barang['harga'];
        $ins   = mysqli_prepare($conn, "INSERT INTO tbl_transaksi (id_user, id_barang, tanggal, total_harga, status) VALUES (?, ?, NOW(), ?, 'Pending')");
        mysqli_stmt_bind_param($ins, "iii", $id_pembeli, $id_barang, $harga);
        mysqli_stmt_execute($ins);

        mysqli_commit($conn);
        $sudah_bayar = true;
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $pesan_error = "Gagal memproses pembayaran. Silakan coba lagi.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Rekos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");
        * { font-family: "Inter", sans-serif; }
        .glass { background: rgba(255,255,255,0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.15); }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-900 via-blue-800 to-blue-950 min-h-screen text-white">

    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 w-64 h-full glass shadow-xl">
        <div class="p-6 border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl"><i class="fas fa-store"></i></div>
                <div><h2 class="font-bold text-lg">Rekos</h2><p class="text-xs text-slate-300">Marketplace Anak Kos</p></div>
            </div>
        </div>
        <nav class="p-6 space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10"><i class="fas fa-home text-blue-400"></i> Dashboard</a>
            <a href="barang_saya.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10"><i class="fas fa-box text-blue-400"></i> Barang Saya</a>
            <a href="jual_barang.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10"><i class="fas fa-plus-circle text-blue-400"></i> Jual Barang</a>
            <a href="profil.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10"><i class="fas fa-user text-blue-400"></i> Profil</a>
        </nav>
        <div class="absolute bottom-6 left-6 right-6">
            <a href="logout.php" class="flex items-center gap-3 p-3 rounded-xl bg-red-500/20 border border-red-400/30 text-red-200 hover:bg-red-500/30">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main -->
    <main class="ml-64 p-8 bg-gray-100 min-h-screen text-slate-800">
        <a href="detail_barang.php?id=<?= $id_barang ?>" class="inline-flex items-center gap-2 text-blue-700 hover:text-blue-900 mb-6 font-medium">
            <i class="fas fa-arrow-left"></i> Kembali ke Detail Barang
        </a>

        <div class="max-w-2xl mx-auto">

            <?php if ($sudah_bayar): ?>
            <!-- ── Sukses Bayar ── -->
            <div class="bg-white rounded-2xl shadow-md p-10 text-center">
                <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check-circle text-green-500 text-5xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 mb-3">Pembayaran Berhasil!</h2>
                <p class="text-slate-600 mb-6">
                    Silakan mengambil barang sesuai kesepakatan dengan penjual.<br>
                    <strong>Terima kasih telah berbelanja di Rekos!</strong>
                </p>
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 text-left">
                    <p class="text-sm font-semibold text-blue-700 mb-2"><i class="fas fa-receipt mr-1"></i> Ringkasan Pesanan</p>
                    <p class="text-sm text-slate-600">Barang: <strong><?= htmlspecialchars($barang['nama_barang']) ?></strong></p>
                    <p class="text-sm text-slate-600">Total: <strong class="text-blue-600">Rp <?= number_format($barang['harga'], 0, ',', '.') ?></strong></p>
                    <p class="text-sm text-slate-600">Status: <span class="text-orange-600 font-semibold">Menunggu Konfirmasi Penjual</span></p>
                </div>
                <div class="flex gap-3 justify-center">
                    <a href="dashboard.php" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition">
                        <i class="fas fa-home"></i> Ke Dashboard
                    </a>
                    <a href="detail_barang.php?id=<?= $id_barang ?>" class="inline-flex items-center gap-2 bg-slate-200 hover:bg-slate-300 text-slate-700 px-6 py-3 rounded-xl font-semibold transition">
                        <i class="fas fa-comments"></i> Chat Penjual
                    </a>
                </div>
            </div>

            <?php else: ?>
            <!-- ── Form Pembayaran ── -->

            <?php if ($pesan_error): ?>
            <div class="mb-4 bg-red-100 border border-red-300 text-red-700 px-4 py-3 rounded-xl text-sm">
                <i class="fas fa-exclamation-circle mr-1"></i> <?= $pesan_error ?>
            </div>
            <?php endif; ?>

            <!-- Rincian Pesanan -->
            <div class="bg-white rounded-2xl shadow-md p-6 mb-4">
                <h2 class="text-xl font-bold text-slate-800 mb-4">
                    <i class="fas fa-receipt text-blue-500 mr-2"></i> Rincian Pesanan
                </h2>
                <div class="flex gap-4 items-start">
                    <img src="<?= !empty($barang['gambar']) ? '../assets/img/' . htmlspecialchars($barang['gambar']) : 'https://placehold.co/100x100?text=No+Image' ?>"
                         class="w-24 h-24 object-cover rounded-xl border border-slate-200" alt="">
                    <div class="flex-1">
                        <h3 class="font-bold text-slate-800 text-lg"><?= htmlspecialchars($barang['nama_barang']) ?></h3>
                        <p class="text-sm text-slate-500 mt-1 line-clamp-2"><?= htmlspecialchars($barang['deskripsi']) ?></p>
                        <p class="text-sm text-slate-500 mt-1">Penjual: <strong><?= htmlspecialchars($barang['nama_penjual']) ?></strong></p>
                    </div>
                </div>
                <div class="mt-4 border-t border-slate-100 pt-4 space-y-2">
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>Harga Barang</span>
                        <span>Rp <?= number_format($barang['harga'], 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>Biaya Layanan</span>
                        <span>Rp 0</span>
                    </div>
                    <div class="flex justify-between font-bold text-slate-800 text-base border-t border-slate-200 pt-2 mt-2">
                        <span>Total Pembayaran</span>
                        <span class="text-blue-600">Rp <?= number_format($barang['harga'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <!-- Metode Pembayaran -->
            <div class="bg-white rounded-2xl shadow-md p-6 mb-4">
                <h2 class="text-xl font-bold text-slate-800 mb-4">
                    <i class="fas fa-qrcode text-blue-500 mr-2"></i> Metode Pembayaran
                </h2>

                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-4 flex items-start gap-3">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                    <p class="text-sm text-blue-700">
                        Pembayaran <strong>hanya dapat dilakukan dengan metode QRIS</strong>. 
                        Selesaikan pembayaran dalam waktu <strong>10 menit</strong> sebelum pesanan dibatalkan otomatis.
                    </p>
                </div>

                <!-- QRIS Mock -->
                <div class="flex flex-col items-center py-4">
                    <div class="bg-white border-2 border-slate-200 rounded-2xl p-4 shadow-inner mb-3">
                        <!-- QR Code placeholder -->
                        <svg width="180" height="180" viewBox="0 0 180 180" xmlns="http://www.w3.org/2000/svg">
                            <rect width="180" height="180" fill="white"/>
                            <!-- Sudut kiri atas -->
                            <rect x="10" y="10" width="60" height="60" rx="4" fill="none" stroke="#1e3a5f" stroke-width="6"/>
                            <rect x="24" y="24" width="32" height="32" rx="2" fill="#1e3a5f"/>
                            <!-- Sudut kanan atas -->
                            <rect x="110" y="10" width="60" height="60" rx="4" fill="none" stroke="#1e3a5f" stroke-width="6"/>
                            <rect x="124" y="24" width="32" height="32" rx="2" fill="#1e3a5f"/>
                            <!-- Sudut kiri bawah -->
                            <rect x="10" y="110" width="60" height="60" rx="4" fill="none" stroke="#1e3a5f" stroke-width="6"/>
                            <rect x="24" y="124" width="32" height="32" rx="2" fill="#1e3a5f"/>
                            <!-- Pola tengah -->
                            <rect x="80" y="10" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="95" y="10" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="80" y="25" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="80" y="80" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="95" y="80" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="110" y="80" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="80" y="95" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="110" y="95" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="125" y="80" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="140" y="95" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="125" y="110" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="140" y="110" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="155" y="110" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="80" y="110" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="80" y="125" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="95" y="125" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="95" y="140" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="80" y="155" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="110" y="140" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="125" y="155" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="140" y="140" width="10" height="10" fill="#1e3a5f"/>
                            <rect x="155" y="155" width="10" height="10" fill="#1e3a5f"/>
                        </svg>
                    </div>
                    <p class="text-xs text-slate-500 mb-1">Scan QR Code di atas menggunakan aplikasi pembayaran</p>
                    <p class="text-sm font-bold text-slate-700">Total: Rp <?= number_format($barang['harga'], 0, ',', '.') ?></p>
                </div>

                <!-- Timer 10 Menit -->
                <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-center">
                    <p class="text-sm text-orange-700 font-semibold mb-1">
                        <i class="fas fa-stopwatch mr-1"></i> Selesaikan pembayaran dalam:
                    </p>
                    <div id="timer-bayar" class="text-3xl font-bold text-orange-600 font-mono">10:00</div>
                    <p class="text-xs text-orange-500 mt-1">Pesanan akan dibatalkan jika waktu habis</p>
                </div>
            </div>

            <!-- Tombol Bayar -->
            <form method="POST">
                <input type="hidden" name="bayar" value="1">
                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl font-bold text-lg transition shadow-lg shadow-blue-600/30 flex items-center justify-center gap-3">
                    <i class="fas fa-lock"></i> Bayar Sekarang
                </button>
            </form>

            <p class="text-center text-xs text-slate-400 mt-3">
                <i class="fas fa-shield-alt mr-1"></i> Transaksi aman & terlindungi oleh Rekos
            </p>

            <?php endif; ?>
        </div>
    </main>

    <script>
        <?php if (!$sudah_bayar): ?>
        // Timer 10 menit
        let totalDetik = 10 * 60;
        const timerEl = document.getElementById('timer-bayar');

        function updateTimer() {
            let menit = Math.floor(totalDetik / 60).toString().padStart(2, '0');
            let detik = (totalDetik % 60).toString().padStart(2, '0');
            timerEl.innerHTML = menit + ":" + detik;

            if (totalDetik <= 0) {
                timerEl.innerHTML = "WAKTU HABIS";
                timerEl.classList.replace("text-orange-600", "text-red-600");
                // Redirect ke dashboard jika waktu habis
                setTimeout(() => window.location.href = 'dashboard.php', 2000);
                return;
            }
            totalDetik--;
        }

        updateTimer();
        setInterval(updateTimer, 1000);
        <?php endif; ?>
    </script>
</body>
</html>