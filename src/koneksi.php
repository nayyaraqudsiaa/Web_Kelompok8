<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";
$db   = "db_rekos";
$port = 3306;       
$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>