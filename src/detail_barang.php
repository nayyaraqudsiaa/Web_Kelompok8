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

// Ambil id_user yang login (sebagai $id_pembeli default untuk user yang sedang aktif)
$res_user = mysqli_query($conn, "SELECT id_user FROM tbl_user WHERE username = '" . mysqli_real_escape_string($conn, $username) . "'");
$current_user = mysqli_fetch_assoc($res_user);
$id_pembeli   = $current_user ? (int)$current_user['id_user'] : 0;

// Ambil id_barang dari URL
$id_barang = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ── Data dummy (sama seperti dashboard) ──────────────────────────────────────
$dummy_barang = [
    1 => ['id_barang' => 1, 'nama_barang' => 'Kipas Angin', 'deskripsi' => 'Kipas angin bekas layak pakai, merk Cosmos.', 'harga' => 50000, 'jumlah' => 2, 'gambar' => 'kipas-angin.jpeg', 'id_user' => 1, 'kondisi' => 'Bekas', 'lama_pakai' => '1 tahun', 'lokasi' => 'Kos Putri Mawar', 'status_barang' => 'tersedia'],
    2 => ['id_barang' => 2, 'nama_barang' => 'Meja Belajar Lipat', 'deskripsi' => 'Meja belajar lipat portable, kondisi bagus.', 'harga' => 35000, 'jumlah' => 1, 'gambar' => 'meja-belajar-lipat.jpg', 'id_user' => 1, 'kondisi' => 'Bekas', 'lama_pakai' => '6 bulan', 'lokasi' => 'Dekat kampus', 'status_barang' => 'tersedia'],
];

// ── Ambil data barang ────────────────────────────────────────────────────────
$barang   = null;
$penjual  = null;

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

// ── Logika Beli Barang (Update Database) ─────────────────────────────────────
// ── Logika Beli Barang (Update Database & Tambah Transaksi) ──────────────────
if (isset($_GET['action']) && $_GET['action'] == 'beli' && $id_pembeli !== $id_penjual && (!isset($barang['status_barang']) || $barang['status_barang'] == 'tersedia')) {
    
    // Mulai transaksi database
    mysqli_begin_transaction($conn);

    try {
        // 1. Update status di tbl_barang
        $query_beli = "UPDATE tbl_barang SET 
                       id_pembeli = ?, 
                       waktu_beli = NOW(), 
                       status_barang = 'dibooking' 
                       WHERE id_barang = ?";
        $stmt = mysqli_prepare($conn, $query_beli);
        mysqli_stmt_bind_param($stmt, "ii", $id_pembeli, $id_barang);
        mysqli_stmt_execute($stmt);

        // 2. Insert data ke tbl_transaksi
        // Kita ambil harga dari variabel $barang yang sudah di-fetch di atas
        $total_harga = $barang['harga'];
        $query_transaksi = "INSERT INTO tbl_transaksi (id_user, id_barang, tanggal, total_harga, status) 
                            VALUES (?, ?, NOW(), ?, 'Pending')";
        
        $stmt_t = mysqli_prepare($conn, $query_transaksi);
        mysqli_stmt_bind_param($stmt_t, "iii", $id_pembeli, $id_barang, $total_harga);
        mysqli_stmt_execute($stmt_t);

        // Jika semua berhasil, simpan permanen
        mysqli_commit($conn);

        header("Location: detail_barang.php?id=" . $id_barang);
        exit();

    } catch (Exception $e) {
        // Jika ada salah satu yang gagal, batalkan semua (rollback)
        mysqli_rollback($conn);
        $pesan_error = "Gagal memproses pembelian. Silakan coba lagi.";
    }
}

// ── Cek Status Booking Berdasarkan Database ──────────────────────────────────
$is_buying = false;
$waktu_habis = 0;

if (isset($barang['status_barang']) && $barang['status_barang'] == 'dibooking') {
    $waktu_beli_timestamp = strtotime($barang['waktu_beli']);
    $waktu_habis = $waktu_beli_timestamp + (24 * 3600); // 24 Jam
    
    // Jika batas waktu sudah habis (lebih dari 24 jam), batalkan otomatis
    if (time() > $waktu_habis) {
        $batal_query = "UPDATE tbl_barang SET status_barang = 'tersedia', id_pembeli = NULL, waktu_beli = NULL WHERE id_barang = ?";
        $stmt_batal = mysqli_prepare($conn, $batal_query);
        mysqli_stmt_bind_param($stmt_batal, "i", $id_barang);
        mysqli_stmt_execute($stmt_batal);
        
        // Refresh halaman agar kembali ke status tersedia
        header("Location: detail_barang.php?id=" . $id_barang);
        exit();
    }
    
    // Pastikan yang sedang "is_buying" adalah user yang login ini
    if ($barang['id_pembeli'] == $id_pembeli) {
        $is_buying = true;
    }
}

// ── Penentu Tampilnya Chat ───────────────────────────────────────────────────
// Cek apakah barang sudah ada yang booking di database
$ada_pembeli = (isset($barang['id_pembeli']) && $barang['id_pembeli'] > 0);

// Chat HANYA tampil jika:
// 1. User login adalah PEMBELI yang sedang membooking barang ini ($is_buying)
// 2. User login adalah PENJUAL, dan barang tersebut SUDAH ada yang booking ($ada_pembeli)
$show_chat = ($is_buying || ($id_pembeli === $id_penjual && $ada_pembeli));

// ── Kirim pesan ──────────────────────────────────────────────────────────────
$pesan_sukses = '';
$pesan_error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['isi_pesan']) && $show_chat) {
    $isi_pesan = trim($_POST['isi_pesan']);
    if ($isi_pesan === '') {
        $pesan_error = 'Pesan tidak boleh kosong.';
    } else {
        // FIX: Jika penjual membalas, kita harus memastikan `id_pembeli` pada tbl_pesan
        // tetap terisi dengan ID pembeli yang sebenarnya, bukan ID penjual.
        $insert_buyer_id = ($id_pembeli === $id_penjual) ? $barang['id_pembeli'] : $id_pembeli;
        $id_pengirim = $id_pembeli; // Menggunakan variabel $id_pembeli karena ini menyimpan user yang sedang login
        
        $stmt2 = mysqli_prepare($conn, "INSERT INTO tbl_pesan (id_pembeli, id_penjual, id_barang, id_pengirim, isi_pesan, waktu_kirim, status_baca) VALUES (?, ?, ?, ?, ?, NOW(), 0)");
        mysqli_stmt_bind_param($stmt2, "iiiis", $insert_buyer_id, $id_penjual, $id_barang, $id_pengirim, $isi_pesan);
        if (mysqli_stmt_execute($stmt2)) {
            $pesan_sukses = 'Pesan berhasil dikirim!';
        } else {
            $pesan_error = 'Gagal mengirim pesan. Coba lagi.';
        }
    }
}

// ── Ambil riwayat chat ───────────────────────────────────────────────────────
$chats = [];
if ($show_chat) {
    // Ambil chat khusus untuk transaksi spesifik ini
    // Jika yang login penjual, cari ID pembeli dari data barang. Jika pembeli, ambil dari sessionnya.
    $chat_pembeli = ($id_pembeli === $id_penjual) ? $barang['id_pembeli'] : $id_pembeli;
    
    $stmt3 = mysqli_prepare($conn, "SELECT p.*, ub.username as nama_pembeli, uj.username as nama_penjual FROM tbl_pesan p LEFT JOIN tbl_user ub ON p.id_pembeli = ub.id_user LEFT JOIN tbl_user uj ON p.id_penjual = uj.id_user WHERE p.id_barang = ? AND p.id_pembeli = ? AND p.id_penjual = ? ORDER BY p.waktu_kirim ASC");
    mysqli_stmt_bind_param($stmt3, "iii", $id_barang, $chat_pembeli, $id_penjual);
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
        .glass { background: rgba(255,255,255,0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.15); }
        .chat-box { height: 350px; overflow-y: auto; scroll-behavior: smooth; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-900 via-blue-800 to-blue-950 min-h-screen text-white">

    <aside class="fixed left-0 top-0 w-64 h-full glass shadow-xl z-10">
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
            <a href="logout.php" class="flex items-center gap-3 p-3 rounded-xl bg-red-500/20 border border-red-400/30 text-red-200 hover:bg-red-500/30"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </aside>

    <main class="ml-64 p-8 bg-gray-100 min-h-screen text-slate-800">
        <a href="dashboard.php" class="inline-flex items-center gap-2 text-blue-700 hover:text-blue-900 mb-6 font-medium">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>

        <div class="<?= $show_chat ? 'max-w-6xl lg:grid-cols-2' : 'max-w-3xl' ?> mx-auto grid grid-cols-1 gap-8 transition-all duration-300">

            <div>
                <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                    <img src="<?= !empty($barang['gambar']) ? '../assets/img/' . htmlspecialchars($barang['gambar']) : 'https://placehold.co/600x400?text=No+Image' ?>" class="w-full h-64 object-cover" alt="<?= htmlspecialchars($barang['nama_barang']) ?>">
                    <div class="p-6">
                        <h1 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($barang['nama_barang']) ?></h1>
                        <p class="text-3xl font-bold text-blue-600 mt-2">Rp <?= number_format($barang['harga'], 0, ',', '.') ?></p>

                        <div class="mt-4 space-y-2 text-sm text-slate-600">
                            <div class="flex items-center gap-2"><i class="fas fa-tag text-blue-400 w-5"></i><span><strong>Kondisi:</strong> <?= htmlspecialchars($barang['kondisi'] ?? 'Bekas') ?></span></div>
                            <div class="flex items-center gap-2"><i class="fas fa-clock text-blue-400 w-5"></i><span><strong>Lama pakai:</strong> <?= htmlspecialchars($barang['lama_pakai'] ?? '-') ?></span></div>
                            <div class="flex items-center gap-2"><i class="fas fa-cubes text-blue-400 w-5"></i><span><strong>Stok:</strong> <?= (int)$barang['jumlah'] ?> unit</span></div>
                            <div class="flex items-center gap-2"><i class="fas fa-map-marker-alt text-blue-400 w-5"></i><span><strong>Lokasi:</strong> <?= htmlspecialchars($barang['lokasi'] ?? '-') ?></span></div>
                        </div>

                        <div class="mt-4 p-4 bg-slate-50 rounded-xl">
                            <p class="text-sm font-semibold text-slate-700 mb-1">Deskripsi</p>
                            <p class="text-sm text-slate-600"><?= nl2br(htmlspecialchars($barang['deskripsi'])) ?></p>
                        </div>

                        <?php if ($id_pembeli !== $id_penjual): ?>
                            
                            <?php if (!isset($barang['status_barang']) || $barang['status_barang'] == 'tersedia'): ?>
                                <a href="detail_barang.php?id=<?= $id_barang ?>&action=beli"
                                   class="mt-6 flex items-center justify-center gap-2 w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition shadow-lg shadow-blue-600/30">
                                    <i class="fas fa-shopping-cart"></i> Beli Barang
                                </a>
                                
                            <?php elseif ($barang['status_barang'] == 'dibooking' && $is_buying): ?>
                                <div class="mt-6 bg-orange-50 border border-orange-200 rounded-xl p-5 text-center shadow-inner">
                                    <p class="text-sm text-orange-600 font-semibold mb-2"><i class="fas fa-stopwatch"></i> Sedang Dibooking. Selesaikan dalam:</p>
                                    <div id="countdown" class="text-3xl font-bold text-orange-700 font-mono tracking-widest">
                                        --:--:--
                                    </div>
                                    <p class="text-xs text-orange-500 mt-3">Fitur chat terbuka! Silakan hubungi penjual untuk COD atau pembayaran.</p>
                                </div>
                                
                            <?php elseif ($barang['status_barang'] == 'dibooking' && !$is_buying): ?>
                                <div class="mt-6 flex items-center justify-center gap-2 w-full bg-slate-200 text-slate-500 py-3 rounded-xl font-semibold cursor-not-allowed">
                                    <i class="fas fa-hourglass-half"></i> Sedang Dibooking Orang Lain
                                </div>
                                
                            <?php elseif ($barang['status_barang'] == 'terjual'): ?>
                                <div class="mt-6 flex items-center justify-center gap-2 w-full bg-red-100 text-red-600 py-3 rounded-xl font-bold cursor-not-allowed border border-red-200">
                                    <i class="fas fa-times-circle"></i> BARANG SUDAH TERJUAL
                                </div>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="mt-6 flex items-center justify-center gap-2 w-full bg-slate-200 text-slate-500 py-3 rounded-xl font-semibold cursor-not-allowed">
                                <i class="fas fa-store"></i> Ini barang jualan Anda
                            </div>
                        <?php endif; ?>
                        
                    </div>
                </div>
            </div>

            <?php if ($show_chat): ?>
            <div class="flex flex-col gap-4">
                <div class="bg-white rounded-2xl shadow-md overflow-hidden flex flex-col h-full">
                    
                    <div class="bg-blue-700 text-white px-5 py-4 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-400 flex items-center justify-center font-bold text-lg shadow-inner">
                            <?= strtoupper(substr($id_pembeli === $id_penjual ? ($barang['nama_pembeli'] ?? 'P') : ($barang['nama_penjual'] ?? 'P'), 0, 1)) ?>
                        </div>
                        <div>
                            <p class="font-semibold">
                                <?= $id_pembeli === $id_penjual ? "Pembeli" : htmlspecialchars($barang['nama_penjual'] ?? 'Penjual') ?>
                            </p>
                            <p class="text-xs text-blue-200">
                                <?= $is_buying || $ada_pembeli ? '<i class="fas fa-circle text-green-400 text-[10px]"></i> Sedang Transaksi' : 'Penjual Rekos' ?>
                            </p>
                        </div>
                        <i class="fas fa-comments ml-auto text-blue-200 text-xl"></i>
                    </div>

                    <?php if ($pesan_sukses): ?>
                    <div class="mx-4 mt-3 p-3 bg-green-100 text-green-700 rounded-xl text-sm"><i class="fas fa-check-circle"></i> <?= $pesan_sukses ?></div>
                    <?php endif; ?>
                    <?php if ($pesan_error): ?>
                    <div class="mx-4 mt-3 p-3 bg-red-100 text-red-700 rounded-xl text-sm"><i class="fas fa-exclamation-circle"></i> <?= $pesan_error ?></div>
                    <?php endif; ?>

                    <div class="chat-box p-4 flex flex-col gap-3 bg-slate-50 flex-1" id="chatBox">
                        <?php if (empty($chats)): ?>
                            <div class="text-center text-slate-400 text-sm mt-12">
                                <i class="fas fa-handshake text-4xl mb-3 block text-slate-300"></i>
                                Transaksi dimulai! Silakan sapa untuk mengatur pertemuan/pembayaran.
                            </div>
                        <?php else: ?>
                            <?php foreach ($chats as $c): ?>
                                <?php 
                                    // Deteksi gelembung pesan berdasarkan siapa pengirim aslinya
                                    // $id_pembeli di variabel awal Anda menyimpan ID user yang sedang login
                                    $is_me = ($c['id_pengirim'] == $id_pembeli); 
                                ?>
                                <div class="flex <?= $is_me ? 'justify-end' : 'justify-start' ?>">
                                    <div class="max-w-[80%] px-4 py-2 text-sm shadow <?= $is_me ? 'bg-blue-600 text-white rounded-2xl rounded-br-none' : 'bg-white text-slate-700 border border-slate-200 rounded-2xl rounded-bl-none' ?>">
                                        <p><?= nl2br(htmlspecialchars($c['isi_pesan'])) ?></p>
                                        <p class="text-[10px] mt-1 <?= $is_me ? 'text-blue-200' : 'text-slate-400' ?> text-right">
                                            <?= date('d M, H:i', strtotime($c['waktu_kirim'])) ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($barang['status_barang'] !== 'terjual'): ?>
                    <form method="POST" class="p-4 bg-white border-t border-slate-200 flex gap-2">
                        <input type="text" name="isi_pesan" placeholder="Ketik pesan..." required class="flex-1 border border-slate-300 rounded-xl px-4 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl transition shadow-md"><i class="fas fa-paper-plane"></i></button>
                    </form>
                    <?php else: ?>
                    <div class="p-4 border-t border-slate-200 text-center text-sm text-slate-400 bg-white">
                        <?= $barang['status_barang'] == 'terjual' ? 'Barang sudah terjual, chat ditutup.' : 'Menunggu pesan dari pembeli...' ?>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <script>
        // Auto scroll chat ke bawah
        const chatBox = document.getElementById('chatBox');
        if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

        // Script Timer 24 Jam
        <?php if ($is_buying): ?>
        const endTime = <?= $waktu_habis ?> * 1000;
        const timerEl = document.getElementById('countdown');

        function updateTimer() {
            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance < 0) {
                timerEl.innerHTML = "WAKTU HABIS";
                timerEl.classList.replace("text-orange-700", "text-red-600");
                // Reload halaman agar PHP membatalkan transaksi di DB
                setTimeout(() => window.location.reload(), 1500); 
                return;
            }

            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            timerEl.innerHTML = 
                String(hours).padStart(2, '0') + ":" + 
                String(minutes).padStart(2, '0') + ":" + 
                String(seconds).padStart(2, '0');
        }

        setInterval(updateTimer, 1000);
        updateTimer(); 
        <?php endif; ?>
    </script>
</body>
</html>