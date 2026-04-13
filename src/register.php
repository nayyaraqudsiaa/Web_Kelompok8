<?php
require 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email    = strtolower($_POST['email']);
    $password = $_POST['password'];
    
    $role     = 'pembeli'; 

    // Validasi email upn
    if (!str_ends_with($email, '@student.upnjatim.ac.id')) {
        echo "<script>alert('Registrasi gagal! Hanya email mahasiswa UPN Jatim (@student.upnjatim.ac.id) yang diizinkan.'); window.history.back();</script>";
        exit;
    }

    // Cek apakah email sudah terdaftar sebelumnya
    $cek_email = $conn->prepare("SELECT email FROM tbl_user WHERE email = ?");
    $cek_email->bind_param("s", $email);
    $cek_email->execute();
    $cek_email->store_result();
    
    if ($cek_email->num_rows > 0) {
        echo "<script>alert('Email sudah terdaftar! Silakan gunakan email lain.'); window.history.back();</script>";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Simpan db
        $stmt = $conn->prepare("INSERT INTO tbl_user (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);

        if ($stmt->execute()) {
            echo "<script>alert('Registrasi Berhasil! Silakan Login.'); window.location='login.php';</script>";
        } else {
            echo "<script>alert('Terjadi kesalahan koneksi database.');</script>";
        }
        $stmt->close();
    }
    $cek_email->close();
}
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Rekos</title>
    <link href="./output.css" rel="stylesheet"> 
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen py-10">

    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
        <h2 class="text-2xl font-bold text-center mb-6">Daftar Akun</h2>

        <form id="formRegister" method="POST" action="" onsubmit="return validateForm()">
            
            <div class="mb-4">
                <label for="regName" class="block mb-2 font-medium">Nama Lengkap</label>
                <input type="text" id="regName" name="username"
                    class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Hanya huruf dan spasi" required>
            </div>

            <div class="mb-4">
                <label for="regEmail" class="block mb-2 font-medium">Email Mahasiswa</label>
                <input type="email" id="regEmail" name="email"
                    class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="NPM@student.upnjatim.ac.id" required>
                <p class="text-xs text-gray-500 mt-1">*Wajib menggunakan email UPN Jatim</p>
            </div>

            <div class="mb-4">
                <label for="regPassword" class="block mb-2 font-medium">Password</label>
                <input type="password" id="regPassword" name="password"
                    class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Buat password" required>
            </div>

            <div class="mb-6">
                <label for="regKonfirmasiPassword" class="block mb-2 font-medium">Konfirmasi Password</label>
                <input type="password" id="regKonfirmasiPassword"
                    class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Ulangi password" required>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                Daftar
            </button>

            <div class="text-center mt-4 text-sm">
                Sudah punya akun?
                <a href="login.php" class="text-blue-600 hover:underline">
                    Login di sini
                </a>
            </div>

        </form>
    </div>

    <script>
    function validateForm() {
        const nameInput = document.getElementById('regName').value.trim();
        const emailInput = document.getElementById('regEmail').value.trim().toLowerCase();
        const passwordInput = document.getElementById('regPassword').value;
        const confirmInput = document.getElementById('regKonfirmasiPassword').value;

        // Validasi nama
        const nameRegex = /^[a-zA-Z\s]+$/;
        if (!nameRegex.test(nameInput)) {
            alert("Nama hanya bisa berisi huruf dan spasi!");
            return false;
        }

        // Validasi email upn
        if (!emailInput.endsWith('@student.upnjatim.ac.id')) {
            alert("Harap gunakan email mahasiswa UPN Jatim (@student.upnjatim.ac.id)!");
            return false;
        }

        // Validasi password
        const passwordRegex = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;
        if (!passwordRegex.test(passwordInput)) {
            alert("Password minimal 8 karakter dan harus mengandung kombinasi huruf dan angka!");
            return false;
        }

        // Validasi kecocokan password
        if (passwordInput !== confirmInput) {
            alert("Password dan Konfirmasi Password tidak sesuai!");
            return false;
        }
        return true;
    }
    </script>

</body>
</html>