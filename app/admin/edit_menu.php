<?php
session_start();
// Pastikan admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php"); // Redirect ke login.php
    exit();
}

require_once 'db_config.php'; // Menggunakan file koneksi database terpusat

$success_message = "";
$error_message = "";
$menu = null; // Inisialisasi variabel menu
$categories = [ // Daftar kategori yang tersedia (konsisten dengan add_menu.php)
    "Makanan",
    "Lauk Pauk",
    "Minuman",
    "Camilan",
];

// --- Bagian GET (Menampilkan Form) ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID menu tidak valid atau tidak ditemukan.";
    header("Location: dashboard.php");
    exit();
}

$id_menu = intval($_GET['id']); // Pastikan ID adalah integer

// Ambil data menu sesuai id menggunakan prepared statement
$sql_get_menu = "SELECT id, nama, harga, deskripsi, kategori FROM menu WHERE id = ?";
$stmt_get_menu = $conn->prepare($sql_get_menu);

if ($stmt_get_menu) {
    $stmt_get_menu->bind_param("i", $id_menu); // i: integer
    $stmt_get_menu->execute();
    $result_menu_data = $stmt_get_menu->get_result();

    if ($result_menu_data->num_rows > 0) {
        $menu = $result_menu_data->fetch_assoc();
    } else {
        $_SESSION['error_message'] = "Menu dengan ID " . htmlspecialchars($id_menu) . " tidak ditemukan.";
        header("Location: dashboard.php");
        exit();
    }
    $stmt_get_menu->close();
} else {
    $_SESSION['error_message'] = "Gagal menyiapkan query ambil menu: " . $conn->error;
    header("Location: dashboard.php");
    exit();
}

// --- Bagian POST (Memproses Perubahan) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $harga = intval($_POST['harga']); // Pastikan harga adalah integer
    $deskripsi = $_POST['deskripsi'];
    $kategori = $_POST['kategori']; // Ambil kategori dari form

    // Validasi input
    if (empty($nama) || $harga <= 0 || empty($deskripsi) || empty($kategori)) {
        $error_message = "Semua field harus diisi dengan benar.";
    } elseif (!in_array($kategori, $categories)) { // Validasi kategori
        $error_message = "Kategori yang dipilih tidak valid.";
    } else {
        // Update menu menggunakan prepared statement
        // Tambahkan 'kategori' ke SET statement
        $sql_update_menu = "UPDATE menu SET nama = ?, harga = ?, deskripsi = ?, kategori = ? WHERE id = ?";
        $stmt_update_menu = $conn->prepare($sql_update_menu);

        if ($stmt_update_menu) {
            // s:string, i:integer, s:string, s:string, i:integer
            $stmt_update_menu->bind_param("sissi", $nama, $harga, $deskripsi, $kategori, $id_menu);
            if ($stmt_update_menu->execute()) {
                $_SESSION['success_message'] = "Menu '" . htmlspecialchars($nama) . "' berhasil diperbarui!";
                header("Location: dashboard.php"); // Redirect ke dashboard setelah sukses
                exit();
            } else {
                $error_message = "Gagal memperbarui menu: " . $stmt_update_menu->error;
            }
            $stmt_update_menu->close();
        } else {
            $error_message = "Gagal menyiapkan query update: " . $conn->error;
        }
    }
}

$conn->close(); // Tutup koneksi setelah semua operasi selesai
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Menu - RM Ampera Abbeey</title>
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
                    <a href="dashboard.php" class="nav-link active"> <i class="fas fa-chart-line me-2"></i> Dashboard
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
            </ul>
        </div>

        <div class="content-area">
            <h1 class="mb-4">Edit Menu: <?= htmlspecialchars($menu['nama'] ?? '') ?></h1>

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

            <?php if ($menu): // Tampilkan form hanya jika data menu ditemukan ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Form Edit Menu</h5>
                    <form method="POST" action="edit_menu.php?id=<?= htmlspecialchars($id_menu) ?>">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Menu:</label>
                            <input type="text" name="nama" id="nama" class="form-control" value="<?= htmlspecialchars($menu['nama']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="harga" class="form-label">Harga:</label>
                            <input type="number" name="harga" id="harga" class="form-control" value="<?= htmlspecialchars($menu['harga']) ?>" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi:</label>
                            <textarea name="deskripsi" id="deskripsi" class="form-control" rows="3" required><?= htmlspecialchars($menu['deskripsi']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori:</label>
                            <select name="kategori" id="kategori" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" <?= ($cat == $menu['kategori']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save me-2"></i> Simpan Perubahan</button>
                    </form>
                    <a href="dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard</a>
                </div>
            </div>
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