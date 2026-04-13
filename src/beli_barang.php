<?php
session_start();
include 'koneksi.php';

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Member';

// Ambil id_user pembeli
$res_user = mysqli_query($conn, "SELECT id_user FROM tbl_user WHERE username = '" . mysqli_real_escape_string($conn, $username) . "'");
$current_user = mysqli_fetch_assoc($res_user);
$id_user = $current_user ? (int)$current_user['id_user'] : 0;

// Ambil id_barang dari URL
$id_barang = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ── Data dummy ───────────────────────────────────────────────────────────────
$dummy_barang = [
    1 => ['id_barang'=>1,'nama_barang'=>'Kipas Angin','deskripsi'=>'Kipas angin bekas layak pakai, merk Cosmos.','harga'=>50000,'jumlah'=>2,'gambar'=>'kipas-angin.jpeg','kondisi'=>'Bekas','lama_pakai'=>'1 tahun','lokasi'=>'Kos Putri Mawar','id_user'=>1],
    2 => ['id_barang'=>2,'nama_barang'=>'Meja Belajar Lipat','deskripsi'=>'Meja belajar lipat portable, kondisi bagus.','harga'=>35000,'jumlah'=>1,'gambar'=>'meja-belajar-lipat.jpg','kondisi'=>'Bekas','lama_pakai'=>'6 bulan','lokasi'=>'Dekat kampus','id_user'=>1],
    3 => ['id_barang'=>3,'nama_barang'=>'Rice Cooker','deskripsi'=>'Rice cooker mini 0.5L, masih berfungsi normal.','harga'=>65000,'jumlah'=>1,'gambar'=>'rice-cooker.jpeg','kondisi'=>'Bekas','lama_pakai'=>'2 tahun','lokasi'=>'Area kampus','id_user'=>1],
    4 => ['id_barang'=>4,'nama_barang'=>'Lampu Belajar','deskripsi'=>'Lampu meja LED, hemat listrik.','harga'=>25000,'jumlah'=>3,'gambar'=>'','kondisi'=>'Bekas','lama_pakai'=>'8 bulan','lokasi'=>'Kos Melati','id_user'=>1],
    5 => ['id_barang'=>5,'nama_barang'=>'Dispenser Mini','deskripsi'=>'Dispenser kecil cocok untuk kamar kos.','harga'=>45000,'jumlah'=>1,'gambar'=>'','kondisi'=>'Bekas','lama_pakai'=>'1.5 tahun','lokasi'=>'Kos Anggrek','id_user'=>1],
    6 => ['id_barang'=>6,'nama_barang'=>'Rak Buku','deskripsi'=>'Rak buku 3 susun, bahan kayu ringan.','harga'=>40000,'jumlah'=>2,'gambar'=>'','kondisi'=>'Bekas','lama_pakai'=>'1 tahun','lokasi'=>'Kos Dahlia','id_user'=>1],
    7 => ['id_barang'=>7,'nama_barang'=>'Setrika','deskripsi'=>'Setrika listrik bekas, panas merata.','harga'=>30000,'jumlah'=>1,'gambar'=>'','kondisi'=>'Bekas','lama_pakai'=>'2 tahun','lokasi'=>'Kos Mawar','id_user'=>1],
    8 => ['id_barang'=>8,'nama_barang'=>'Cermin Dinding','deskripsi'=>'Cermin oval bingkai putih, ukuran sedang.','harga'=>20000,'jumlah'=>2,'gambar'=>'','kondisi'=>'Bekas','lama_pakai'=>'3 bulan','lokasi'=>'Kos Kenanga','id_user'=>1],
];

// ── Ambil data barang: DB dulu, fallback dummy ───────────────────────────────
$barang = null;
if ($id_barang > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM tbl_barang WHERE id_barang = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_barang);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $barang = mysqli_fetch_assoc($res);
    }
}
if (!$barang && isset($dummy_barang[$id_barang])) {
    $barang = $dummy_barang[$id_barang];
}
if (!$barang) {
    header("Location: dashboard.php");
    exit();
}

// Cek apakah pembeli = penjual
if ((int)$barang['id_user'] === $id_user) {
    header("Location: detail_barang.php?id=$id_barang");
    exit();
}

// ── Proses konfirmasi pembelian ──────────────────────────────────────────────
$sukses = false;
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi'])) {
    $jumlah_beli = max(1, (int)($_POST['jumlah_beli'] ?? 1));
    $total_harga = $jumlah_beli * (int)$barang['harga'];

    // Cek stok cukup
    if ($jumlah_beli > (int)$barang['jumlah']) {
        $error = 'Jumlah pembelian melebihi stok yang tersedia.';
    } else {
        // Simpan transaksi
        $stmt2 = mysqli_prepare($conn,
            "INSERT INTO tbl_transaksi (id_user, id_barang, tanggal, total_harga, status)
             VALUES (?, ?, NOW(), ?, 'Pending')"
        );
        mysqli_stmt_bind_param($stmt2, "iii", $id_user, $id_barang, $total_harga);

        if (mysqli_stmt_execute($stmt2)) {
            // Kurangi stok jika barang dari DB (bukan dummy)
            if (!isset($dummy_barang[$id_barang]) || $barang !== $dummy_barang[$id_barang]) {
                $stok_baru = (int)$barang['jumlah'] - $jumlah_beli;
                $upd = mysqli_prepare($conn, "UPDATE tbl_barang SET jumlah = ? WHERE id_barang = ?");
                mysqli_stmt_bind_param($upd, "ii", $stok_baru, $id_barang);
                mysqli_stmt_execute($upd);
            }
            $sukses = true;
        } else {
            $error = 'Gagal: ' . mysqli_stmt_error($stmt2);
        }
    }
}

$jumlah_beli_form = max(1, (int)($_POST['jumlah_beli'] ?? 1));
$total_preview    = $jumlah_beli_form * (int)$barang['harga'];
?>
<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Beli Barang - Rekos</title>
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

        <a href="detail_barang.php?id=<?= $id_barang ?>" class="inline-flex items-center gap-2 text-blue-700 hover:text-blue-900 mb-6 font-medium">
            <i class="fas fa-arrow-left"></i> Kembali ke Detail Barang
        </a>

        <?php if ($sukses): ?>
        <!-- ── SUKSES ── -->
        <div class="max-w-lg mx-auto mt-10 bg-white rounded-2xl shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check-circle text-green-500 text-4xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-800 mb-2">Pesanan Berhasil!</h2>
            <p class="text-slate-500 mb-1">Transaksi untuk <strong><?= htmlspecialchars($barang['nama_barang']) ?></strong> telah dicatat.</p>
            <p class="text-slate-500 mb-6">Status: <span class="text-yellow-600 font-semibold">Menunggu Konfirmasi Penjual</span></p>
            <div class="bg-slate-50 rounded-xl p-4 text-left mb-6 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Barang</span>
                    <span class="font-medium"><?= htmlspecialchars($barang['nama_barang']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Jumlah</span>
                    <span class="font-medium"><?= $jumlah_beli_form ?> unit</span>
                </div>
                <div class="flex justify-between border-t pt-2 mt-2">
                    <span class="font-semibold">Total Bayar</span>
                    <span class="font-bold text-blue-600">Rp <?= number_format($total_preview, 0, ',', '.') ?></span>
                </div>
            </div>
            <div class="flex gap-3">
                <a href="dashboard.php" class="flex-1 text-center bg-slate-200 hover:bg-slate-300 text-slate-700 py-3 rounded-xl font-semibold transition">
                    Kembali ke Dashboard
                </a>
                <a href="barang_saya.php" class="flex-1 text-center bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition">
                    Lihat Transaksi
                </a>
            </div>
        </div>

        <?php else: ?>
        <!-- ── FORM RINGKASAN ── -->
        <div class="max-w-2xl mx-auto">
            <h1 class="text-2xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                <i class="fas fa-shopping-cart text-blue-600"></i> Ringkasan Pembelian
            </h1>

            <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-xl flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-2xl shadow-md overflow-hidden mb-6">
                <!-- Info barang -->
                <div class="flex gap-4 p-5 border-b border-slate-100">
                    <img
                        src="<?= !empty($barang['gambar']) ? '../assets/img/' . htmlspecialchars($barang['gambar']) : 'https://placehold.co/120x120?text=No+Image' ?>"
                        class="w-24 h-24 rounded-xl object-cover flex-shrink-0"
                        alt="<?= htmlspecialchars($barang['nama_barang']) ?>"
                    >
                    <div class="flex-1">
                        <h2 class="text-lg font-bold text-slate-800"><?= htmlspecialchars($barang['nama_barang']) ?></h2>
                        <p class="text-blue-600 text-xl font-bold">Rp <?= number_format($barang['harga'], 0, ',', '.') ?> <span class="text-slate-400 text-sm font-normal">/ unit</span></p>
                        <div class="flex flex-wrap gap-3 mt-2 text-xs text-slate-500">
                            <span class="flex items-center gap-1"><i class="fas fa-tag text-blue-400"></i> <?= htmlspecialchars($barang['kondisi'] ?? 'Bekas') ?></span>
                            <span class="flex items-center gap-1"><i class="fas fa-map-marker-alt text-blue-400"></i> <?= htmlspecialchars($barang['lokasi'] ?? '-') ?></span>
                            <span class="flex items-center gap-1"><i class="fas fa-cubes text-blue-400"></i> Stok: <?= (int)$barang['jumlah'] ?></span>
                        </div>
                    </div>
                </div>

                <!-- Form jumlah & total -->
                <form method="POST" class="p-5">
                    <input type="hidden" name="konfirmasi" value="1">

                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Jumlah Pembelian</label>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="ubahJumlah(-1)"
                                class="w-10 h-10 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-lg transition flex items-center justify-center">
                                <i class="fas fa-minus text-sm"></i>
                            </button>
                            <input
                                type="number"
                                name="jumlah_beli"
                                id="jumlah_beli"
                                value="1"
                                min="1"
                                max="<?= (int)$barang['jumlah'] ?>"
                                class="w-20 text-center border border-slate-300 rounded-xl py-2 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-400"
                                oninput="updateTotal()"
                            >
                            <button type="button" onclick="ubahJumlah(1)"
                                class="w-10 h-10 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-lg transition flex items-center justify-center">
                                <i class="fas fa-plus text-sm"></i>
                            </button>
                            <span class="text-sm text-slate-400">Maks. <?= (int)$barang['jumlah'] ?> unit</span>
                        </div>
                    </div>

                    <!-- Rincian harga -->
                    <div class="bg-slate-50 rounded-xl p-4 mb-5 space-y-2 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Harga satuan</span>
                            <span>Rp <?= number_format($barang['harga'], 0, ',', '.') ?></span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Jumlah</span>
                            <span id="label_jumlah">1 unit</span>
                        </div>
                        <div class="flex justify-between font-bold text-slate-800 border-t pt-2 text-base">
                            <span>Total Pembayaran</span>
                            <span class="text-blue-600 text-lg" id="label_total">
                                Rp <?= number_format($barang['harga'], 0, ',', '.') ?>
                            </span>
                        </div>
                    </div>

                    <!-- Info pembeli -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-5 text-sm text-slate-600">
                        <p class="font-semibold text-slate-700 mb-1"><i class="fas fa-info-circle text-blue-500 mr-1"></i> Info Pembelian</p>
                        <p>Pembeli: <strong><?= htmlspecialchars($username) ?></strong></p>
                        <p class="mt-1 text-xs text-slate-400">Setelah konfirmasi, status transaksi akan menjadi <em>"Menunggu"</em> hingga penjual memproses pesanan Anda.</p>
                    </div>

                    <!-- Tombol -->
                    <div class="flex gap-3">
                        <a href="detail_barang.php?id=<?= $id_barang ?>"
                           class="flex-1 text-center bg-slate-200 hover:bg-slate-300 text-slate-700 py-3 rounded-xl font-semibold transition">
                            Batal
                        </a>
                        <button type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition flex items-center justify-center gap-2">
                            <i class="fas fa-check"></i> Konfirmasi Pembelian
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </main>

    <script>
        const harga = <?= (int)$barang['harga'] ?>;
        const stokMax = <?= (int)$barang['jumlah'] ?>;

        function formatRupiah(angka) {
            return 'Rp ' + angka.toLocaleString('id-ID');
        }

        function updateTotal() {
            const input = document.getElementById('jumlah_beli');
            let qty = parseInt(input.value) || 1;
            if (qty < 1) qty = 1;
            if (qty > stokMax) qty = stokMax;
            input.value = qty;
            document.getElementById('label_jumlah').textContent = qty + ' unit';
            document.getElementById('label_total').textContent = formatRupiah(qty * harga);
        }

        function ubahJumlah(delta) {
            const input = document.getElementById('jumlah_beli');
            let qty = (parseInt(input.value) || 1) + delta;
            if (qty < 1) qty = 1;
            if (qty > stokMax) qty = stokMax;
            input.value = qty;
            updateTotal();
        }
    </script>
</body>
</html>