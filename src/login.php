<?php
session_start();
require 'koneksi.php'; // Wajib menyertakan koneksi database

// Atur durasi session 10 menit
$session_timeout = 600; // 600 detik = 10 menit

// Kalau sudah login dan session masih aktif, langsung ke dashboard
if (isset($_SESSION['username'])) {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
        session_unset();
        session_destroy();
        // Redirect ke login dengan pesan expired
        header("Location: login.php?expired=1");
        exit();
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

    // Menggunakan prepared statement untuk mencegah SQL Injection
    $stmt = $conn->prepare("SELECT id_user, username, password, role FROM tbl_user WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Cek apakah username ditemukan
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verifikasi kecocokan password input dengan hash di database
        if (password_verify($password, $user['password'])) {
            // Set session data
            $_SESSION['id_user']       = $user['id_user'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['status']        = "login";
            $_SESSION['last_activity'] = time();

            // Cookie opsional, ikut 10 menit juga
            setcookie("username", $username, time() + $session_timeout, "/");

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Password yang Anda masukkan salah!";
        }
    } else {
        $error = "Nama pengguna tidak ditemukan!";
    }
    
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="./output.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

<div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md">
    <h2 class="text-2xl font-bold text-center mb-6">Login</h2>

    <?php if (isset($_GET['expired'])): ?>
    <div class="mb-4 p-3 rounded-lg bg-yellow-100 text-yellow-700 border border-yellow-300 text-sm text-center">
        Sesi Anda telah berakhir. Silakan login kembali.
    </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 border border-red-300 text-sm text-center">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-4">
            <label for="loginName" class="block mb-2 font-medium">Nama Pengguna</label>
            <input 
                type="text" 
                id="loginName"
                name="username"
                placeholder="Masukkan nama pengguna Anda"
                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
            >
        </div>

        <div class="mb-6">
            <label for="loginPassword" class="block mb-2 font-medium">Password</label>
            <input 
                type="password" 
                id="loginPassword"
                name="password"
                placeholder="Masukkan password"
                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
            >
        </div>

        <button 
            type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition"
        >
            Masuk
        </button>
    </form>

    <div class="text-center mt-4 text-sm">
        Belum punya akun?
        <a href="register.php" class="text-blue-600 hover:underline">Daftar di sini</a>
    </div>
</div>

</body>
</html>