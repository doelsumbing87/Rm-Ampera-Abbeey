<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';

$success_message = "";
$error_message = "";

// Ambil data menu untuk dropdown
$menu_items = [];
$sql_menu_select = "SELECT id, nama, harga FROM menu ORDER BY nama ASC";
$result_menu = $conn->query($sql_menu_select);
if ($result_menu && $result_menu->num_rows > 0) {
    while($row = $result_menu->fetch_assoc()) {
        $menu_items[] = $row;
    }
} else {
    $error_message = "Tidak ada menu tersedia atau terjadi kesalahan saat mengambil data menu.";
}

// Proses form penjualan jika data dikirimkan melalui POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    date_default_timezone_set('Asia/Jakarta');
    $tanggal_transaksi = date('Y-m-d H:i:s');

    $selected_menu_ids = $_POST['menu_id'] ?? [];
    $quantities = $_POST['jumlah'] ?? [];
    $uang_pembeli_display = floatval(str_replace('.', '', $_POST['uang_pembeli_display'] ?? '0'));

    $all_items_processed = true;
    $total_transaksi_keseluruhan = 0; // Untuk menyimpan total global dari PHP
    $menu_details_for_calculation = []; // Array untuk menyimpan detail menu sementara

    if (empty($selected_menu_ids)) {
        $error_message = "Tidak ada menu yang dipilih untuk transaksi.";
        $all_items_processed = false;
    } else {
        // --- Langkah 1: Hitung total keseluruhan terlebih dahulu dan validasi item ---
        foreach ($selected_menu_ids as $index => $menu_id) {
            $menu_id = intval($menu_id);
            $jumlah = intval($quantities[$index]);

            if ($menu_id <= 0 || $jumlah <= 0) {
                $error_message .= "Pilihan menu atau jumlah untuk baris " . ($index + 1) . " tidak valid.<br>";
                $all_items_processed = false;
                break;
            }

            $sql_get_harga = "SELECT nama, harga FROM menu WHERE id = ?";
            $stmt_get_harga = $conn->prepare($sql_get_harga);
            if ($stmt_get_harga) {
                $stmt_get_harga->bind_param("i", $menu_id);
                $stmt_get_harga->execute();
                $result_harga = $stmt_get_harga->get_result();
                $menu_data = $result_harga->fetch_assoc();
                $stmt_get_harga->close();

                if ($menu_data) {
                    $harga_satuan = $menu_data['harga'];
                    $total_harga_item = $harga_satuan * $jumlah;
                    $total_transaksi_keseluruhan += $total_harga_item;

                    // Simpan detail menu untuk insert ke detail_transaksi nanti
                    $menu_details_for_calculation[] = [
                        'menu_id' => $menu_id,
                        'nama_menu' => $menu_data['nama'], // Mungkin berguna untuk debugging atau logging
                        'jumlah' => $jumlah,
                        'harga_satuan' => $harga_satuan,
                        'total_harga_item' => $total_harga_item
                    ];
                } else {
                    $error_message .= "Menu ID " . htmlspecialchars($menu_id) . " untuk baris " . ($index + 1) . " tidak ditemukan.<br>";
                    $all_items_processed = false;
                    break;
                }
            } else {
                $error_message .= "Gagal menyiapkan query harga menu untuk item " . ($index + 1) . ": " . $conn->error . "<br>";
                $all_items_processed = false;
                break;
            }
        } // End initial foreach for total calculation and validation

        if ($all_items_processed) {
            $conn->begin_transaction(); // Mulai transaksi di sini setelah semua validasi awal
            try {
                $kembalian_untuk_struk = $uang_pembeli_display - $total_transaksi_keseluruhan;

                // --- Langkah 2: Masukkan data ke tabel 'transaksi' (transaksi utama) ---
                $sql_insert_transaksi = "INSERT INTO transaksi (tanggal_transaksi, total_keseluruhan, uang_dibayar, kembalian) VALUES (?, ?, ?, ?)";
                $stmt_transaksi = $conn->prepare($sql_insert_transaksi);
                if (!$stmt_transaksi) {
                    throw new Exception("Gagal menyiapkan query insert transaksi utama: " . $conn->error);
                }
                $stmt_transaksi->bind_param("sddd", $tanggal_transaksi, $total_transaksi_keseluruhan, $uang_pembeli_display, $kembalian_untuk_struk);
                if (!$stmt_transaksi->execute()) {
                    throw new Exception("Gagal menyimpan transaksi utama: " . $stmt_transaksi->error);
                }
                $new_transaksi_id = $conn->insert_id; // ID transaksi utama yang baru
                $stmt_transaksi->close();

                // --- Langkah 3: Masukkan setiap item ke tabel 'detail_transaksi' ---
                $sql_insert_detail_transaksi = "INSERT INTO detail_transaksi (transaksi_id, menu_id, jumlah, harga_satuan, total_harga_item) VALUES (?, ?, ?, ?, ?)";
                $stmt_detail_transaksi = $conn->prepare($sql_insert_detail_transaksi);
                if (!$stmt_detail_transaksi) {
                    throw new Exception("Gagal menyiapkan query insert detail transaksi: " . $conn->error);
                }

                foreach ($menu_details_for_calculation as $item) {
                    $stmt_detail_transaksi->bind_param(
                        "iiidd", // i: integer, d: double (float)
                        $new_transaksi_id,
                        $item['menu_id'],
                        $item['jumlah'],
                        $item['harga_satuan'],
                        $item['total_harga_item']
                    );
                    if (!$stmt_detail_transaksi->execute()) {
                        throw new Exception("Gagal menyimpan detail item " . htmlspecialchars($item['nama_menu']) . ": " . $stmt_detail_transaksi->error);
                    }
                }
                $stmt_detail_transaksi->close();

                // Jika semua berhasil, commit transaksi
                $conn->commit();
                $_SESSION['success_message'] = "Transaksi berhasil disimpan!";
                $_SESSION['struk_uang_dibayar'] = $uang_pembeli_display;
                $_SESSION['struk_kembalian'] = $kembalian_untuk_struk;

                // Redirect ke cetak_struk.php dengan ID transaksi utama
                header("Location: cetak_struk.php?id=$new_transaksi_id");
                exit();

            } catch (Exception $e) {
                // Rollback jika ada kesalahan
                $conn->rollback();
                $error_message = "Terjadi kesalahan saat menyimpan transaksi: " . $e->getMessage() . " Transaksi dibatalkan.";
            }
        } else {
            // Jika ada masalah validasi di awal (sebelum memulai transaction)
            // error_message sudah terisi, tidak perlu rollback
        }
    }
}

// Ambil pesan sukses dari sesi jika ada
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Form Kasir - RM Ampera Abbeey</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="./assets/css/style.css" />
    <link rel="stylesheet" href="./assets/css/admin.css" />
    <style>
        .item-row {
            display: flex;
            align-items: flex-end;
            gap: 15px;
            margin-bottom: 15px;
            border: 1px solid #e0e0e0;
            padding: 10px;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .item-row .form-group {
            flex: 1;
        }
        .item-row .col-select, .item-row .col-quantity {
            flex-basis: 45%;
        }
        .item-row .col-actions {
            flex-basis: auto;
        }
        .item-row .form-control, .item-row .form-select {
            height: calc(1.5em + .75rem + 2px);
        }
        /* Tambahan styling untuk input uang dan kembalian */
        .cash-payment-section .form-control {
            font-size: 1.2rem;
            font-weight: bold;
            text-align: end;
        }
        .cash-payment-section .form-control[readonly] {
            background-color: #e9ecef; /* Warna latar belakang untuk input readonly */
            cursor: default;
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
                    <a href="kasir.php" class="nav-link active">
                        <i class="fas fa-calculator me-2"></i> Form Kasir
                    </a>
                </li>
            </ul>
        </div>

        <div class="content-area">
            <h1 class="mb-4">Form Kasir Multi-Item</h1>

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
                    <h5 class="card-title mb-4">Input Transaksi Penjualan</h5>
                    <form method="POST" action="kasir.php" id="kasirForm">
                        <div id="menu-items-container">
                            <div class="item-row" data-item-index="0">
                                <div class="form-group col-select">
                                    <label for="menu_id_0" class="form-label visually-hidden">Menu</label>
                                    <select name="menu_id[]" id="menu_id_0" class="form-select menu-select" required onchange="calculateTotal()">
                                        <option value="">-- Pilih Menu --</option>
                                        <?php if (!empty($menu_items)): ?>
                                            <?php foreach($menu_items as $menu_item): ?>
                                                <option value="<?= htmlspecialchars($menu_item['id']) ?>" data-harga="<?= htmlspecialchars($menu_item['harga']) ?>">
                                                    <?= htmlspecialchars($menu_item['nama']) ?> - Rp <?= number_format($menu_item['harga'],0,',','.') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                             <option value="" disabled>Tidak ada menu tersedia</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="form-group col-quantity">
                                    <label for="jumlah_0" class="form-label visually-hidden">Jumlah</label>
                                    <input type="number" name="jumlah[]" id="jumlah_0" class="form-control quantity-input" value="1" min="1" required oninput="calculateTotal()">
                                </div>
                                <div class="form-group col-actions">
                                    </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-info btn-sm mb-3" id="add-item-btn"><i class="fas fa-plus me-2"></i> Tambah Item Lain</button>

                        <div class="mb-4">
                            <label for="total_harga_global" class="form-label">Total Harga Keseluruhan:</label>
                            <input type="text" id="total_harga_global" class="form-control form-control-lg fw-bold text-end" readonly>
                        </div>

                        <div class="cash-payment-section mt-5 p-4 border rounded">
                            <h5 class="card-title mb-3">Pembayaran Tunai</h5>
                            <div class="mb-3">
                                <label for="uang_pembeli_display" class="form-label">Uang Pembeli:</label>
                                <input type="text" name="uang_pembeli_display" id="uang_pembeli_display" class="form-control" placeholder="Masukkan jumlah uang dari pembeli" oninput="formatAndCalculateChange()">
                            </div>
                            <div class="mb-4">
                                <label for="kembalian" class="form-label">Kembalian:</label>
                                <input type="text" id="kembalian" class="form-control" readonly>
                            </div>
                        </div>


                        <button type="submit" class="btn btn-primary-custom mt-4"><i class="fas fa-save me-2"></i> Simpan Transaksi</button>
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
        const menuItemsContainer = document.getElementById('menu-items-container');
        const addMenuItemBtn = document.getElementById('add-item-btn');
        const globalTotalHargaInput = document.getElementById('total_harga_global');
        const uangPembeliInput = document.getElementById('uang_pembeli_display');
        const kembalianInput = document.getElementById('kembalian');

        let itemIndex = 0;
        const menuData = <?php echo json_encode($menu_items); ?>;

        function createMenuItemRow(index) {
            const newRow = document.createElement('div');
            newRow.classList.add('item-row');
            newRow.setAttribute('data-item-index', index);

            let optionsHtml = '<option value="">-- Pilih Menu --</option>';
            menuData.forEach(item => {
                optionsHtml += `<option value="${item.id}" data-harga="${item.harga}">${item.nama} - Rp ${item.harga.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 })}</option>`;
            });

            newRow.innerHTML = `
                <div class="form-group col-select">
                    <label for="menu_id_${index}" class="form-label visually-hidden">Menu</label>
                    <select name="menu_id[]" id="menu_id_${index}" class="form-select menu-select" required onchange="calculateTotal()">
                        ${optionsHtml}
                    </select>
                </div>
                <div class="form-group col-quantity">
                    <label for="jumlah_${index}" class="form-label visually-hidden">Jumlah</label>
                    <input type="number" name="jumlah[]" id="jumlah_${index}" class="form-control quantity-input" value="1" min="1" required oninput="calculateTotal()">
                </div>
                <div class="form-group col-actions">
                    <button type="button" class="btn btn-danger btn-sm remove-item-btn"><i class="fas fa-trash"></i></button>
                </div>
            `;

            newRow.querySelector('.remove-item-btn').addEventListener('click', function() {
                newRow.remove();
                calculateTotal();
            });

            return newRow;
        }

        addMenuItemBtn.addEventListener('click', function() {
            itemIndex++;
            const newRow = createMenuItemRow(itemIndex);
            menuItemsContainer.appendChild(newRow);
            calculateTotal();
        });

        let currentGrandTotal = 0; // Variabel global untuk menyimpan total harga
        function calculateTotal() {
            let grandTotal = 0;
            const itemRows = document.querySelectorAll('.item-row');

            itemRows.forEach(row => {
                const menuSelect = row.querySelector('.menu-select');
                const quantityInput = row.querySelector('.quantity-input');

                const selectedOption = menuSelect.options[menuSelect.selectedIndex];
                let itemPrice = 0;
                if (selectedOption && selectedOption.value !== "") {
                    itemPrice = parseFloat(selectedOption.getAttribute('data-harga')) || 0;
                }

                const quantity = parseInt(quantityInput.value) || 0;
                grandTotal += itemPrice * quantity;
            });

            currentGrandTotal = grandTotal; // Simpan total global
            globalTotalHargaInput.value = grandTotal.toLocaleString('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 });

            calculateChange(); // Panggil fungsi kembalian setiap kali total berubah
        }

        function formatAndCalculateChange() {
            let value = uangPembeliInput.value.replace(/\D/g, ''); // Hapus semua non-digit
            if (value === '') {
                uangPembeliInput.value = '';
                calculateChange();
                return;
            }
            value = parseFloat(value).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            uangPembeliInput.value = value;
            calculateChange();
        }

        function calculateChange() {
            const uangPembeliFormatted = uangPembeliInput.value;
            const uangPembeliRaw = parseFloat(uangPembeliFormatted.replace(/\./g, '')) || 0; // Hapus titik ribuan untuk perhitungan

            let kembalian = uangPembeliRaw - currentGrandTotal;

            if (kembalian < 0) {
                kembalianInput.value = "Kurang Rp " + Math.abs(kembalian).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                kembalianInput.style.color = 'red';
            } else {
                kembalianInput.value = "Rp " + kembalian.toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                kembalianInput.style.color = 'green';
            }
        }


        // Initialize first item row and calculate total on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
            document.getElementById('copyrightCurrentYearAdmin').textContent = new Date().getFullYear();
            // Attach event listener for uang_pembeli input
            uangPembeliInput.addEventListener('input', formatAndCalculateChange);
        });
    </script>
</body>
</html>