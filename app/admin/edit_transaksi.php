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
$detail_transaksi_item = null; // Ini akan menyimpan data satu item dari detail_transaksi
$menu_items = []; // Untuk dropdown menu

// --- Bagian GET (Menampilkan Form) ---
// Kita perlu ID dari detail_transaksi, bukan ID dari transaksi utama
if (!isset($_GET['detail_id']) || !is_numeric($_GET['detail_id'])) {
    $_SESSION['error_message'] = "ID detail transaksi tidak valid atau tidak ditemukan.";
    header("Location: transaksi_list.php"); // Atau ke detail transaksi utama jika ada
    exit();
}

$id_detail_transaksi = intval($_GET['detail_id']); // ID dari tabel detail_transaksi

// Ambil data detail transaksi yang akan diedit
$sql_get_detail_transaksi = "SELECT dt.id, dt.transaksi_id, dt.menu_id, dt.jumlah, dt.harga_satuan, dt.total_harga_item, m.nama as menu_nama, m.harga as menu_harga 
                             FROM detail_transaksi dt
                             JOIN menu m ON dt.menu_id = m.id
                             WHERE dt.id = ?";
$stmt_get_detail_transaksi = $conn->prepare($sql_get_detail_transaksi);

if ($stmt_get_detail_transaksi) {
    $stmt_get_detail_transaksi->bind_param("i", $id_detail_transaksi);
    $stmt_get_detail_transaksi->execute();
    $result_detail_transaksi = $stmt_get_detail_transaksi->get_result();

    if ($result_detail_transaksi->num_rows > 0) {
        $detail_transaksi_item = $result_detail_transaksi->fetch_assoc();
        // Simpan id_transaksi utama untuk redirect kembali
        $id_transaksi_utama = $detail_transaksi_item['transaksi_id']; 
    } else {
        $_SESSION['error_message'] = "Detail transaksi dengan ID " . htmlspecialchars($id_detail_transaksi) . " tidak ditemukan.";
        header("Location: transaksi_list.php"); // Atau ke detail transaksi utama jika ada
        exit();
    }
    $stmt_get_detail_transaksi->close();
} else {
    $_SESSION['error_message'] = "Gagal menyiapkan query ambil detail transaksi: " . $conn->error;
    header("Location: transaksi_list.php"); // Atau ke detail transaksi utama jika ada
    exit();
}

// Ambil semua data menu untuk dropdown
$sql_get_menus = "SELECT id, nama, harga FROM menu ORDER BY nama ASC";
$result_menu = $conn->query($sql_get_menus);
if ($result_menu && $result_menu->num_rows > 0) {
    while($row = $result_menu->fetch_assoc()) {
        $menu_items[] = $row;
    }
} else {
    $error_message = "Tidak ada menu tersedia atau terjadi kesalahan saat mengambil data menu.";
}

// --- Bagian POST (Memproses Perubahan) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_menu_id = intval($_POST['menu_id']);
    $new_jumlah = intval($_POST['jumlah']);

    // Validasi input
    if ($new_menu_id <= 0 || $new_jumlah <= 0) {
        $error_message = "Pilihan menu atau jumlah tidak valid.";
    } else {
        // Ambil harga menu baru yang dipilih
        $sql_get_new_harga = "SELECT harga FROM menu WHERE id = ?";
        $stmt_get_new_harga = $conn->prepare($sql_get_new_harga);
        if ($stmt_get_new_harga) {
            $stmt_get_new_harga->bind_param("i", $new_menu_id);
            $stmt_get_new_harga->execute();
            $result_new_harga = $stmt_get_new_harga->get_result();
            $new_menu_data = $result_new_harga->fetch_assoc();
            $stmt_get_new_harga->close();

            if ($new_menu_data) {
                $new_harga_satuan = $new_menu_data['harga'];
                $new_total_harga_item = $new_harga_satuan * $new_jumlah;

                // Update detail_transaksi
                $sql_update_detail_transaksi = "UPDATE detail_transaksi SET menu_id = ?, jumlah = ?, harga_satuan = ?, total_harga_item = ? WHERE id = ?";
                $stmt_update_detail_transaksi = $conn->prepare($sql_update_detail_transaksi);
                if ($stmt_update_detail_transaksi) {
                    $stmt_update_detail_transaksi->bind_param("iiidi", $new_menu_id, $new_jumlah, $new_harga_satuan, $new_total_harga_item, $id_detail_transaksi); // d for double/float
                    if ($stmt_update_detail_transaksi->execute()) {
                        // Setelah mengupdate item detail, kita perlu mengupdate total_keseluruhan di tabel transaksi utama
                        $sql_recalculate_total = "UPDATE transaksi 
                                                  SET total_keseluruhan = (SELECT SUM(total_harga_item) FROM detail_transaksi WHERE transaksi_id = ?) 
                                                  WHERE id = ?";
                        $stmt_recalculate_total = $conn->prepare($sql_recalculate_total);
                        if ($stmt_recalculate_total) {
                            $stmt_recalculate_total->bind_param("ii", $id_transaksi_utama, $id_transaksi_utama);
                            $stmt_recalculate_total->execute();
                            $stmt_recalculate_total->close();
                        } else {
                            // Log error, but don't stop the process for the user
                            error_log("Failed to recalculate total for transaction ID {$id_transaksi_utama}: " . $conn->error);
                        }


                        $_SESSION['success_message'] = "Detail transaksi ID " . htmlspecialchars($id_detail_transaksi) . " berhasil diperbarui!";
                        header("Location: transaksi_list.php"); // Redirect ke daftar transaksi
                        exit();
                    } else {
                        $error_message = "Gagal memperbarui detail transaksi: " . $stmt_update_detail_transaksi->error;
                    }
                    $stmt_update_detail_transaksi->close();
                } else {
                    $error_message = "Gagal menyiapkan query update detail transaksi: " . $conn->error;
                }
            } else {
                $error_message = "Menu yang dipilih tidak ditemukan.";
            }
        } else {
            $error_message = "Gagal menyiapkan query harga menu baru: " . $conn->error;
        }
    }
}

// Ambil pesan sukses/error dari sesi jika ada (misal dari redirect sebelumnya)
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

$conn->close(); // Tutup koneksi setelah semua operasi selesai
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Detail Transaksi - RM Ampera Abbeey</title>
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
                    <a href="transaksi_list.php" class="nav-link active"> <i class="fas fa-cash-register me-2"></i> Daftar Transaksi
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
            <h1 class="mb-4">Edit Detail Transaksi ID: <?= htmlspecialchars($id_detail_transaksi) ?></h1>
            <p>Untuk Transaksi Utama ID: <?= htmlspecialchars($id_transaksi_utama) ?></p>

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

            <?php if ($detail_transaksi_item): // Tampilkan form hanya jika detail transaksi ditemukan ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-4">Form Edit Item Transaksi</h5>
                    <form method="POST" action="edit_transaksi.php?detail_id=<?= htmlspecialchars($id_detail_transaksi) ?>">
                        <div class="mb-3">
                            <label for="menu" class="form-label">Menu:</label>
                            <select name="menu_id" id="menu" class="form-select" required onchange="updateHarga()">
                                <?php if (!empty($menu_items)): ?>
                                    <?php foreach($menu_items as $menu): ?>
                                        <option value="<?= htmlspecialchars($menu['id']) ?>" data-harga="<?= htmlspecialchars($menu['harga']) ?>"
                                            <?= ($menu['id'] == $detail_transaksi_item['menu_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($menu['nama']) ?> - Rp <?= number_format($menu['harga'],0,',','.') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Tidak ada menu tersedia</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="jumlah" class="form-label">Jumlah:</label>
                            <input type="number" name="jumlah" id="jumlah" class="form-control" value="<?= htmlspecialchars($detail_transaksi_item['jumlah']) ?>" min="1" required oninput="updateHarga()">
                        </div>

                        <div class="mb-4">
                            <label for="total_harga" class="form-label">Total Harga Item (Otomatis):</label>
                            <input type="text" id="total_harga" class="form-control" readonly>
                        </div>

                        <button type="submit" class="btn btn-primary-custom"><i class="fas fa-save me-2"></i> Simpan Perubahan</button>
                    </form>
                    <a href="transaksi_list.php" class="btn btn-secondary mt-3"><i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Transaksi</a>
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
    function updateHarga() {
        const menuSelect = document.getElementById('menu');
        const jumlahInput = document.getElementById('jumlah');
        const totalHargaInput = document.getElementById('total_harga');

        const selectedOption = menuSelect.options[menuSelect.selectedIndex];
        let harga = 0;
        if (selectedOption && selectedOption.value !== "") {
            harga = parseFloat(selectedOption.getAttribute('data-harga')) || 0;
        }

        const jumlah = parseInt(jumlahInput.value) || 0;
        const total = harga * jumlah;

        totalHargaInput.value = total.toLocaleString('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 });
    }

    // Panggil updateHarga saat halaman dimuat untuk menampilkan total harga awal
    document.addEventListener('DOMContentLoaded', updateHarga);
    </script>
</body>
</html>