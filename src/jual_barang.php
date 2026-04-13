<?php
session_start();
require 'koneksi.php';

$session_timeout = 600;

// Cek login
if (!isset($_SESSION['username']) || !isset($_SESSION['status'])) {
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

$id_user  = $_SESSION['id_user'];
$username = $_SESSION['username'];

// Ambil data user dari DB
$res_user = $conn->prepare("SELECT * FROM tbl_user WHERE id_user = ?");
$res_user->bind_param("i", $id_user);
$res_user->execute();
$user = $res_user->get_result()->fetch_assoc();
$role_asli = $user['role'] ?? 'pembeli'; // role permanen di DB

// Role aktif = dari session (bisa di-switch lewat profil)
$role = $_SESSION['role'] ?? $role_asli;

$currentPage = basename($_SERVER['PHP_SELF']);

// ════════════════════════════════════════════════════════════
// PROSES 1: Daftar sebagai penjual
// ════════════════════════════════════════════════════════════
$upgrade_sukses = false;
$upgrade_error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['daftar_penjual'])) {
    $alamat  = trim($_POST['alamat']  ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');

    if ($alamat === '' || $no_telp === '') {
        $upgrade_error = 'Alamat dan no. telepon wajib diisi.';
    } else {
        $upd = $conn->prepare("UPDATE tbl_user SET role = 'penjual', alamat = ?, no_telp = ? WHERE id_user = ?");
        $upd->bind_param("ssi", $alamat, $no_telp, $id_user);
        if ($upd->execute()) {
            $_SESSION['role'] = 'penjual';
            $role = 'penjual';
            $upgrade_sukses = true;
        } else {
            $upgrade_error = 'Gagal mendaftar. Silakan coba lagi.';
        }
    }
}

// ════════════════════════════════════════════════════════════
// PROSES 2: Upload barang (hanya jika sudah penjual)
// ════════════════════════════════════════════════════════════
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jual_barang']) && $role === 'penjual') {
    $nama_barang = trim($_POST['nama_barang']);
    $deskripsi   = trim($_POST['deskripsi']);
    $harga       = (int)$_POST['harga'];
    $jumlah      = (int)$_POST['jumlah'];

    if (empty($nama_barang) || empty($deskripsi) || $harga <= 0 || $jumlah <= 0) {
        $error = "Semua field wajib diisi dengan benar.";
    } elseif (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== 0) {
        $error = "Gambar barang wajib diupload.";
    } else {
        $folder = "../assets/img/";
        if (!is_dir($folder)) mkdir($folder, 0777, true);

        $namaAsli   = $_FILES['gambar']['name'];
        $tmpFile    = $_FILES['gambar']['tmp_name'];
        $ukuranFile = $_FILES['gambar']['size'];
        $ext        = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));
        $allowed    = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            $error = "Format gambar harus jpg, jpeg, png, atau webp.";
        } elseif ($ukuranFile > 2 * 1024 * 1024) {
            $error = "Ukuran gambar maksimal 2 MB.";
        } else {
            $namaFileBaru = time() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "_", $namaAsli);
            if (move_uploaded_file($tmpFile, $folder . $namaFileBaru)) {
                $stmt = $conn->prepare("INSERT INTO tbl_barang (nama_barang, deskripsi, harga, jumlah, gambar, id_user) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiisi", $nama_barang, $deskripsi, $harga, $jumlah, $namaFileBaru, $id_user);
                if ($stmt->execute()) {
                    $success = "Barang berhasil ditambahkan.";
                } else {
                    $error = "Gagal menyimpan data barang ke database.";
                }
            } else {
                $error = "Gagal upload gambar.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Jual Barang - Rekos</title>
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
        input::placeholder, textarea::placeholder { color: #94a3b8; opacity: 1; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-900 via-blue-800 to-blue-950 min-h-screen text-white">

    <!-- Sidebar -->
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

    <!-- Main -->
    <main class="ml-64 p-8 bg-gray-100 min-h-screen">
        <header class="bg-blue-800 rounded-2xl p-6 mb-8 shadow text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Jual Barang</h1>
                    <p class="text-slate-300">Upload barang bekas yang masih layak pakai</p>
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

        <div class="max-w-4xl mx-auto">

        <?php if ($role !== 'penjual'): ?>
        <!-- ══════════════════════════════════════════
             BUKAN MODE PENJUAL
        ══════════════════════════════════════════ -->

            <?php if ($role_asli === 'penjual'): ?>
            <!-- Sudah terdaftar penjual tapi mode aktif = pembeli -->
            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-yellow-400 to-orange-400 p-6 text-white">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold">Kamu Sedang dalam Mode Pembeli</h2>
                            <p class="text-yellow-100 text-sm">Ganti ke mode penjual untuk mulai menjual barang</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 text-center">
                    <p class="text-slate-600 mb-2">Akun kamu sudah terdaftar sebagai <strong>Penjual Rekos</strong>.</p>
                    <p class="text-slate-500 text-sm mb-6">Untuk mengupload barang, kamu perlu beralih ke <strong>Mode Penjual</strong> terlebih dahulu melalui halaman Profil.</p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <a href="profil.php"
                           class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold transition">
                            <i class="fas fa-store"></i> Ke Halaman Profil & Ganti Mode
                        </a>
                        <a href="dashboard.php"
                           class="inline-flex items-center justify-center gap-2 bg-slate-200 hover:bg-slate-300 text-slate-700 px-6 py-3 rounded-xl font-semibold transition">
                            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    </div>
                    <p class="mt-4 text-xs text-slate-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        Di halaman Profil, klik tombol <strong>"Beralih ke Mode Penjual"</strong>
                    </p>
                </div>
            </div>

            <?php elseif ($upgrade_sukses): ?>
            <!-- Sukses daftar penjual -->
            <div class="bg-white rounded-2xl shadow-md p-8 text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-check-circle text-green-500 text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Selamat! Kamu Sudah Jadi Penjual</h2>
                <p class="text-slate-500 mb-6">Akun kamu sekarang bisa menjual barang di Rekos.</p>
                <a href="jual_barang.php"
                   class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-xl font-semibold transition">
                    Mulai Jual Barang <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>

            <?php else: ?>
            <!-- Form daftar penjual -->
            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6 text-white">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-store text-2xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold">Daftarkan Diri Sebagai Penjual</h2>
                            <p class="text-blue-200 text-sm">Mulai jual barang bekas kamu di Rekos</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 border-b border-slate-100">
                    <p class="text-sm font-semibold text-slate-600 mb-3">Keuntungan menjadi penjual Rekos:</p>
                    <div class="space-y-2 text-sm text-slate-600">
                        <div class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Upload barang bekas kapan saja</div>
                        <div class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Terima pesan langsung dari pembeli</div>
                        <div class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Kelola barang dan transaksi dengan mudah</div>
                        <div class="flex items-center gap-2"><i class="fas fa-check-circle text-green-500"></i> Tetap bisa berbelanja sebagai pembeli</div>
                    </div>
                </div>
                <div class="p-6">
                    <h3 class="font-semibold text-slate-700 mb-4">Lengkapi Data Diri</h3>
                    <?php if ($upgrade_error): ?>
                    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-xl text-sm flex items-center gap-2">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($upgrade_error) ?>
                    </div>
                    <?php endif; ?>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="daftar_penjual" value="1">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">
                                <i class="fas fa-map-marker-alt text-blue-500 mr-1"></i> Alamat Lengkap
                            </label>
                            <textarea name="alamat" rows="3" required
                                placeholder="Contoh: Kos Melati, Jl. Sumber No. 5, Surabaya"
                                class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"
                            ><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">
                                <i class="fas fa-phone text-blue-500 mr-1"></i> No. Telepon / WhatsApp
                            </label>
                            <input type="text" name="no_telp" required
                                placeholder="Contoh: 08123456789"
                                value="<?= htmlspecialchars($user['no_telp'] ?? '') ?>"
                                class="w-full border border-slate-300 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <button type="submit"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-semibold transition flex items-center justify-center gap-2">
                            <i class="fas fa-store"></i> Daftar Sebagai Penjual Sekarang
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
        <!-- ══════════════════════════════════════════
             SUDAH PENJUAL → Form upload barang
        ══════════════════════════════════════════ -->
            <div class="bg-white rounded-2xl shadow-md p-8">
                <h2 class="text-2xl font-bold text-slate-800 mb-2">Form Jual Barang</h2>
                <p class="text-slate-500 text-sm mb-6">Isi data barang dengan lengkap agar pembeli lebih mudah menemukan barangmu.</p>

                <?php if (!empty($success)): ?>
                <div class="mb-5 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-700">
                    <?= htmlspecialchars($success) ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-700">
                    <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="jual_barang" value="1">
                    <div>
                        <label for="nama_barang" class="block mb-2 text-sm font-semibold text-slate-700">Nama Barang</label>
                        <input type="text" id="nama_barang" name="nama_barang" required
                            placeholder="Contoh: Kipas Angin Cosmos"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label for="deskripsi" class="block mb-2 text-sm font-semibold text-slate-700">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" required
                            placeholder="Jelaskan kondisi barang, lama pemakaian, dan detail penting lainnya..."
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[140px]"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="harga" class="block mb-2 text-sm font-semibold text-slate-700">Harga (Rp)</label>
                            <input type="number" id="harga" name="harga" min="1" required
                                placeholder="Contoh: 50000"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label for="jumlah" class="block mb-2 text-sm font-semibold text-slate-700">Jumlah Barang</label>
                            <input type="number" id="jumlah" name="jumlah" min="1" required
                                placeholder="Contoh: 1"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>
                    <div>
                        <label for="gambar" class="block mb-2 text-sm font-semibold text-slate-700">Upload Gambar Barang</label>
                        <input type="file" id="gambar" name="gambar" accept=".jpg,.jpeg,.png,.webp" required
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-white hover:file:bg-blue-700">
                        <p class="mt-2 text-xs text-slate-500">Format: JPG, JPEG, PNG, WEBP. Maksimal 2 MB.</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <button type="submit"
                            class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700 transition">
                            Upload Barang
                        </button>
                        <a href="barang_saya.php"
                            class="rounded-xl bg-slate-200 px-6 py-3 font-semibold text-slate-700 hover:bg-slate-300 transition text-center">
                            Lihat Barang Saya
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        </div>
    </main>
</body>
</html>