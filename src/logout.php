<?php
session_start();
// Mengosongkan semua variabel session saat ini
session_unset();
// Menghancurkan session dari server
session_destroy();
// Menghapus cookie 'username' dengan mengatur waktu kedaluwarsa ke masa lalu
setcookie("username", "", time() - 3600, "/");
// Redirect kembali ke halaman login
header("Location: login.php");
exit();
?>