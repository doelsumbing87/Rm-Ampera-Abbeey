<?php
session_start();
// Pastikan admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php"); // Redirect ke login.php jika belum login
    exit();
}

require_once 'db_config.php'; // Menggunakan file koneksi database terpusat

date_default_timezone_set('Asia/Jakarta'); // Pastikan timezone sesuai

// Inisialisasi tanggal mulai dan tanggal selesai
// Jika parameter GET 'tgl_mulai' atau 'tgl_selesai' tidak ada,
// maka setel ke tanggal hari ini secara otomatis.
$today_date = date('Y-m-d');
$tgl_mulai = $_GET['tgl_mulai'] ?? $today_date;
$tgl_selesai = $_GET['tgl_selesai'] ?? $today_date;

// Inisialisasi array untuk menyimpan data laporan
$laporan_data = [];
$total_penjualan_periode = 0;
$error_message = ""; // Pastikan $error_message diinisialisasi

// Filter tanggal
$where_clause = "";
$bind_params = [];
$bind_types = "";

// Selalu terapkan filter tanggal, baik dari GET maupun default hari ini
if (strtotime($tgl_mulai) === false || strtotime($tgl_selesai) === false) {
    $error_message = "Format tanggal tidak valid.";
    // Reset tanggal agar form tidak menampilkan tanggal yang salah jika ada error format
    $tgl_mulai = '';
    $tgl_selesai = '';
} else {
    $where_clause = "WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?";
    $bind_params[] = $tgl_mulai;
    $bind_params[] = $tgl_selesai;
    $bind_types = "ss"; // s for string (date)
}

// Query untuk mengambil data laporan penjualan (detail per item)
// Query hanya dijalankan jika tidak ada error_message dari validasi tanggal
if (empty($error_message)) {
    $sql = "SELECT 
                dt.id AS detail_id,
                t.id AS transaksi_utama_id,
                t.tanggal_transaksi,
                m.nama AS menu_nama,
                dt.jumlah,
                dt.harga_satuan,
                dt.total_harga_item
            FROM 
                detail_transaksi dt
            JOIN 
                transaksi t ON dt.transaksi_id = t.id
            JOIN 
                menu m ON dt.menu_id = m.id
            $where_clause
            ORDER BY 
                t.tanggal_transaksi DESC, dt.id ASC";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        if (!empty($bind_params)) {
            $stmt->bind_param($bind_types, ...$bind_params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $laporan_data[] = $row;
                // Hitung total penjualan dari item yang difilter
                $total_penjualan_periode += $row['total_harga_item'];
            }
        }
        $stmt->close();
    } else {
        $error_message = "Gagal menyiapkan query laporan: " . $conn->error;
    }
} else {
    // Jika ada error_message dari validasi tanggal, set data laporan kosong
    $laporan_data = [];
    $total_penjualan_periode = 0;
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Laporan Penjualan - RM Ampera Abbeey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./assets/css/style.css" />
    <link rel="stylesheet" href="./assets/css/admin.css" />
    <style>
        /* Gaya tambahan untuk laporan */
        .report-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .report-header h2 {
            font-weight: bold;
            color: #333;
        }
        .report-summary {
            font-size: 1.2em;
            font-weight: bold;
            text-align: right;
            margin-top: 20px;
            padding-right: 15px;
        }
    </style>
</head>
<body>

    <header class="admin-header">
      <div class="container-fluid d-flex justify-content-between align-items-center px-4">
        <div class="logo">
          <a href="./dashboard.php" class="text-decoration-none">
            <i class="fa fa-utensils me-2"></i>
            <h1 class="mb-0 d-inline-block">Admin RM Ampera Abbeey</h1>
          </a>
        </div>
        <nav class="admin-nav">
          <ul class="d-flex mb-0 ps-0">
            <li class="list-unstyled">
              <a class="nav-link text-uppercase" href="./logout.php">Logout <i class="fas fa-sign-out-alt ms-2"></i></a>
            </li>
          </ul>
        </nav>
      </div>
    </header>

    <div class="main-wrapper-admin">
        <div class="sidebar d-flex flex-column p-3">
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-chart-line me-2"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="add_menu.php" class="nav-link">
                        <i class="fas fa-plus-circle me-2"></i> Tambah Menu
                    </a>
                </li>
                <li>
                    <a href="transaksi_list.php" class="nav-link">
                        <i class="fas fa-cash-register me-2"></i> Daftar Transaksi
                    </a>
                </li>
                <li>
                    <a href="kasir.php" class="nav-link">
                        <i class="fas fa-calculator me-2"></i> Form Kasir
                    </a>
                </li>
                 <li class="nav-item">
                    <a href="laporan_penjualan.php" class="nav-link active">
                        <i class="fas fa-file-invoice me-2"></i> Laporan Penjualan
                    </a>
                </li>
            </ul>
        </div>

        <div class="content-area">
            <h1 class="mb-4">Laporan Penjualan</h1>

            <?php if (isset($error_message) && $error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Filter Laporan</h5>
                    <form method="GET" action="" class="row g-3 align-items-end">
                        <div class="col-md-auto">
                            <label for="tgl_mulai" class="form-label">Dari Tanggal:</label>
                            <input type="date" name="tgl_mulai" id="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tgl_mulai) ?>" required>
                        </div>
                        <div class="col-md-auto">
                            <label for="tgl_selesai" class="form-label">Sampai Tanggal:</label>
                            <input type="date" name="tgl_selesai" id="tgl_selesai" class="form-control" value="<?= htmlspecialchars($tgl_selesai) ?>" required>
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary-custom"><i class="fas fa-filter me-2"></i> Filter</button>
                        </div>
                        <div class="col-md-auto">
                            <a href="laporan_penjualan.php" class="btn btn-secondary"><i class="fas fa-sync-alt me-2"></i> Reset Filter</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0 text-white"><i class="fas fa-list-alt me-2"></i> Data Penjualan</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID Detail</th>
                                    <th>ID Transaksi Utama</th>
                                    <th>Tanggal & Waktu</th>
                                    <th>Nama Menu</th>
                                    <th>Jumlah</th>
                                    <th>Harga Satuan (Rp)</th>
                                    <th>Total Harga Item (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($laporan_data)): ?>
                                    <?php foreach($laporan_data as $row) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['detail_id']) ?></td>
                                            <td><?= htmlspecialchars($row['transaksi_utama_id']) ?></td>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($row['tanggal_transaksi']))) ?></td>
                                            <td><?= htmlspecialchars($row['menu_nama']) ?></td>
                                            <td><?= htmlspecialchars($row['jumlah']) ?></td>
                                            <td><?= number_format($row['harga_satuan'], 0, ',', '.') ?></td>
                                            <td><?= number_format($row['total_harga_item'], 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data penjualan untuk periode yang dipilih.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="report-summary">
                        <p>Total Penjualan Periode: Rp <?= number_format($total_penjualan_periode, 0, ',', '.') ?></p>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <a href="export_excel_laporan.php?tgl_mulai=<?= urlencode($tgl_mulai) ?>&tgl_selesai=<?= urlencode($tgl_selesai) ?>" class="btn btn-success"><i class="fas fa-file-excel me-2"></i> Export ke Excel</a>
            </div>

        </div>
    </div>

    <footer class="admin-footer">
        <p class="mb-0">&copy; <span id="copyrightCurrentYearAdmin"></span> RM Ampera Abbeey Admin Panel. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <script>
      document.getElementById('copyrightCurrentYearAdmin').textContent = new Date().getFullYear();
    </script>
</body>
</html>