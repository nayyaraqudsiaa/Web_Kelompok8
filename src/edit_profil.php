<?php
session_start();
require 'koneksi.php';

// Proteksi Halaman
if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// 1. Ambil data user saat ini untuk ditampilkan di form
$query = $conn->prepare("SELECT username, email, no_telp, alamat FROM tbl_user WHERE id_user = ?");
$query->bind_param("i", $id_user);
$query->execute();
$user_data = $query->get_result()->fetch_assoc();

// 2. Proses jika tombol Simpan ditekan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_username = $_POST['username'];
    $new_notelp   = $_POST['no_telp'];
    $new_alamat   = $_POST['alamat'];
    $new_password = $_POST['password'];

    // Cek apakah user mengisi password baru
    if (!empty($new_password)) {
        // Jika diisi, update semua data termasuk password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE tbl_user SET username = ?, no_telp = ?, alamat = ?, password = ? WHERE id_user = ?");
        $update_stmt->bind_param("ssssi", $new_username, $new_notelp, $new_alamat, $hashed_password, $id_user);
    } else {
        // Jika dikosongkan, update data tanpa menyentuh password
        $update_stmt = $conn->prepare("UPDATE tbl_user SET username = ?, no_telp = ?, alamat = ? WHERE id_user = ?");
        $update_stmt->bind_param("sssi", $new_username, $new_notelp, $new_alamat, $id_user);
    }

    if ($update_stmt->execute()) {
        // Update session username agar nama di pojok kanan atas dashboard ikut berubah
        $_SESSION['username'] = $new_username; 
        
        echo "<script>alert('Profil berhasil diperbarui!'); window.location='profil.php';</script>";
    } else {
        echo "<script>alert('Terjadi kesalahan saat memperbarui profil.');</script>";
    }
    $update_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Rekos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="bg-slate-50 min-h-screen pb-10">

    <nav class="bg-blue-800 p-4 text-white mb-6">
        <div class="max-w-2xl mx-auto flex justify-between items-center">
            <a href="profil.php" class="hover:text-blue-200 transition"><i class="fas fa-arrow-left mr-2"></i> Batal</a>
            <span class="font-bold">Edit Profil</span>
            <div class="w-16"></div> </div>
    </nav>

    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            
            <div class="bg-gradient-to-r from-blue-500 to-blue-400 py-8 px-6 text-center">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_data['username']) ?>&size=128&background=fff&color=3b82f6" 
                     alt="Avatar" 
                     class="w-24 h-24 rounded-full mx-auto border-4 border-white shadow-md mb-3">
                <h2 class="text-xl font-bold text-white"><?= htmlspecialchars($user_data['username']) ?></h2>
                <p class="text-blue-100 text-sm"><?= htmlspecialchars($user_data['email']) ?></p>
            </div>

            <form method="POST" action="" class="p-6 md:p-8 space-y-6">
                
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700 mb-2">Nama Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-slate-400"></i>
                        </div>
                        <input type="text" id="username" name="username" 
                               value="<?= htmlspecialchars($user_data['username']) ?>"
                               class="pl-10 w-full border border-slate-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" required>
                    </div>
                </div>

                <div>
                    <label for="no_telp" class="block text-sm font-medium text-slate-700 mb-2">Nomor Telepon / WhatsApp</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-phone text-slate-400"></i>
                        </div>
                        <input type="text" id="no_telp" name="no_telp" 
                               value="<?= htmlspecialchars($user_data['no_telp'] ?? '') ?>"
                               class="pl-10 w-full border border-slate-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" 
                               placeholder="Contoh: 08123456789">
                    </div>
                </div>

                <div>
                    <label for="alamat" class="block text-sm font-medium text-slate-700 mb-2">Alamat Lengkap</label>
                    <div class="relative">
                        <div class="absolute top-3 left-3 pointer-events-none">
                            <i class="fas fa-map-marker-alt text-slate-400"></i>
                        </div>
                        <textarea id="alamat" name="alamat" rows="3"
                                  class="pl-10 w-full border border-slate-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" 
                                  placeholder="Masukkan alamat kos atau tempat tinggal..."><?= htmlspecialchars($user_data['alamat'] ?? '') ?></textarea>
                    </div>
                </div>

                <hr class="border-slate-200">

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Password Baru <span class="text-slate-400 font-normal">(Opsional)</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-slate-400"></i>
                        </div>
                        <input type="password" id="password" name="password" 
                               class="pl-10 w-full border border-slate-300 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition" 
                               placeholder="Isi jika ingin mengganti password">
                    </div>
                    <p class="mt-1 text-xs text-slate-500">Biarkan kosong jika Anda tidak ingin mengubah password saat ini.</p>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 flex justify-center items-center gap-2">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>

            </form>
        </div>
    </div>

</body>
</html>