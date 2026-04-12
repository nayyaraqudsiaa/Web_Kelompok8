<?php
session_start();
include "koneksi.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username   = trim($_POST['username']);
    $email      = trim($_POST['email']);
    $role       = $_POST['role'] ?? '';
    $alamat     = trim($_POST['alamat']);
    $no_telp    = trim($_POST['no_telp']);
    $password   = trim($_POST['password']);
    $konfirmasi = trim($_POST['konfirmasi_password']);

    if ($username == "" || $email == "" || $role == "" || $alamat == "" || $no_telp == "" || $password == "" || $konfirmasi == "") {
        $error = "Semua kolom wajib diisi!";
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter.";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak sama.";
    } else {
        $cek = mysqli_query($conn, "SELECT * FROM tbl_user WHERE username='$username' OR email='$email'");

        if ($cek && mysqli_num_rows($cek) > 0) {
            $error = "Username atau email sudah terdaftar!";
        } else {
            $query = mysqli_query($conn, "INSERT INTO tbl_user (username, email, password, role, alamat, no_telp)
                                          VALUES ('$username', '$email', '$password', '$role', '$alamat', '$no_telp')");

            if ($query) {
                $success = "Registrasi berhasil! Silakan login.";
            } else {
                $error = "Registrasi gagal: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun</title>
    <link href="./output.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen py-8">

<div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-md">
    <h2 class="text-2xl font-bold text-center mb-6">Daftar Akun</h2>

    <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 border border-red-300 text-sm text-center">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 border border-green-300 text-sm text-center">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" onsubmit="return validateForm()">
        <div class="mb-4">
            <label for="username" class="block mb-2 font-medium">Username</label>
            <input 
                type="text" 
                id="username"
                name="username"
                placeholder="Masukkan username"
                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
                value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
            >
        </div>

        <div class="mb-4">
            <label for="email" class="block mb-2 font-medium">Email</label>
            <input 
                type="email" 
                id="email"
                name="email"
                placeholder="Masukkan email"
                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
                value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
            >
        </div>

        <div class="mb-4">
            <label class="block mb-2 font-medium">Daftar sebagai</label>
            <div class="flex gap-6">
                <label class="flex items-center gap-2">
                    <input 
                        type="radio" 
                        name="role" 
                        value="pembeli" 
                        required
                        <?= (isset($_POST['role']) && $_POST['role'] == 'pembeli') ? 'checked' : '' ?>
                    >
                    Pembeli
                    <label class="flex items-center gap-2">
                </label>

                <label class="flex items-center gap-2">
                    <input 
                        type="radio" 
                        name="role" 
                        value="penjual"
                        <?= (isset($_POST['role']) && $_POST['role'] == 'penjual') ? 'checked' : '' ?>
                    >
                    Penjual
                </label>
            </div>
        </div>

        <div class="mb-4">
            <label for="alamat" class="block mb-2 font-medium">Alamat</label>
            <input 
                type="text" 
                id="alamat"
                name="alamat"
                placeholder="Masukkan alamat"
                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
                value="<?= isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : '' ?>"
            >
        </div>

        <div class="mb-4">
            <label for="no_telp" class="block mb-2 font-medium">No Telepon</label>
            <input 
                type="text" 
                id="no_telp"
                name="no_telp"
                placeholder="Masukkan no telepon"
                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
                value="<?= isset($_POST['no_telp']) ? htmlspecialchars($_POST['no_telp']) : '' ?>"
            >
        </div>

        <div class="mb-2">
            <label for="password" class="block mb-2 font-medium">Password</label>
            <input 
                type="password" 
                id="password"
                name="password"
                placeholder="Masukkan password"
                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
                minlength="8"
                oninput="checkPasswordHint()"
            >
        </div>

        <p id="passwordHint" class="mb-4 text-sm text-gray-500">
            Password minimal 8 karakter.
        </p>

        <div class="mb-2">
            <label for="konfirmasi_password" class="block mb-2 font-medium">Konfirmasi Password</label>
            <input 
                type="password" 
                id="konfirmasi_password"
                name="konfirmasi_password"
                placeholder="Ulangi password"
                class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
                oninput="checkPasswordMatch()"
            >
        </div>

        <p id="matchHint" class="mb-6 text-sm text-gray-500 min-h-[20px]"></p>

        <button 
            type="submit"
            name="register"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition"
        >
            Daftar
        </button>
    </form>

    <div class="text-center mt-4 text-sm">
        Sudah punya akun?
        <a href="login.php" class="text-blue-600 hover:underline">Login di sini</a>
    </div>
</div>

<script>
function checkPasswordHint() {
    const password = document.getElementById('password').value;
    const hint = document.getElementById('passwordHint');

    if (password.length === 0) {
        hint.textContent = 'Password minimal 8 karakter.';
        hint.className = 'mb-4 text-sm text-gray-500';
    } else if (password.length < 8) {
        hint.textContent = 'Password masih kurang dari 8 karakter.';
        hint.className = 'mb-4 text-sm text-red-500';
    } else {
        hint.textContent = 'Password sudah memenuhi minimal 8 karakter.';
        hint.className = 'mb-4 text-sm text-green-600';
    }
}

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const konfirmasi = document.getElementById('konfirmasi_password').value;
    const hint = document.getElementById('matchHint');

    if (konfirmasi.length === 0) {
        hint.textContent = '';
        hint.className = 'mb-6 text-sm text-gray-500 min-h-[20px]';
    } else if (password !== konfirmasi) {
        hint.textContent = 'Konfirmasi password belum cocok.';
        hint.className = 'mb-6 text-sm text-red-500 min-h-[20px]';
    } else {
        hint.textContent = 'Konfirmasi password cocok.';
        hint.className = 'mb-6 text-sm text-green-600 min-h-[20px]';
    }
}

function validateForm() {
    const password = document.getElementById('password').value;
    const konfirmasi = document.getElementById('konfirmasi_password').value;

    if (password.length < 8) {
        alert('Password minimal 8 karakter.');
        return false;
    }

    if (password !== konfirmasi) {
        alert('Konfirmasi password tidak sama.');
        return false;
    }

    return true;
}
</script>

</body>
</html>