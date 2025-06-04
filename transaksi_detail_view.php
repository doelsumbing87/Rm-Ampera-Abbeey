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
$main_transaksi = null;
$detail_items = [];

// Get the main transaction ID from the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID Transaksi utama tidak valid atau tidak ditemukan.";
    header("Location: transaksi_list.php");
    exit();
}

$id_transaksi_utama = intval($_GET['id']);

// Fetch main transaction details
$sql_main_transaksi = "SELECT id, tanggal_transaksi, total_keseluruhan, uang_dibayar, kembalian 
                       FROM transaksi 
                       WHERE id = ?";
$stmt_main_transaksi = $conn->prepare($sql_main_transaksi);
if ($stmt_main_transaksi) {
    $stmt_main_transaksi->bind_param("i", $id_transaksi_utama);
    $stmt_main_transaksi->execute();
    $result_main_transaksi = $stmt_main_transaksi->get_result();
    if ($result_main_transaksi->num_rows > 0) {
        $main_transaksi = $result_main_transaksi->fetch_assoc();
    } else {
        $_SESSION['error_message'] = "Transaksi utama dengan ID " . htmlspecialchars($id_transaksi_utama) . " tidak ditemukan.";
        header("Location: transaksi_list.php");
        exit();
    }
    $stmt_main_transaksi->close();
} else {
    $_SESSION['error_message'] = "Gagal menyiapkan query transaksi utama: " . $conn->error;
    header("Location: transaksi_list.php");
    exit();
}

// Fetch detail items for this main transaction
$sql_detail_items = "SELECT dt.id, dt.menu_id, dt.jumlah, dt.harga_satuan, dt.total_harga_item, m.nama AS menu_nama 
                     FROM detail_transaksi dt
                     JOIN menu m ON dt.menu_id = m.id
                     WHERE dt.transaksi_id = ?";
$stmt_detail_items = $conn->prepare($sql_detail_items);
if ($stmt_detail_items) {
    $stmt_detail_items->bind_param("i", $id_transaksi_utama);
    $stmt_detail_items->execute();
    $result_detail_items = $stmt_detail_items->get_result();
    if ($result_detail_items->num_rows > 0) {
        while($row = $result_detail_items->fetch_assoc()) {
            $detail_items[] = $row;
        }
    }
    $stmt_detail_items->close();
} else {
    $error_message = "Gagal mengambil detail item transaksi: " . $conn->error;
}

$conn->close();

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Detail Transaksi - RM Ampera Abbeey</title>
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
            <h1 class="mb-4">Detail Transaksi #<?= htmlspecialchars($id_transaksi_utama) ?></h1>

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

            <?php if ($main_transaksi): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0 text-white"><i class="fas fa-info-circle me-2"></i> Informasi Utama Transaksi</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID Transaksi:</strong> <?= htmlspecialchars($main_transaksi['id']) ?></p>
                            <p><strong>Tanggal:</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($main_transaksi['tanggal_transaksi']))) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Keseluruhan:</strong> Rp <?= number_format($main_transaksi['total_keseluruhan'], 0, ',', '.') ?></p>
                            <p><strong>Uang Dibayar:</strong> Rp <?= number_format($main_transaksi['uang_dibayar'], 0, ',', '.') ?></p>
                            <p><strong>Kembalian:</strong> Rp <?= number_format($main_transaksi['kembalian'], 0, ',', '.') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0 text-white"><i class="fas fa-list-alt me-2"></i> Item-item Transaksi</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-end mb-3">
                        </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID Item</th>
                                    <th>Menu</th>
                                    <th>Jumlah</th>
                                    <th>Harga Satuan</th>
                                    <th>Total Harga Item</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($detail_items)): ?>
                                    <?php foreach ($detail_items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['id']) ?></td>
                                        <td><?= htmlspecialchars($item['menu_nama']) ?></td>
                                        <td><?= htmlspecialchars($item['jumlah']) ?></td>
                                        <td>Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($item['total_harga_item'], 0, ',', '.') ?></td>
                                        <td>
                                            <a href="edit_transaksi.php?detail_id=<?= htmlspecialchars($item['id']) ?>" class="btn btn-sm btn-warning me-2"><i class="fas fa-edit"></i> Edit</a>
                                            <a href="delete_transaksi_item.php?detail_id=<?= htmlspecialchars($item['id']) ?>&transaksi_id=<?= htmlspecialchars($id_transaksi_utama) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus item ini dari transaksi?');"><i class="fas fa-trash-alt"></i> Hapus</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada item detail untuk transaksi ini.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-info" role="alert">
                    Transaksi tidak ditemukan.
                </div>
            <?php endif; ?>

            <div class="mt-4">
                <a href="transaksi_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Transaksi Utama</a>
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