<?php
session_start();
require 'koneksi.php';

// 1. Pastikan user login dan ada ID barang yang dikirim
if (!isset($_SESSION['id_user']) || !isset($_GET['id'])) {
    header("Location: barang_saya.php");
    exit();
}

$id_barang = intval($_GET['id']);
$id_penjual = $_SESSION['id_user'];

// 2. Mulai transaksi database
mysqli_begin_transaction($conn);

try {
    // A. Ubah status barang menjadi 'terjual'
    // Pastikan juga bahwa barang ini memang milik penjual yang sedang login
    $query_barang = "UPDATE tbl_barang SET status_barang = 'terjual' WHERE id_barang = ? AND id_user = ?";
    $stmt1 = mysqli_prepare($conn, $query_barang);
    mysqli_stmt_bind_param($stmt1, "ii", $id_barang, $id_penjual);
    mysqli_stmt_execute($stmt1);

    // B. Ubah status di tbl_transaksi dari 'Pending' menjadi 'Selesai'
    // Kita mencari transaksi terakhir untuk barang ini yang statusnya masih Pending
    $query_transaksi = "UPDATE tbl_transaksi SET status = 'Selesai' 
                        WHERE id_barang = ? AND status = 'Pending' 
                        ORDER BY id_transaksi DESC LIMIT 1";
    $stmt2 = mysqli_prepare($conn, $query_transaksi);
    mysqli_stmt_bind_param($stmt2, "i", $id_barang);
    mysqli_stmt_execute($stmt2);

    // C. Jika semua query berhasil, simpan perubahan secara permanen
    mysqli_commit($conn);
    
    header("Location: barang_saya.php?status=sukses");

} catch (Exception $e) {
    // D. Jika ada error, batalkan semua perubahan yang sempat dilakukan
    mysqli_rollback($conn);
    header("Location: barang_saya.php?status=gagal");
}

exit();
?>