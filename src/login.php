<?php
session_start();
include "koneksi.php";

$session_timeout = 600; // 10 menit

if (isset($_SESSION['username'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
        session_unset();
        session_destroy();
    } else {
        $_SESSION['last_activity'] = time();
        header("Location: dashboard.php");
        exit();
    }
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // 1. Cari dulu username-nya di database (jangan cari password-nya di sini)
    $query = mysqli_query($conn, "SELECT * FROM tbl_user WHERE username='$username'");

    if ($query && mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);

        // 2. Cocokkan password yang diketik dengan password acak di database
        if (password_verify($password, $data['password'])) {
            
            // --- JIKA PASSWORD COCOK, LOGIN SUKSES ---
            $_SESSION['id_user'] = $data['id_user']; // (Tambahan dari perbaikan sebelumnya)
            $_SESSION['username'] = $data['username'];
            $_SESSION['role'] = $data['role'];
            $_SESSION['status'] = "login";
            $_SESSION['last_activity'] = time();

            setcookie("username", $data['username'], time() + $session_timeout, "/");

            header("Location: dashboard.php");
            exit();
            
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Username tidak ditemukan!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Rekos</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-900 px-4 py-8">

    <div class="relative w-full max-w-6xl min-h-[680px] rounded-[28px] overflow-hidden shadow-2xl bg-cover bg-center"
         style="background-image: url('https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=1600&q=80');">

        <div class="absolute inset-0 bg-black/55"></div>

        <div class="relative z-10 grid md:grid-cols-2 min-h-[680px]">

            <div class="flex flex-col justify-center px-8 md:px-14 py-12 text-white bg-black/35 backdrop-blur-[2px]">
                <div class="max-w-md">
                    <p class="text-sm uppercase tracking-[0.25em] text-white/70 mb-4">
                        Marketplace Rekos
                    </p>

                    <h1 class="text-4xl md:text-5xl font-bold leading-tight mb-6">
                        Selamat Datang di Rekos
                    </h1>

                    <p class="text-white/85 text-base leading-8">
                        Platform yang dirancang untuk memfasilitasi jual beli barang kos bekas yang masih layak pakai, dengan tujuan membantu mahasiswa memperoleh kebutuhan kos dengan harga terjangkau serta mengurangi limbah barang yang masih dapat digunakan.
                    </p>

                    <div class="mt-8">
                        <a href="register.php"
                           class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-600 transition">
                            Daftar Sekarang
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-center px-6 py-10">
                <div class="w-full max-w-md rounded-[24px] bg-black/55 backdrop-blur-md border border-white/10 p-8 md:p-10 text-white shadow-xl">
                    <h2 class="text-3xl font-bold mb-8">Login</h2>

                    <?php if (isset($_GET['expired'])): ?>
                        <div class="mb-4 rounded-xl bg-yellow-500/15 border border-yellow-400/30 px-4 py-3 text-sm text-yellow-200">
                            Sesi Anda habis. Silakan login kembali.
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="mb-4 rounded-xl bg-red-500/15 border border-red-400/30 px-4 py-3 text-sm text-red-200">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" class="space-y-5">
                        <div>
                            <label for="loginName" class="block mb-2 text-sm text-white/80">Username</label>
                            <input
                                type="text"
                                id="loginName"
                                name="username"
                                placeholder="Masukkan username"
                                class="w-full border-0 border-b border-white/30 bg-transparent px-0 py-3 text-white placeholder:text-white/45 focus:outline-none focus:border-emerald-400"
                                required
                            >
                        </div>

                        <div>
                            <label for="loginPassword" class="block mb-2 text-sm text-white/80">Password</label>
                            <input
                                type="password"
                                id="loginPassword"
                                name="password"
                                placeholder="Masukkan password"
                                class="w-full border-0 border-b border-white/30 bg-transparent px-0 py-3 text-white placeholder:text-white/45 focus:outline-none focus:border-emerald-400"
                                required
                            >
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-xl bg-emerald-500 py-3 text-base font-semibold text-white hover:bg-emerald-600 transition mt-4">
                            Login
                        </button>
                    </form>

                    <p class="mt-6 text-sm text-white/70">
                        Belum punya akun?
                        <a href="register.php" class="text-emerald-400 hover:underline font-medium">Daftar di sini</a>
                    </p>
                </div>
            </div>

        </div>
    </div>

</body>
</html>