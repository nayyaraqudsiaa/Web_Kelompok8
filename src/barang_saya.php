<?php
session_start();
require 'koneksi.php';

$session_timeout = 600;

// Cek login
if (!isset($_SESSION['username']) || !isset($_SESSION['status']) || !isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

// Cek timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?expired=1");
    exit();
}

$_SESSION['last_activity'] = time();

$id_user     = $_SESSION['id_user'];
$username    = $_SESSION['username'];
$role        = isset($_SESSION['role']) ? $_SESSION['role'] : 'pembeli';
$currentPage = basename($_SERVER['PHP_SELF']);

// Kalau belum penjual, arahkan ke jual_barang.php
if ($role !== 'penjual') {
    header("Location: jual_barang.php");
    exit();
}

// ── Proses Hapus Barang ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    $hapus_id = (int)$_POST['hapus_id'];

    // Pastikan barang milik user yang login
    $cek = $conn->prepare("SELECT id_barang, gambar FROM tbl_barang WHERE id_barang = ? AND id_user = ?");
    $cek->bind_param("ii", $hapus_id, $id_user);
    $cek->execute();
    $cek_result = $cek->get_result()->fetch_assoc();

    if ($cek_result) {
        // Hapus gambar dari folder jika ada
        if (!empty($cek_result['gambar'])) {
            $path_gambar = '../assets/img/' . $cek_result['gambar'];
            if (file_exists($path_gambar)) {
                unlink($path_gambar);
            }
        }

        // Hapus data terkait dulu
        $conn->query("DELETE FROM tbl_pesan WHERE id_barang = $hapus_id");
        $conn->query("DELETE FROM tbl_transaksi WHERE id_barang = $hapus_id");

        // Hapus barangnya
        $del = $conn->prepare("DELETE FROM tbl_barang WHERE id_barang = ? AND id_user = ?");
        $del->bind_param("ii", $hapus_id, $id_user);
        $del->execute();
    }

    header("Location: barang_saya.php");
    exit();
}

// ── Ambil barang milik user ──────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM tbl_barang WHERE id_user = ? ORDER BY id_barang DESC");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
?>
<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Barang Saya - Rekos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");
        * { font-family: "Inter", sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        .glass:hover { background: rgba(255, 255, 255, 0.12); transition: 0.2s; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-900 via-blue-800 to-blue-950 min-h-screen text-white">

    <aside class="fixed left-0 top-0 w-64 h-full glass shadow-xl">
        <div class="p-6 border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="p-3 bg-gradient-to-br from-blue-400 to-blue-600 rounded-xl">
                    <i class="fas fa-store"></i>
                </div>
                <div>
                    <h2 class="font-bold text-lg">Rekos</h2>
                    <p class="text-xs text-slate-300">Marketplace Anak Kos</p>
                </div>
            </div>
        </div>
        <nav class="p-6 space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-xl <?= $currentPage == 'dashboard.php' ? 'bg-blue-500/20 border border-blue-400/30' : 'hover:bg-white/10' ?>">
                <i class="fas fa-home text-blue-400"></i> Dashboard
            </a>
            <a href="barang_saya.php" class="flex items-center gap-3 p-3 rounded-xl <?= $currentPage == 'barang_saya.php' ? 'bg-blue-500/20 border border-blue-400/30' : 'hover:bg-white/10' ?>">
                <i class="fas fa-box text-blue-400"></i> Barang Saya
            </a>
            <a href="jual_barang.php" class="flex items-center gap-3 p-3 rounded-xl <?= $currentPage == 'jual_barang.php' ? 'bg-blue-500/20 border border-blue-400/30' : 'hover:bg-white/10' ?>">
                <i class="fas fa-plus-circle text-blue-400"></i> Jual Barang
            </a>
            <a href="profil.php" class="flex items-center gap-3 p-3 rounded-xl <?= $currentPage == 'profil.php' ? 'bg-blue-500/20 border border-blue-400/30' : 'hover:bg-white/10' ?>">
                <i class="fas fa-user text-blue-400"></i> Profil
            </a>
        </nav>
        <div class="absolute bottom-6 left-6 right-6">
            <a href="logout.php" class="flex items-center gap-3 p-3 rounded-xl bg-red-500/20 border border-red-400/30 text-red-200 hover:bg-red-500/30">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <main class="ml-64 p-8 bg-gray-100 min-h-screen text-slate-800">
        <header class="bg-blue-800 rounded-2xl p-6 mb-8 shadow text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Barang Saya</h1>
                    <p class="text-slate-300">Kelola barang yang sudah kamu upload</p>
                </div>
                <div class="flex items-center gap-3 bg-white/10 px-4 py-2 rounded-xl">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($username) ?>&background=3b82f6&color=fff"
                         class="w-10 h-10 rounded-lg" alt="Avatar">
                    <div>
                        <p class="font-semibold"><?= htmlspecialchars($username) ?></p>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars(ucfirst($role)) ?> Rekos</p>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Daftar Barang Saya</h2>
                    <p class="text-slate-500 text-sm">Semua barang yang kamu jual akan tampil di sini.</p>
                </div>
                <a href="jual_barang.php"
                   class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700 transition">
                    <i class="fas fa-plus"></i> Jual Barang
                </a>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php while ($row = $result->fetch_assoc()):
                        $is_booked    = (isset($row['status_barang']) && $row['status_barang'] == 'dibooking');
                        $waktu_target = 0;
                        if ($is_booked && !empty($row['waktu_beli'])) {
                            $waktu_target = strtotime($row['waktu_beli']) + (24 * 3600);
                        }
                    ?>
                        <div class="bg-white rounded-2xl shadow-md overflow-hidden flex flex-col hover:shadow-lg transition">

                            <a href="detail_barang.php?id=<?= $row['id_barang'] ?>" class="group flex flex-col relative flex-1 cursor-pointer">
                                <div class="relative overflow-hidden">
                                    <img
                                        src="<?= !empty($row['gambar']) ? '../assets/img/' . htmlspecialchars($row['gambar']) : 'https://placehold.co/400x250?text=No+Image' ?>"
                                        class="w-full h-56 object-cover group-hover:scale-105 transition duration-300"
                                        alt="<?= htmlspecialchars($row['nama_barang']) ?>">

                                    <?php if (isset($row['status_barang']) && $row['status_barang'] == 'terjual'): ?>
                                        <div class="absolute inset-0 bg-black/60 flex items-center justify-center">
                                            <span class="bg-red-500 text-white px-5 py-2 rounded-full font-bold shadow-lg tracking-wider">TERJUAL</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="p-5 flex-1 flex flex-col">
                                    <h3 class="text-lg font-semibold text-slate-800"><?= htmlspecialchars($row['nama_barang']) ?></h3>
                                    <p class="text-blue-600 text-2xl font-bold mt-1">Rp <?= number_format($row['harga'], 0, ',', '.') ?></p>
                                    <p class="text-sm text-slate-500 mt-2 line-clamp-2"><?= htmlspecialchars($row['deskripsi']) ?></p>
                                    <p class="text-sm text-slate-500 mt-auto pt-4">
                                        Stok: <span class="font-semibold text-slate-700"><?= (int)$row['jumlah'] ?></span>
                                    </p>
                                </div>
                            </a>

                            <div class="p-5 border-t border-slate-100 bg-slate-50">

                                <?php if ($is_booked): ?>
                                    <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-4 shadow-inner">
                                        <div class="flex items-center justify-between mb-3">
                                            <span class="text-xs font-bold text-orange-700 flex items-center gap-1">
                                                <i class="fas fa-clock animate-pulse"></i> DIBOOKING
                                            </span>
                                            <div id="timer-<?= $row['id_barang'] ?>" class="text-red-600 font-mono font-bold text-sm bg-white px-2 py-1 border border-orange-200 rounded">
                                                00:00:00
                                            </div>
                                        </div>
                                        <a href="proses_konfirmasi.php?id=<?= $row['id_barang'] ?>"
                                           onclick="return confirm('Apakah Anda yakin transaksi ini berhasil? Barang akan ditandai Terjual.')"
                                           class="block w-full bg-green-500 text-white text-center py-2 rounded-lg text-sm font-medium hover:bg-green-600 transition shadow-sm">
                                            Konfirmasi
                                        </a>
                                    </div>

                                    <script>
                                        setInterval(function() {
                                            let deadline = <?= $waktu_target ?> * 1000;
                                            let sekarang = new Date().getTime();
                                            let selisih  = deadline - sekarang;
                                            let el = document.getElementById("timer-<?= $row['id_barang'] ?>");
                                            if (selisih > 0) {
                                                let jam   = Math.floor((selisih % (1000*60*60*24)) / (1000*60*60)).toString().padStart(2,'0');
                                                let menit = Math.floor((selisih % (1000*60*60)) / (1000*60)).toString().padStart(2,'0');
                                                let detik = Math.floor((selisih % (1000*60)) / 1000).toString().padStart(2,'0');
                                                if (el) el.innerHTML = jam + ":" + menit + ":" + detik;
                                            } else {
                                                if (el) el.innerHTML = "WAKTU HABIS";
                                            }
                                        }, 1000);
                                    </script>
                                <?php endif; ?>

                                <!-- ✅ Tombol Edit & Hapus -->
                                <div class="flex gap-3">
                                    <a href="#"
                                       class="flex-1 rounded-xl bg-slate-200 py-2 text-center font-medium text-slate-700 hover:bg-slate-300 transition">
                                        Edit
                                    </a>
                                    <form method="POST" class="flex-1"
                                          onsubmit="return confirm('Yakin ingin menghapus barang ini? Data tidak bisa dikembalikan.')">
                                        <input type="hidden" name="hapus_id" value="<?= $row['id_barang'] ?>">
                                        <button type="submit"
                                                class="w-full rounded-xl bg-red-100 py-2 text-center font-medium text-red-600 hover:bg-red-200 transition">
                                            Hapus
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-md p-12 text-center">
                    <div class="text-slate-300 text-6xl mb-4"><i class="fas fa-box-open"></i></div>
                    <h3 class="text-2xl font-semibold text-slate-700 mb-2">Belum ada barang</h3>
                    <p class="text-slate-500 mb-6">Kamu belum mengupload barang apa pun.</p>
                    <a href="jual_barang.php"
                       class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700 transition">
                        <i class="fas fa-plus"></i> Mulai Jual Barang
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>