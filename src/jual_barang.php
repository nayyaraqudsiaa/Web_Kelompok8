<?php
session_start();
require 'koneksi.php';

$session_timeout = 600; // 10 menit

// cek login
if (!isset($_SESSION['username']) || !isset($_SESSION['status'])) {
    header("Location: login.php");
    exit();
}

// cek timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?expired=1");
    exit();
}

// update aktivitas
$_SESSION['last_activity'] = time();

$id_user = $_SESSION['id_user'];
$username = $_SESSION['username'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'pembeli';

// otomatis ubah role jadi penjual saat masuk halaman jual barang
if ($role !== 'penjual') {
    $updateRole = $conn->prepare("UPDATE tbl_user SET role = 'penjual' WHERE id_user = ?");
    $updateRole->bind_param("i", $id_user);
    $updateRole->execute();
    $_SESSION['role'] = 'penjual';
    $role = 'penjual';
}

$success = "";
$error = "";
$currentPage = basename($_SERVER['PHP_SELF']);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_barang = trim($_POST['nama_barang']);
    $deskripsi = trim($_POST['deskripsi']);
    $harga = (int) $_POST['harga'];
    $jumlah = (int) $_POST['jumlah'];

    if (empty($nama_barang) || empty($deskripsi) || $harga <= 0 || $jumlah <= 0) {
        $error = "Semua field wajib diisi dengan benar.";
    } elseif (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== 0) {
        $error = "Gambar barang wajib diupload.";
    } else {
        // samakan ke folder img agar konsisten dengan dashboard kamu
        $folder = "../assets/img/";

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $namaAsli = $_FILES['gambar']['name'];
        $tmpFile = $_FILES['gambar']['tmp_name'];
        $ukuranFile = $_FILES['gambar']['size'];

        $ext = strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

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
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />

    <style>
        @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap");

        * {
            font-family: "Inter", sans-serif;
        }

        .glass {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .glass:hover {
            background: rgba(255, 255, 255, 0.12);
            transition: 0.2s;
        }

        input::placeholder,
        textarea::placeholder {
            color: #94a3b8;
            opacity: 1;
        }
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
            <a href="dashboard.php"
               class="flex items-center gap-3 p-3 rounded-xl <?= $currentPage == 'dashboard.php' ? 'bg-blue-500/20 border border-blue-400/30' : 'hover:bg-white/10' ?>">
                <i class="fas fa-home text-blue-400"></i>
                Dashboard
            </a>

            <a href="barang_saya.php"
               class="flex items-center gap-3 p-3 rounded-xl <?= $currentPage == 'barang_saya.php' ? 'bg-blue-500/20 border border-blue-400/30' : 'hover:bg-white/10' ?>">
                <i class="fas fa-box text-blue-400"></i>
                Barang Saya
            </a>

            <a href="jual_barang.php"
               class="flex items-center gap-3 p-3 rounded-xl <?= $currentPage == 'jual_barang.php' ? 'bg-blue-500/20 border border-blue-400/30' : 'hover:bg-white/10' ?>">
                <i class="fas fa-plus-circle text-blue-400"></i>
                Jual Barang
            </a>

            <a href="profil.php"
               class="flex items-center gap-3 p-3 rounded-xl <?= $currentPage == 'profil.php' ? 'bg-blue-500/20 border border-blue-400/30' : 'hover:bg-white/10' ?>">
                <i class="fas fa-user text-blue-400"></i>
                Profil
            </a>
        </nav>

        <div class="absolute bottom-6 left-6 right-6">
            <a href="logout.php"
               class="flex items-center gap-3 p-3 rounded-xl bg-red-500/20 border border-red-400/30 text-red-200 hover:bg-red-500/30">
                <i class="fas fa-sign-out-alt"></i>
                Logout
            </a>
        </div>
    </aside>

    <main class="ml-64 p-8 bg-gray-100 min-h-screen">
        <header class="bg-blue-800 rounded-2xl p-6 mb-8 shadow text-white">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold">Jual Barang</h1>
                    <p class="text-slate-300">Upload barang bekas yang masih layak pakai</p>
                </div>

                <div class="flex items-center gap-3 bg-white/10 px-4 py-2 rounded-xl">
                    <img
                        src="https://ui-avatars.com/api/?name=<?= urlencode($username) ?>&background=3b82f6&color=fff"
                        class="w-10 h-10 rounded-lg"
                        alt="Avatar"
                    />
                    <div>
                        <p class="font-semibold"><?= htmlspecialchars($username) ?></p>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars(ucfirst($role)) ?> Rekos</p>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-4xl mx-auto">
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
                    <div>
                        <label for="nama_barang" class="block mb-2 text-sm font-semibold text-slate-700">Nama Barang</label>
                        <input
                            type="text"
                            id="nama_barang"
                            name="nama_barang"
                            placeholder="Contoh: Kipas Angin Cosmos"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400"
                            required
                        >
                    </div>

                    <div>
                        <label for="deskripsi" class="block mb-2 text-sm font-semibold text-slate-700">Deskripsi</label>
                        <textarea
                            id="deskripsi"
                            name="deskripsi"
                            placeholder="Jelaskan kondisi barang, lama pemakaian, dan detail penting lainnya..."
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400 min-h-[140px]"
                            required
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="harga" class="block mb-2 text-sm font-semibold text-slate-700">Harga (Rp)</label>
                            <input
                                type="number"
                                id="harga"
                                name="harga"
                                min="1"
                                placeholder="Contoh: 50000"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                required
                            >
                        </div>

                        <div>
                            <label for="jumlah" class="block mb-2 text-sm font-semibold text-slate-700">Jumlah Barang</label>
                            <input
                                type="number"
                                id="jumlah"
                                name="jumlah"
                                min="1"
                                placeholder="Contoh: 1"
                                class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400"
                                required
                            >
                        </div>
                    </div>

                    <div>
                        <label for="gambar" class="block mb-2 text-sm font-semibold text-slate-700">Upload Gambar Barang</label>
                        <input
                            type="file"
                            id="gambar"
                            name="gambar"
                            accept=".jpg,.jpeg,.png,.webp"
                            class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-700 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:text-white hover:file:bg-blue-700"
                            required
                        >
                        <p class="mt-2 text-xs text-slate-500">Format: JPG, JPEG, PNG, WEBP. Maksimal 2 MB.</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <button
                            type="submit"
                            class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-700 transition">
                            Upload Barang
                        </button>

                        <a
                            href="barang_saya.php"
                            class="rounded-xl bg-slate-200 px-6 py-3 font-semibold text-slate-700 hover:bg-slate-300 transition text-center">
                            Lihat Barang Saya
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>

</body>
</html>