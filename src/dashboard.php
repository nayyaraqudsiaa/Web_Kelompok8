<?php
session_start();
include 'koneksi.php';

$session_timeout = 600;

if (!isset($_SESSION['username']) || !isset($_SESSION['status'])) {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: login.php?expired=1");
    exit();
}

$_SESSION['last_activity'] = time();

$username = $_SESSION['username'];
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Member';

// Ambil keyword pencarian
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';
$items = []; // Array utama untuk menampung data yang akan ditampilkan

// Data dummy fallback (cadangan jika DB kosong)
$dummy_barang = [
    ['id_barang' => 1, 'nama_barang' => 'Kipas Angin',        'deskripsi' => 'Kipas angin bekas layak pakai, merk Cosmos',         'harga' => 50000, 'jumlah' => 2, 'gambar' => 'kipas-angin.jpeg'],
    ['id_barang' => 2, 'nama_barang' => 'Meja Belajar Lipat', 'deskripsi' => 'Meja belajar lipat portable, kondisi bagus',          'harga' => 35000, 'jumlah' => 1, 'gambar' => 'meja-belajar-lipat.jpg'],
    ['id_barang' => 3, 'nama_barang' => 'Rice Cooker',        'deskripsi' => 'Rice cooker mini 0.5L, masih berfungsi normal',       'harga' => 65000, 'jumlah' => 1, 'gambar' => 'rice-cooker.jpeg'],
    ['id_barang' => 4, 'nama_barang' => 'Lampu Belajar',      'deskripsi' => 'Lampu meja LED, hemat listrik',                       'harga' => 25000, 'jumlah' => 3, 'gambar' => ''],
    ['id_barang' => 5, 'nama_barang' => 'Dispenser Mini',      'deskripsi' => 'Dispenser kecil cocok untuk kamar kos',               'harga' => 45000, 'jumlah' => 1, 'gambar' => ''],
    ['id_barang' => 6, 'nama_barang' => 'Rak Buku',            'deskripsi' => 'Rak buku 3 susun, bahan kayu ringan',                 'harga' => 40000, 'jumlah' => 2, 'gambar' => ''],
    ['id_barang' => 7, 'nama_barang' => 'Setrika',             'deskripsi' => 'Setrika listrik bekas, panas merata',                 'harga' => 30000, 'jumlah' => 1, 'gambar' => ''],
    ['id_barang' => 8, 'nama_barang' => 'Cermin Dinding',      'deskripsi' => 'Cermin oval bingkai putih, ukuran sedang',            'harga' => 20000, 'jumlah' => 2, 'gambar' => ''],
];

if ($keyword !== '') {
    // 1. JIKA ADA PENCARIAN (Berdasarkan Keyword)
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

    if ($result && mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $items[] = $row;
        }
    } else {
        // Fallback pencarian ke data dummy jika di DB tidak ketemu
        $keyword_lower = strtolower($keyword);
        foreach ($dummy_barang as $item) {
            if (
                str_contains(strtolower($item['nama_barang']), $keyword_lower) ||
                str_contains(strtolower($item['deskripsi']), $keyword_lower)
            ) {
                $items[] = $item;
            }
        }
    }
} else {
    // 2. JIKA TIDAK ADA PENCARIAN (Tampilkan Semua Barang dari Semua User)
    $query = mysqli_query($conn, "SELECT * FROM tbl_barang ORDER BY id_barang DESC");
    
    if ($query && mysqli_num_rows($query) > 0) {
        // Ambil semua data dari database
        while ($row = mysqli_fetch_assoc($query)) {
            $items[] = $row;
        }
    } else {
        // Jika database benar-benar kosong, gunakan data dummy agar tampilan tidak kosong
        $items = $dummy_barang;
    }
}
?>
<!doctype html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard - Rekos</title>
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
        .glass:hover { background: rgba(255,255,255,0.12); transition: 0.2s; }
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
                <i class="fas fa-home text-blue-400"></i> Dashboard
            </a>
            <a href="barang_saya.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                <i class="fas fa-box text-blue-400"></i> Barang Saya
            </a>
            <a href="jual_barang.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                <i class="fas fa-plus-circle text-blue-400"></i> Jual Barang
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
                <?php if (!empty($items)): ?>
                    <?php foreach ($items as $row): ?>
                        <div class="bg-white rounded-2xl shadow-md overflow-hidden flex flex-col hover:shadow-lg transition duration-200">
                            <?php 
                                // Cek apakah gambar berasal dari dummy atau upload user
                                $gambarPath = 'https://placehold.co/400x250?text=No+Image';
                                if (!empty($row['gambar'])) {
                                    if (file_exists('../assets/uploads/' . $row['gambar'])) {
                                        $gambarPath = '../assets/uploads/' . htmlspecialchars($row['gambar']);
                                    } else {
                                        $gambarPath = '../assets/img/' . htmlspecialchars($row['gambar']);
                                    }
                                }
                            ?>
                            <img src="<?= $gambarPath ?>" class="w-full h-48 object-cover" alt="<?= htmlspecialchars($row['nama_barang']) ?>">
                            
                            <div class="p-4 flex flex-col flex-grow">
                                <h3 class="text-lg font-semibold text-slate-800 line-clamp-1"><?= htmlspecialchars($row['nama_barang']) ?></h3>
                                <p class="text-blue-600 text-xl font-bold mt-1 mb-2">Rp <?= number_format($row['harga'], 0, ',', '.') ?></p>
                                
                                <div class="flex-grow">
                                    <p class="text-sm text-slate-500 mt-2 line-clamp-2"><?= htmlspecialchars($row['deskripsi']) ?></p>
                                    <p class="text-sm text-slate-500 font-medium mt-2">Stok: <?= (int)$row['jumlah'] ?> pcs</p>
                                </div>
                                
                                <a href="detail_barang.php?id=<?= (int)$row['id_barang'] ?>"
                                   class="mt-4 block w-full text-center bg-blue-600 text-white py-2 rounded-xl hover:bg-blue-700 transition font-medium shadow-sm">
                                    Lihat Detail
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full bg-white rounded-2xl shadow-md p-10 text-center">
                        <i class="fas fa-box-open text-5xl text-slate-300 mb-4"></i>
                        <h3 class="text-xl font-bold text-slate-800 mb-1">Barang Kosong</h3>
                        <p class="text-slate-500">Belum ada barang yang tersedia saat ini atau kata kunci tidak ditemukan.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

</body>
</html>