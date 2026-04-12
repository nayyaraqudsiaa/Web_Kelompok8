<?php
session_start();
require 'koneksi.php';

// cek login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// hanya penjual yang boleh akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'penjual') {
    header("Location: jual_barang.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// ambil hanya barang milik user yang login
$stmt = $conn->prepare("SELECT * FROM tbl_barang WHERE id_user = ? ORDER BY id_barang DESC");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang Saya - Rekos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc; 
            margin: 0;
            padding-bottom: 40px;
        }

        .grid-container {
            max-width: 64rem; 
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); 
            gap: 24px; 
        }

        .card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease; 
            border: 1px solid #e2e8f0;
            cursor: pointer; /* Mengubah kursor jadi tangan saat diarahkan */
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .card img {
            width: 100%;
            height: 180px; 
            object-fit: cover;
            display: block;
        }

        .card-content {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex-grow: 1; 
        }

        .card h3 {
            margin: 0 0 8px;
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 600;
            line-height: 1.4;
        }

        .harga {
            color: #2563eb;
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .kosong {
            max-width: 64rem;
            margin: 0 auto;
            background: white;
            padding: 40px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: center;
            color: #64748b;
        }
    </style>
</head>
<body>

    <nav class="bg-blue-800 p-4 text-white mb-8 shadow-md">
        <div class="max-w-5xl mx-auto flex justify-between items-center">
            <a href="dashboard.php" class="hover:text-blue-200 transition"><i class="fas fa-arrow-left mr-2"></i> Kembali</a>
            <span class="font-bold text-lg">Barang Saya</span>
            <a href="jual_barang.php" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm font-semibold transition shadow-sm">
                <i class="fas fa-plus mr-1"></i> Jual
            </a>
        </div>
    </nav>

    <?php if ($result->num_rows > 0): ?>
        <div class="grid-container">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card" 
                     onclick="bukaModal(this)"
                     data-nama="<?= htmlspecialchars($row['nama_barang']) ?>"
                     data-harga="Rp <?= number_format($row['harga'], 0, ',', '.') ?>"
                     data-stok="<?= htmlspecialchars($row['jumlah']) ?>"
                     data-deskripsi="<?= htmlspecialchars($row['deskripsi']) ?>"
                     data-gambar="../assets/uploads/<?= htmlspecialchars($row['gambar']) ?>">
                     
                    <img src="../assets/uploads/<?= htmlspecialchars($row['gambar']) ?>" alt="Gambar Barang">
                    <div class="card-content">
                        <h3><?= htmlspecialchars($row['nama_barang']) ?></h3>
                        <div class="harga">Rp <?= number_format($row['harga'], 0, ',', '.') ?></div>
                        
                        <div class="flex-grow">
                            <p class="text-sm text-slate-600 mb-2"><strong>Stok:</strong> <?= htmlspecialchars($row['jumlah']) ?> pcs</p>
                            <p class="text-sm text-slate-500 line-clamp-2 leading-relaxed"><?= htmlspecialchars($row['deskripsi']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="kosong border border-slate-200">
            <i class="fas fa-box-open text-5xl mb-4 text-slate-300"></i>
            <p class="text-lg">Belum ada barang yang kamu upload.</p>
            <p class="text-sm mt-2">Mulai jualan barang kos bekasmu sekarang!</p>
        </div>
    <?php endif; ?>

    <div id="modalDetail" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4 opacity-0 transition-opacity duration-300">
        <div id="modalContent" class="bg-white rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl transform scale-95 transition-transform duration-300">
            
            <div class="flex justify-between items-center p-4 border-b border-slate-100">
                <h3 class="font-bold text-lg text-slate-800">Detail Barang</h3>
                <button onclick="tutupModal()" class="text-slate-400 hover:text-red-500 transition w-8 h-8 rounded-full hover:bg-red-50 flex items-center justify-center">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div class="p-0 overflow-y-auto max-h-[75vh]">
                <img id="modalGambar" src="" alt="Gambar Barang" class="w-full h-64 object-cover">
                
                <div class="p-6">
                    <h2 id="modalNama" class="text-2xl font-bold text-slate-800 mb-2">Nama Barang</h2>
                    <div id="modalHarga" class="text-blue-600 font-bold text-2xl mb-4">Rp 0</div>
                    
                    <div class="mb-5">
                        <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full border border-blue-200">
                            Stok: <span id="modalStok">0</span> pcs
                        </span>
                    </div>
                    
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                        <h4 class="font-semibold text-sm text-slate-700 mb-2"><i class="fas fa-info-circle mr-1"></i> Deskripsi:</h4>
                        <p id="modalDeskripsi" class="text-slate-600 text-sm whitespace-pre-line leading-relaxed"></p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <script>
        function bukaModal(element) {
            const modal = document.getElementById('modalDetail');
            const modalContent = document.getElementById('modalContent');

            // Ambil data dari atribut card yang di-klik lalu masukkan ke dalam modal
            document.getElementById('modalGambar').src = element.getAttribute('data-gambar');
            document.getElementById('modalNama').textContent = element.getAttribute('data-nama');
            document.getElementById('modalHarga').textContent = element.getAttribute('data-harga');
            document.getElementById('modalStok').textContent = element.getAttribute('data-stok');
            document.getElementById('modalDeskripsi').textContent = element.getAttribute('data-deskripsi');

            // Tampilkan modal
            modal.classList.remove('hidden');
            
            // Sedikit delay agar animasi munculnya terlihat smooth
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);
            
            // Mencegah scroll pada halaman di belakangnya
            document.body.style.overflow = 'hidden';
        }

        function tutupModal() {
            const modal = document.getElementById('modalDetail');
            const modalContent = document.getElementById('modalContent');

            // Jalankan animasi menghilang
            modal.classList.add('opacity-0');
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');

            // Sembunyikan setelah animasi selesai (300ms)
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto'; // Kembalikan fungsi scroll
            }, 300);
        }

        // Fitur tambahan: Tutup modal jika user mengklik area gelap di luar kotak modal
        document.getElementById('modalDetail').addEventListener('click', function(e) {
            if (e.target === this) {
                tutupModal();
            }
        });
    </script>

</body>
</html>