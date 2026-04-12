<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "rekos";
$port = 3307; // penting karena MySQL kamu pakai 3307

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>