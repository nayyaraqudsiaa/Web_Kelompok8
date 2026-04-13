<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username    = $_SESSION['username'];
$role        = isset($_SESSION['role']) ? $_SESSION['role'] : 'Member';

// Ambil id_user yang login
$res_user = mysqli_query($conn, "SELECT id_user FROM tbl_user WHERE username = '" . mysqli_real_escape_string($conn, $username) . "'");
$current_user = mysqli_fetch_assoc($res_user);
$id_pembeli   = $current_user ? (int)$current_user['id_user'] : 0;

// Ambil id_barang dari URL
$id_barang = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ── Data dummy (sama seperti dashboard) ──────────────────────────────────────
$dummy_barang = [
    1 => [
        'id_barang'   => 1,
        'nama_barang' => 'Kipas Angin',
        'deskripsi'   => 'Kipas angin bekas layak pakai, merk Cosmos. Masih berputar kencang di semua speed.',
        'harga'       => 50000,
        'jumlah'      => 2,
        'gambar'      => 'kipas-angin.jpeg',
        'id_user'     => 1,
        'kondisi'     => 'Bekas',
        'lama_pakai'  => '1 tahun',
        'lokasi'      => 'Kos Putri Mawar',
    ],
    2 => [
        'id_barang'   => 2,
        'nama_barang' => 'Meja Belajar Lipat',
        'deskripsi'   => 'Meja belajar lipat portable, kondisi bagus. Ringan dan mudah disimpan.',
        'harga'       => 35000,
        'jumlah'      => 1,
        'gambar'      => 'meja-belajar-lipat.jpg',
        'id_user'     => 1,
        'kondisi'     => 'Bekas',
        'lama_pakai'  => '6 bulan',
        'lokasi'      => 'Dekat kampus',
    ],
    3 => [
        'id_barang'   => 3,
        'nama_barang' => 'Rice Cooker',
        'deskripsi'   => 'Rice cooker mini 0.5L, masih berfungsi normal. Cocok untuk 1–2 porsi.',
        'harga'       => 65000,
        'jumlah'      => 1,
        'gambar'      => 'rice-cooker.jpeg',
        'id_user'     => 1,
        'kondisi'     => 'Bekas',
        'lama_pakai'  => '2 tahun',
        'lokasi'      => 'Area kampus',
    ],
    4 => ['id_barang'=>4,'nama_barang'=>'Lampu Belajar','deskripsi'=>'Lampu meja LED, hemat listrik. Cahaya tidak menyilaukan.','harga'=>25000,'jumlah'=>3,'gambar'=>'','id_user'=>1,'kondisi'=>'Bekas','lama_pakai'=>'8 bulan','lokasi'=>'Kos Melati'],
    5 => ['id_barang'=>5,'nama_barang'=>'Dispenser Mini','deskripsi'=>'Dispenser kecil cocok untuk kamar kos. Galon bisa muat.','harga'=>45000,'jumlah'=>1,'gambar'=>'','id_user'=>1,'kondisi'=>'Bekas','lama_pakai'=>'1.5 tahun','lokasi'=>'Kos Anggrek'],
    6 => ['id_barang'=>6,'nama_barang'=>'Rak Buku','deskripsi'=>'Rak buku 3 susun, bahan kayu ringan. Cat masih bagus.','harga'=>40000,'jumlah'=>2,'gambar'=>'','id_user'=>1,'kondisi'=>'Bekas','lama_pakai'=>'1 tahun','lokasi'=>'Kos Dahlia'],
    7 => ['id_barang'=>7,'nama_barang'=>'Setrika','deskripsi'=>'Setrika listrik bekas, panas merata. Kabel masih aman.','harga'=>30000,'jumlah'=>1,'gambar'=>'','id_user'=>1,'kondisi'=>'Bekas','lama_pakai'=>'2 tahun','lokasi'=>'Kos Mawar'],
    8 => ['id_barang'=>8,'nama_barang'=>'Cermin Dinding','deskripsi'=>'Cermin oval bingkai putih, ukuran sedang. Tidak ada goresan.','harga'=>20000,'jumlah'=>2,'gambar'=>'','id_user'=>1,'kondisi'=>'Bekas','lama_pakai'=>'3 bulan','lokasi'=>'Kos Kenanga'],
];

// ── Ambil data barang: coba DB dulu, fallback dummy ──────────────────────────
$barang   = null;
$penjual  = null;
$from_dummy = false;

if ($id_barang > 0) {
    $stmt = mysqli_prepare($conn, "SELECT b.*, u.username as nama_penjual, u.email as email_penjual, u.id_user as id_penjual FROM tbl_barang b LEFT JOIN tbl_user u ON b.id_user = u.id_user WHERE b.id_barang = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $barang = mysqli_fetch_assoc($res);
    }
}

// Fallback ke dummy jika tidak ada di DB
if (!$barang && isset($dummy_barang[$id_barang])) {
    $barang = $dummy_barang[$id_barang];
    $from_dummy = true;
    // Ambil info penjual dari DB pakai id_user dummy (1)
    $res_penjual = mysqli_query($conn, "SELECT * FROM tbl_user WHERE id_user = " . (int)$barang['id_user']);
    $penjual = $res_penjual ? mysqli_fetch_assoc($res_penjual) : null;
    $barang['nama_penjual']  = $penjual ? $penjual['username'] : 'Penjual Rekos';
    $barang['email_penjual'] = $penjual ? $penjual['email']    : '-';
    $barang['id_penjual']    = $penjual ? $penjual['id_user']  : 1;
}

if (!$barang) {
    header("Location: dashboard.php");
    exit();
}

$id_penjual = (int)$barang['id_penjual'];

// ── Kirim pesan ──────────────────────────────────────────────────────────────
$pesan_sukses = '';
$pesan_error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['isi_pesan'])) {
    $isi_pesan = trim($_POST['isi_pesan']);
    if ($isi_pesan === '') {
        $pesan_error = 'Pesan tidak boleh kosong.';
    } elseif ($id_pembeli === $id_penjual) {
        $pesan_error = 'Anda tidak bisa mengirim pesan ke diri sendiri.';
    } else {
        $stmt2 = mysqli_prepare($conn,
            "INSERT INTO tbl_pesan (id_pembeli, id_penjual, id_barang, isi_pesan, waktu_kirim, status_baca)
             VALUES (?, ?, ?, ?, NOW(), 0)"
        );
        mysqli_stmt_bind_param($stmt2, "iiis", $id_pembeli, $id_penjual, $id_barang, $isi_pesan);
        if (mysqli_stmt_execute($stmt2)) {
            $pesan_sukses = 'Pesan berhasil dikirim ke penjual!';
        } else {
            $pesan_error = 'Gagal mengirim pesan. Coba lagi.';
        }
    }
}

// ── Ambil riwayat chat ───────────────────────────────────────────────────────
$chats = [];
if ($id_pembeli && $id_penjual) {
    $stmt3 = mysqli_prepare($conn,
        "SELECT p.*, 
                ub.username as nama_pembeli,
                uj.username as nama_penjual
         FROM tbl_pesan p
         LEFT JOIN tbl_user ub ON p.id_pembeli = ub.id_user
         LEFT JOIN tbl_user uj ON p.id_penjual = uj.id_user
         WHERE p.id_barang = ? AND p.id_pembeli = ? AND p.id_penjual = ?
         ORDER BY p.waktu_kirim ASC"
    );
    mysqli_stmt_bind_param($stmt3, "iii", $id_barang, $id_pembeli, $id_penjual);
    mysqli_stmt_execute($stmt3);
    $res3 = mysqli_stmt_get_result($stmt3);
    while ($c = mysqli_fetch_assoc($res3)) {
        $chats[] = $c;
    }
}
?>
<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($barang['nama_barang']) ?> - Rekos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");
        * { font-family: "Inter", sans-serif; }
        .glass {
            background: rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .chat-box { height: 300px; overflow-y: auto; scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-900 via-blue-800 to-blue-950 min-h-screen text-white">

    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 w-64 h-full glass shadow-xl z-10">
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
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                <i class="fas fa-home text-blue-400"></i> Dashboard
            </a>
            <a href="barang_saya.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                <i class="fas fa-box text-blue-400"></i> Barang Saya
            </a>
            <a href="jual_barang.php" class="flex items-center gap-3 p-3 rounded-xl bg-blue-500/20 border border-blue-400/30">
                <i class="fas fa-plus-circle"></i> Jual Barang
            </a>
            <a href="profil.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                <i class="fas fa-user text-blue-400"></i> Profil
            </a>
        </nav>
        <div class="absolute bottom-6 left-6 right-6">
            <a href="logout.php" class="flex items-center gap-3 p-3 rounded-xl bg-red-500/20 border border-red-400/30 text-red-200 hover:bg-red-500/30">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>

    <!-- Main -->
    <main class="ml-64 p-8 bg-gray-100 min-h-screen text-slate-800">

        <!-- Back button -->
        <a href="dashboard.php" class="inline-flex items-center gap-2 text-blue-700 hover:text-blue-900 mb-6 font-medium">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>

        <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8">

            <!-- Kiri: Gambar + Info Barang -->
            <div>
                <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                    <img
                        src="<?= !empty($barang['gambar']) ? '../assets/img/' . htmlspecialchars($barang['gambar']) : 'https://placehold.co/600x400?text=No+Image' ?>"
                        class="w-full h-64 object-cover"
                        alt="<?= htmlspecialchars($barang['nama_barang']) ?>"
                    >
                    <div class="p-6">
                        <h1 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($barang['nama_barang']) ?></h1>
                        <p class="text-3xl font-bold text-blue-600 mt-2">
                            Rp <?= number_format($barang['harga'], 0, ',', '.') ?>
                        </p>

                        <!-- Detail kondisi -->
                        <div class="mt-4 space-y-2 text-sm text-slate-600">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-tag text-blue-400 w-5"></i>
                                <span><strong>Kondisi:</strong> <?= htmlspecialchars($barang['kondisi'] ?? 'Bekas layak pakai') ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-clock text-blue-400 w-5"></i>
                                <span><strong>Lama pemakaian:</strong> <?= htmlspecialchars($barang['lama_pakai'] ?? '-') ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-cubes text-blue-400 w-5"></i>
                                <span><strong>Stok tersedia:</strong> <?= (int)$barang['jumlah'] ?> unit</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-map-marker-alt text-blue-400 w-5"></i>
                                <span><strong>Lokasi:</strong> <?= htmlspecialchars($barang['lokasi'] ?? 'Area kampus') ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-user text-blue-400 w-5"></i>
                                <span><strong>Penjual:</strong> <?= htmlspecialchars($barang['nama_penjual'] ?? '-') ?></span>
                            </div>
                        </div>

                        <div class="mt-4 p-4 bg-slate-50 rounded-xl">
                            <p class="text-sm font-semibold text-slate-700 mb-1">Deskripsi</p>
                            <p class="text-sm text-slate-600"><?= nl2br(htmlspecialchars($barang['deskripsi'])) ?></p>
                        </div>

                        <!-- Tombol Beli -->
                        <?php if ($id_pembeli !== $id_penjual): ?>
                        <a href="beli_barang.php?id=<?= $id_barang ?>"
                           class="mt-6 flex items-center justify-center gap-2 w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition">
                            <i class="fas fa-shopping-cart"></i> Beli Barang
                        </a>
                        <?php else: ?>
                        <div class="mt-6 flex items-center justify-center gap-2 w-full bg-slate-300 text-slate-500 py-3 rounded-xl font-semibold cursor-not-allowed">
                            <i class="fas fa-store"></i> Ini barang Anda sendiri
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Kanan: Chat Penjual -->
            <div class="flex flex-col gap-4">
                <div class="bg-white rounded-2xl shadow-md overflow-hidden flex flex-col">
                    <!-- Header chat -->
                    <div class="bg-blue-700 text-white px-5 py-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-400 flex items-center justify-center font-bold text-lg">
                            <?= strtoupper(substr($barang['nama_penjual'] ?? 'P', 0, 1)) ?>
                        </div>
                        <div>
                            <p class="font-semibold"><?= htmlspecialchars($barang['nama_penjual'] ?? 'Penjual') ?></p>
                            <p class="text-xs text-blue-200">Penjual Rekos</p>
                        </div>
                        <i class="fas fa-comments ml-auto text-blue-200 text-xl"></i>
                    </div>

                    <!-- Notifikasi -->
                    <?php if ($pesan_sukses): ?>
                    <div class="mx-4 mt-3 p-3 bg-green-100 text-green-700 rounded-xl text-sm flex items-center gap-2">
                        <i class="fas fa-check-circle"></i> <?= $pesan_sukses ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($pesan_error): ?>
                    <div class="mx-4 mt-3 p-3 bg-red-100 text-red-700 rounded-xl text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i> <?= $pesan_error ?>
                    </div>
                    <?php endif; ?>

                    <!-- Riwayat Chat -->
                    <div class="chat-box p-4 flex flex-col gap-3 bg-slate-50" id="chatBox">
                        <?php if (empty($chats)): ?>
                            <div class="text-center text-slate-400 text-sm mt-8">
                                <i class="fas fa-comment-dots text-3xl mb-2 block"></i>
                                Belum ada pesan. Mulai chat dengan penjual!
                            </div>
                        <?php else: ?>
                            <?php foreach ($chats as $c): ?>
                                <?php $is_me = ($c['id_pembeli'] == $id_pembeli); ?>
                                <div class="flex <?= $is_me ? 'justify-end' : 'justify-start' ?>">
                                    <div class="max-w-[75%] px-4 py-2 rounded-2xl text-sm shadow
                                        <?= $is_me
                                            ? 'bg-blue-600 text-white rounded-br-none'
                                            : 'bg-white text-slate-700 border border-slate-200 rounded-bl-none' ?>">
                                        <p><?= nl2br(htmlspecialchars($c['isi_pesan'])) ?></p>
                                        <p class="text-xs mt-1 <?= $is_me ? 'text-blue-200' : 'text-slate-400' ?>">
                                            <?= date('d M, H:i', strtotime($c['waktu_kirim'])) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Form kirim pesan -->
                    <?php if ($id_pembeli !== $id_penjual): ?>
                    <form method="POST" class="p-4 border-t border-slate-200 flex gap-2">
                        <input
                            type="text"
                            name="isi_pesan"
                            placeholder="Tulis pesan ke penjual..."
                            required
                            class="flex-1 border border-slate-300 rounded-xl px-4 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400"
                        >
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl transition">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                    <?php else: ?>
                    <div class="p-4 border-t border-slate-200 text-center text-sm text-slate-400">
                        Anda tidak bisa chat dengan diri sendiri.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Auto scroll chat ke bawah
        const chatBox = document.getElementById('chatBox');
        if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
    </script>
</body>
</html>