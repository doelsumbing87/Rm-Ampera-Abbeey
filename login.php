<?php
session_start(); // Selalu mulai sesi di awal file PHP yang menggunakan sesi
require_once 'db_config.php'; // Sertakan file koneksi database Anda

// Aktifkan pelaporan error untuk debugging (HANYA UNTUK PENGEMBANGAN)
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Proses form login jika data dikirimkan melalui POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Gunakan prepared statement untuk keamanan
    $sql = "SELECT id, username, password FROM admin WHERE username = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            // Verifikasi password yang di-hash
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $admin['username'];
                header("Location: dashboard.php"); // Redirect ke dashboard setelah login sukses
                exit();
            } else {
                // Password salah
                $_SESSION['login_error'] = "Username atau password salah.";
            }
        } else {
            // Username tidak ditemukan
            $_SESSION['login_error'] = "Username atau password salah.";
        }
        $stmt->close();
    } else {
        $_SESSION['login_error'] = "Terjadi kesalahan database. Silakan coba lagi.";
        // Untuk debugging, bisa tambahkan: $_SESSION['login_error'] .= " Error: " . $conn->error;
    }
    $conn->close(); // Tutup koneksi setelah selesai
}
// Jika bukan POST request (misalnya, saat halaman pertama kali dimuat) atau login gagal,
// maka tampilkan form login di bawah ini
?>
<!DOCTYPE html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login Admin - RM Ampera Abbeey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./assets/css/style.css" />
    <style>
      body {
        background-color: var(--background-color);
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        font-family: var(--font-family-heebo); /* Pastikan ini ada di style.css */
        color: var(--text-color);
      }
      .login-container {
        background-color: var(--text-color-white);
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        width: 100%;
        max-width: 450px;
      }
      .login-header {
        text-align: center;
        margin-bottom: 35px;
      }
      .login-header h1 {
        font-size: 2.8rem;
        color: var(--heading-color);
        font-family: var(--font-family-nunito); /* Pastikan ini ada di style.css */
        font-weight: 800;
      }
      .login-header .fa-utensils {
        color: var(--primary-color);
        font-size: 3rem;
      }
      .form-label {
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 8px;
      }
      .form-control {
        border-color: var(--border-color);
        padding: 12px 15px;
        font-size: 1rem;
      }
      .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 0.25rem rgba(229, 97, 47, 0.25); /* Jika --primary-rgb belum di style.css */
      }
      .btn-primary-custom {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
        width: 100%;
        padding: 12px;
        font-weight: 600;
        font-size: 1.1rem;
        transition: background-color 0.3s ease, border-color 0.3s ease;
      }
      .btn-primary-custom:hover {
        background-color: #d15123; /* Menggunakan nilai hardcode jika --darker-primary-color belum di style.css */
        border-color: #d15123;
      }
      .alert-danger {
          margin-top: 20px;
          text-align: center;
          font-family: var(--font-family-heebo); /* Pastikan ini ada di style.css */
          font-weight: 500;
      }
    </style>
  </head>
  <body>
    <div class="login-container">
      <div class="login-header">
        <i class="fa fa-utensils mb-2"></i>
        <h1 class="mb-0">Admin Login</h1>
        <p class="text-muted mt-2">RM Ampera Abbeey</p>
      </div>

      <?php
      // PHP untuk menampilkan pesan error jika ada
      if (isset($_SESSION['login_error'])) {
          echo '<div class="alert alert-danger" role="alert">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
          unset($_SESSION['login_error']); // Hapus pesan error setelah ditampilkan
      }
      ?>

      <form action="login.php" method="POST">
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="text" class="form-control" id="username" name="username" required>
        </div>
        <div class="mb-4">
          <label for="password" class="form-label">Password</label>
          <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary-custom">Login</button>
      </form>

      <div class="mt-3 text-center">
          <a href="change_password.php" class="text-decoration-none text-primary-custom">Ganti Password?</a>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
  </body>
</html>