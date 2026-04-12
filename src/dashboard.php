<?php
session_start();
include "koneksi.php";

$session_timeout = 600; // 10 menit

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
// Mengambil data role dari session (jika kosong, gunakan fallback 'Member')
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'Member';
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

            <?php if ($role == 'penjual'): ?>
                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                    <i class="fas fa-box text-blue-400"></i>
                    Barang Saya
                </a>

                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                    <i class="fas fa-plus-circle text-blue-400"></i>
                    Jual Barang
                </a>

        <a href="profil.php" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
          <i class="fas fa-user text-blue-400"></i>
          Profil
        </a>
      </nav>

                <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
                    <i class="fas fa-receipt text-blue-400"></i>
                    Pesanan Saya
                </a>
            <?php endif; ?>

            <a href="#" class="flex items-center gap-3 p-3 rounded-xl hover:bg-white/10">
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
        </header>

        <?php if ($role == 'penjual'): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 max-w-5xl mx-auto">
                <div class="bg-blue-800 rounded-2xl p-6 min-h-[100px] text-white shadow">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-500/20 rounded-xl">
                            <i class="fas fa-box-open text-xl text-blue-400"></i>
                        </div>

                        <div>
                            <p class="text-sm text-slate-400">Barang Terjual</p>
                            <p class="text-2xl font-bold">12</p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-800 rounded-2xl p-6 min-h-[100px] text-white shadow">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-500/20 rounded-xl">
                            <i class="fas fa-shopping-cart text-xl text-blue-400"></i>
                        </div>

                        <div>
                            <p class="text-sm text-slate-400">Total Penjualan</p>
                            <p class="text-2xl font-bold">Rp 2.450.000</p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-800 rounded-2xl p-6 min-h-[100px] text-white shadow">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-500/20 rounded-xl">
                            <i class="fas fa-users text-xl text-blue-400"></i>
                        </div>

                        <div>
                            <p class="text-sm text-slate-400">Pembeli</p>
                            <p class="text-2xl font-bold">23</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8 max-w-5xl mx-auto">
                <div class="bg-blue-800 rounded-2xl p-6 min-h-[100px] text-white shadow">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-500/20 rounded-xl">
                            <i class="fas fa-store text-xl text-blue-400"></i>
                        </div>

                        <div>
                            <p class="text-sm text-slate-400">Produk Tersedia</p>
                            <p class="text-2xl font-bold">45</p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-800 rounded-2xl p-6 min-h-[100px] text-white shadow">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-500/20 rounded-xl">
                            <i class="fas fa-cart-shopping text-xl text-blue-400"></i>
                        </div>

                        <div>
                            <p class="text-sm text-slate-400">Pesanan Saya</p>
                            <p class="text-2xl font-bold">3</p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-800 rounded-2xl p-6 min-h-[100px] text-white shadow">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-500/20 rounded-xl">
                            <i class="fas fa-heart text-xl text-blue-400"></i>
                        </div>

                        <div>
                            <p class="text-sm text-slate-400">Wishlist</p>
                            <p class="text-2xl font-bold">7</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>