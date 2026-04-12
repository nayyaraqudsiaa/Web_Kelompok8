<?php
session_start();
include 'koneksi.php';

$session_timeout = 600; // 10 menit

// Cek apakah user sudah login
if (!isset($_SESSION['username']) || !isset($_SESSION['status'])) {
    header("Location: login.php");
    exit();
}

// Cek timeout session
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?expired=1");
    exit();
}

// Update aktivitas terakhir
$_SESSION['last_activity'] = time();

$username = $_SESSION['username'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Member';

// Ambil keyword pencarian
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$result = false;

// Jika ada keyword, cari di database
if ($keyword !== '') {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT * FROM tbl_barang 
         WHERE nama_barang LIKE ? OR deskripsi LIKE ?
         ORDER BY id_barang DESC"
    );

    $search = "%$keyword%";
    mysqli_stmt_bind_param($stmt, "ss", $search, $search);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
}
?>
<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Rekos</title>

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
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-xl bg-blue-500/20 border border-blue-400/30">
                <i class="fas fa-home text-blue-400"></i>
                Dashboard
            </a>

            <a href="barang_saya.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                <i class="fas fa-box text-blue-400"></i>
                Barang Saya
            </a>

            <a href="jual_barang.php" class="flex items-center gap-3 p-3 rounded-xl bg-blue-500/20 border border-blue-400/30">
                <i class="fas fa-plus-circle"></i>
                Jual Barang
            </a>

            <a href="profil.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
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
                    <h1 class="text-3xl font-bold">Dashboard</h1>
                    <p class="text-slate-300">Selamat datang kembali!</p>
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

        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800">Barang Tersedia</h2>
                    <p class="text-slate-500 text-sm">Temukan kebutuhan kos bekas yang masih layak pakai</p>
                </div>

                <form action="dashboard.php" method="GET" class="w-full md:w-80 flex gap-2">
                    <input
                        type="text"
                        name="q"
                        placeholder="Cari barang..."
                        value="<?= htmlspecialchars($keyword) ?>"
                        class="w-full rounded-xl border border-slate-300 px-4 py-3 text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    >
                    <button type="submit" class="bg-blue-600 text-white px-5 rounded-xl hover:bg-blue-700">
                        Cari
                    </button>
                </form>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">

                <?php if ($keyword !== ''): ?>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                                <img
                                    src="<?= !empty($row['gambar']) ? '../assets/img/' . htmlspecialchars($row['gambar']) : 'https://via.placeholder.com/400x250?text=No+Image' ?>"
                                    class="w-full h-48 object-cover"
                                    alt="<?= htmlspecialchars($row['nama_barang']) ?>"
                                >

                                <div class="p-4">
                                    <h3 class="text-lg font-semibold text-slate-800">
                                        <?= htmlspecialchars($row['nama_barang']) ?>
                                    </h3>

                                    <p class="text-blue-600 text-xl font-bold mt-1">
                                        Rp <?= number_format($row['harga'], 0, ',', '.') ?>
                                    </p>

                                    <p class="text-sm text-slate-500 mt-2">
                                        <?= htmlspecialchars($row['deskripsi']) ?>
                                    </p>

                                    <p class="text-sm text-slate-500">
                                        Stok: <?= (int)$row['jumlah'] ?>
                                    </p>

                                    <button class="mt-4 w-full bg-blue-600 text-white py-2 rounded-xl hover:bg-blue-700 transition">
                                        Lihat Detail
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-full bg-white rounded-2xl shadow-md p-6">
                            <p class="text-slate-600">Barang tidak ditemukan.</p>
                        </div>
                    <?php endif; ?>

                <?php else: ?>

                    <!-- PRODUK 1 -->
                    <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                        <img
                            src="../assets/img/meja-belajar-lipat.jpg"
                            class="w-full h-48 object-cover"
                            alt="Meja Belajar Lipat"
                        >

                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-slate-800">Meja Belajar Lipat</h3>
                            <p class="text-blue-600 text-xl font-bold mt-1">Rp 35.000</p>
                            <p class="text-sm text-slate-500 mt-2">Kondisi: Bekas layak pakai</p>
                            <p class="text-sm text-slate-500">Lokasi: Dekat kampus</p>

                            <button class="mt-4 w-full bg-blue-600 text-white py-2 rounded-xl hover:bg-blue-700 transition">
                                Lihat Detail
                            </button>
                        </div>
                    </div>

                    <!-- PRODUK 2 -->
                    <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                        <img
                            src="../assets/img/kipas-angin.jpeg"
                            class="w-full h-48 object-cover"
                            alt="Kipas Angin"
                        >

                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-slate-800">Kipas Angin</h3>
                            <p class="text-blue-600 text-xl font-bold mt-1">Rp 50.000</p>
                            <p class="text-sm text-slate-500 mt-2">Kondisi: Bekas layak pakai</p>
                            <p class="text-sm text-slate-500">Lokasi: Kos Putri Mawar</p>

                            <button class="mt-4 w-full bg-blue-600 text-white py-2 rounded-xl hover:bg-blue-700 transition">
                                Lihat Detail
                            </button>
                        </div>
                    </div>

                    <!-- PRODUK 3 -->
                    <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                        <img
                            src="../assets/img/rice-cooker.jpeg"
                            class="w-full h-48 object-cover"
                            alt="Rice Cooker"
                        >

                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-slate-800">Rice Cooker</h3>
                            <p class="text-blue-600 text-xl font-bold mt-1">Rp 65.000</p>
                            <p class="text-sm text-slate-500 mt-2">Kondisi: Bekas layak pakai</p>
                            <p class="text-sm text-slate-500">Lokasi: Area kampus</p>

                            <button class="mt-4 w-full bg-blue-600 text-white py-2 rounded-xl hover:bg-blue-700 transition">
                                Lihat Detail
                            </button>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>
    </main>

</body>
</html>