<?php
session_start();
// Pastikan admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';

$menu_items_by_category = [];
$categories_list = [ // Daftar kategori yang konsisten dengan add_menu.php dan edit_menu.php
    "Makanan",
    "Lauk Pauk",
    "Minuman",
    "Camilan",
];

// Ambil semua menu dan kelompokkan berdasarkan kategori
$sql_menu_all = "SELECT id, nama, harga, deskripsi, kategori FROM menu ORDER BY kategori ASC, nama ASC";
$result_menu_all = $conn->query($sql_menu_all);

if ($result_menu_all) {
    if ($result_menu_all->num_rows > 0) {
        while($row = $result_menu_all->fetch_assoc()) {
            // Inisialisasi array untuk kategori jika belum ada
            if (!isset($menu_items_by_category[$row['kategori']])) {
                $menu_items_by_category[$row['kategori']] = [];
            }
            $menu_items_by_category[$row['kategori']][] = $row;
        }
    }
} else {
    // Handle error jika query gagal
    // error_log("Failed to fetch all menu items: " . $conn->error);
    // Mungkin tampilkan pesan error di halaman
}

// Untuk total penjualan hari ini, mengambil dari tabel 'transaksi' dengan kolom yang sesuai
$total_sales_today = 0;
date_default_timezone_set('Asia/Jakarta');
$today = date('Y-m-d');

// SQL query diubah untuk menggunakan 'transaksi' dan kolom 'total_keseluruhan' serta 'tanggal_transaksi'
$sql_sales_today = "SELECT SUM(total_keseluruhan) AS total_penjualan FROM transaksi WHERE DATE(tanggal_transaksi) = ?";
$stmt_sales = $conn->prepare($sql_sales_today);
if ($stmt_sales) {
    $stmt_sales->bind_param("s", $today);
    $stmt_sales->execute();
    $result_sales = $stmt_sales->get_result();
    if ($result_sales->num_rows > 0) {
        $row_sales = $result_sales->fetch_assoc();
        // Pastikan 'total_penjualan' ada dan tidak NULL
        $total_sales_today = $row_sales['total_penjualan'] ?? 0;
    }
    $stmt_sales->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Admin - RM Ampera Abbeey</title>
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
                    <a href="dashboard.php" class="nav-link active">
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
                    <a href="laporan_penjualan.php" class="nav-link">
                        <i class="fas fa-file-invoice me-2"></i> Laporan Penjualan
                    </a>
                </li>
                <li class="nav-item">
                    <a href="change_password.php" class="nav-link">
                        <i class="fas fa-key me-2"></i> Ganti Password
                    </a>
                </li>
            </ul>
        </div>

        <div class="content-area">
            <h1 class="mb-4">Selamat Datang, Admin!</h1>

            <div class="row mb-4 gy-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-0">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-utensils me-2"></i> Total Menu</h5>
                            <p class="card-text display-4"><?php echo count($menu_items_by_category) > 0 ? array_sum(array_map('count', $menu_items_by_category)) : 0; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-0">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-dollar-sign me-2"></i> Total Penjualan Hari Ini</h5>
                            <p class="card-text display-4">Rp <?php echo number_format($total_sales_today, 0, ',', '.'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-0">
                        <div class="card-body">
                            <h5 class="card-title"><i class="fas fa-users me-2"></i> Pengguna Aktif</h5>
                            <p class="card-text display-4">1</p>
                        </div>
                    </div>
                </div>
            </div>

            <h2 class="mb-3">Daftar Menu Makanan</h2>
            <p class="mb-4">Berikut adalah daftar menu yang dikelompokkan berdasarkan kategori.</p>

            <a href="add_menu.php" class="btn btn-primary mb-4"><i class="fas fa-plus-circle me-2"></i> Tambah Menu Baru</a>

            <?php if (empty($menu_items_by_category)): ?>
                <div class="alert alert-info" role="alert">
                    Belum ada menu yang ditambahkan.
                </div>
            <?php else: ?>
                <?php foreach ($categories_list as $category): ?>
                    <?php if (isset($menu_items_by_category[$category]) && !empty($menu_items_by_category[$category])): ?>
                        <div class="card mb-5"> <div class="card-header bg-dark text-white"> <h4 class="mb-0 text-white"><i class="fas fa-folder me-2"></i> Kategori: <?= htmlspecialchars($category) ?></h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Nama Menu</th>
                                                    <th>Harga</th>
                                                    <th>Deskripsi</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($menu_items_by_category[$category] as $menu): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($menu['id']) ?></td>
                                                        <td><?= htmlspecialchars($menu['nama']) ?></td>
                                                        <td>Rp <?= number_format($menu['harga'], 0, ',', '.') ?></td>
                                                        <td><?= htmlspecialchars($menu['deskripsi']) ?></td>
                                                        <td>
                                                            <a href="edit_menu.php?id=<?= htmlspecialchars($menu['id']) ?>" class="btn btn-sm btn-warning me-2"><i class="fas fa-edit"></i> Edit</a>
                                                            <a href="delete_menu.php?id=<?= htmlspecialchars($menu['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus menu ini?');"><i class="fas fa-trash-alt"></i> Hapus</a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
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