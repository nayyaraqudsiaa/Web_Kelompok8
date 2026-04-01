<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($username) && !empty($password)) {
        // simpan session
        $_SESSION['username'] = $username;

        // simpan cookie 1 hari
        setcookie("username", $username, time() + 86400, "/");

        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Semua kolom harus diisi!";
    }
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

    <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 text-sm">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-4">
            <label for="loginName" class="block mb-2 font-medium">Nama Pengguna</label>
            <input 
                type="text" 
                id="loginName"
                name="username"
                placeholder="Masukkan nama Anda"
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
        <a href="register.html" class="text-blue-600 hover:underline">
            Daftar di sini
        </a>
    </div>
</div>

</body>
</html>