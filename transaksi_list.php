<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';

$success_message = "";
$error_message = "";

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

$sql = "SELECT id, tanggal_transaksi, total_keseluruhan, uang_dibayar, kembalian
        FROM transaksi
        ORDER BY tanggal_transaksi DESC";

$result = $conn->query($sql);

if (!$result) {
    $error_message = "Gagal mengambil data transaksi: " . $conn->error;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Daftar Transaksi Kasir - RM Ampera Abbeey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./assets/css/style.css" />
    <link rel="stylesheet" href="./assets/css/admin.css" />
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
                <li class="nav-item">
                    <a href="transaksi_list.php" class="nav-link active">
                        <i class="fas fa-cash-register me-2"></i> Daftar Transaksi
                    </a>
                </li>
                <li class="nav-item">
                    <a href="kasir.php" class="nav-link">
                        <i class="fas fa-calculator me-2"></i> Form Kasir
                    </a>
                </li>
            </ul>
        </div>

        <div class="content-area">
            <h1 class="mb-4">Daftar Transaksi Kasir</h1>

            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard</a>
                        <a href="kasir.php" class="btn btn-primary-custom"><i class="fas fa-plus-circle me-2"></i> Tambah Transaksi Baru</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID Transaksi</th>
                                    <th>Tanggal Transaksi</th>
                                    <th>Total Keseluruhan</th>
                                    <th>Uang Dibayar</th>
                                    <th>Kembalian</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()) : ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['id']) ?></td>
                                            <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($row['tanggal_transaksi']))) ?></td>
                                            <td>Rp <?= number_format($row['total_keseluruhan'], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($row['uang_dibayar'], 0, ',', '.') ?></td>
                                            <td>Rp <?= number_format($row['kembalian'], 0, ',', '.') ?></td>
                                            <td>
                                                <a href="cetak_struk.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-info me-2" target="_blank"><i class="fas fa-print"></i> Cetak Ulang</a>
                                                <a href="transaksi_detail_view.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-primary me-2"><i class="fas fa-eye"></i> Detail & Edit</a>
                                                <a href="hapus_transaksi.php?id=<?= htmlspecialchars($row['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus transaksi ini beserta detailnya?');"><i class="fas fa-trash-alt"></i> Hapus</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Belum ada data transaksi.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
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