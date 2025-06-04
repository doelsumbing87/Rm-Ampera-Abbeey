<?php
session_start();
// Pastikan admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php"); // Redirect ke login.php jika belum login
    exit();
}

require_once 'db_config.php'; // Menggunakan file koneksi database terpusat

$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    // Ambil password (hash) admin dari database
    $admin_username = $_SESSION['admin_username'];
    $sql_get_password = "SELECT password FROM admin WHERE username = ?";
    $stmt_get_password = $conn->prepare($sql_get_password);

    if ($stmt_get_password) {
        $stmt_get_password->bind_param("s", $admin_username);
        $stmt_get_password->execute();
        $result_password = $stmt_get_password->get_result();
        $admin_data = $result_password->fetch_assoc();
        $stmt_get_password->close();

        if ($admin_data) {
            $hashed_current_password_db = $admin_data['password'];

            // 1. Verifikasi password lama
            if (!password_verify($current_password, $hashed_current_password_db)) {
                $error_message = "Password lama salah.";
            }
            // 2. Verifikasi password baru dan konfirmasi
            else if (empty($new_password) || empty($confirm_new_password)) {
                $error_message = "Password baru dan konfirmasi password tidak boleh kosong.";
            }
            else if ($new_password !== $confirm_new_password) {
                $error_message = "Password baru dan konfirmasi password tidak cocok.";
            }
            // 3. Tambahkan validasi keamanan password baru (misalnya panjang minimum)
            else if (strlen($new_password) < 6) { // Contoh: minimal 6 karakter
                $error_message = "Password baru harus minimal 6 karakter.";
            }
            else {
                // Semua validasi sukses, hash password baru dan update
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

                $sql_update_password = "UPDATE admin SET password = ? WHERE username = ?";
                $stmt_update_password = $conn->prepare($sql_update_password);

                if ($stmt_update_password) {
                    $stmt_update_password->bind_param("ss", $hashed_new_password, $admin_username);
                    if ($stmt_update_password->execute()) {
                        $success_message = "Password berhasil diubah!";
                        // Opsional: memaksa logout setelah ganti password untuk login ulang dengan yang baru
                        // session_unset();
                        // session_destroy();
                        // header("Location: login.php?message=password_changed");
                        // exit();
                    } else {
                        $error_message = "Gagal mengubah password: " . $stmt_update_password->error;
                    }
                    $stmt_update_password->close();
                } else {
                    $error_message = "Gagal menyiapkan query update password: " . $conn->error;
                }
            }
        } else {
            // Ini seharusnya tidak terjadi jika sesi admin_username valid
            $error_message = "Data admin tidak ditemukan. Silakan login ulang.";
            session_unset();
            session_destroy();
            header("Location: login.php");
            exit();
        }
    } else {
        $error_message = "Gagal menyiapkan query verifikasi password: " . $conn->error;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Ganti Password Admin - RM Ampera Abbeey</title>
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
                    <a href="change_password.php" class="nav-link active"> <i class="fas fa-key me-2"></i> Ganti Password
                    </a>
                </li>
            </ul>
        </div>

        <div class="content-area">
            <h1 class="mb-4">Ganti Password Admin</h1>

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
                    <h5 class="card-title mb-4">Form Ganti Password</h5>
                    <form method="POST" action="change_password.php">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Lama:</label>
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru:</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" required>
                            <div class="form-text">Minimal 6 karakter.</div>
                        </div>
                        <div class="mb-4">
                            <label for="confirm_new_password" class="form-label">Konfirmasi Password Baru:</label>
                            <input type="password" name="confirm_new_password" id="confirm_new_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary-custom"><i class="fas fa-key me-2"></i> Ubah Password</button>
                    </form>
                    <a href="dashboard.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard</a>
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