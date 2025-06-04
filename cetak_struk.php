<?php
session_start();
// Pastikan admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php"); // Redirect ke login.php
    exit();
}

// Pastikan ID transaksi ada di URL dan merupakan angka
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID transaksi tidak valid atau tidak ditemukan.";
    header("Location: transaksi_list.php");
    exit();
}

require_once 'db_config.php'; // Menggunakan file koneksi database terpusat

$id_transaksi = intval($_GET['id']); // Pastikan ID adalah integer

$transaction_header = null; // Data utama transaksi (tanggal, total_keseluruhan, uang_dibayar, kembalian)
$transaction_items = []; // Detail item transaksi (menu_nama, jumlah, harga, total_harga_item)

// 1. Ambil data header transaksi dari tabel 'transaksi'
$sql_header = "SELECT id, tanggal_transaksi, total_keseluruhan, uang_dibayar, kembalian 
               FROM transaksi 
               WHERE id = ?";
$stmt_header = $conn->prepare($sql_header);

if ($stmt_header) {
    $stmt_header->bind_param("i", $id_transaksi);
    $stmt_header->execute();
    $result_header = $stmt_header->get_result();

    if ($result_header->num_rows > 0) {
        $transaction_header = $result_header->fetch_assoc();
    } else {
        // Transaksi tidak ditemukan
        $_SESSION['error_message'] = "Transaksi dengan ID " . htmlspecialchars($id_transaksi) . " tidak ditemukan.";
        header("Location: transaksi_list.php");
        exit();
    }
    $stmt_header->close();
} else {
    // Gagal menyiapkan query header
    $_SESSION['error_message'] = "Terjadi kesalahan saat menyiapkan query header: " . $conn->error;
    header("Location: transaksi_list.php");
    exit();
}

// 2. Ambil detail item transaksi dari tabel 'detail_transaksi' dan 'menu'
// PERBAIKAN DI SINI: Mengubah 'dt.id_transaksi' menjadi 'dt.transaksi_id'
$sql_items = "SELECT dt.jumlah, dt.harga_satuan, dt.total_harga_item, m.nama AS menu_nama 
              FROM detail_transaksi dt
              JOIN menu m ON dt.menu_id = m.id
              WHERE dt.transaksi_id = ?"; // <-- THIS IS THE FIX
$stmt_items = $conn->prepare($sql_items);

if ($stmt_items) {
    $stmt_items->bind_param("i", $id_transaksi);
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();

    if ($result_items->num_rows > 0) {
        while($row_item = $result_items->fetch_assoc()) {
            $transaction_items[] = $row_item;
        }
    }
    $stmt_items->close();
} else {
    // Gagal menyiapkan query item
    $_SESSION['error_message'] = "Terjadi kesalahan saat menyiapkan query item: " . $conn->error;
    header("Location: transaksi_list.php");
    exit();
}

$conn->close(); // Tutup koneksi setelah semua operasi selesai

// Data untuk ditampilkan di struk
$tanggal_transaksi = $transaction_header['tanggal_transaksi'];
$total_keseluruhan = $transaction_header['total_keseluruhan'];
$uang_dibayar = $transaction_header['uang_dibayar'];
$kembalian = $transaction_header['kembalian'];

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Struk Pembayaran - RM Ampera Abbeey</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* Gaya dasar untuk tampilan di layar (non-cetak) */
        body {
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f0f0f0;
        }
        .struk-container {
            width: 300px;
            margin: 20px auto;
            border: 1px solid #ddd;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .struk-header {
            text-align: center;
            margin-bottom: 15px;
        }
        .struk-header h3 {
            margin: 0;
            font-size: 1.4em;
            color: #222;
        }
        .struk-header p {
            margin: 3px 0;
            font-size: 0.9em;
        }
        .struk-details table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .struk-details table td {
            padding: 5px 0;
            vertical-align: top;
        }
        .struk-details table td:last-child {
            text-align: right;
        }
        .struk-details .item-name {
            font-weight: bold;
        }
        .struk-details .item-line {
            border-bottom: 1px dashed #ccc;
        }
        .struk-details .total-label {
            font-weight: bold;
            padding-top: 8px;
            font-size: 1.1em;
        }
        .struk-details .total-value {
            font-weight: bold;
            text-align: right;
            padding-top: 8px;
            font-size: 1.1em;
        }
        .struk-details .final-total-row {
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        .struk-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
        }

        .no-print {
            text-align: center;
            margin-top: 20px;
        }
        .btn-secondary {
            display: inline-block;
            font-weight: 400;
            line-height: 1.5;
            color: #6c757d;
            text-align: center;
            vertical-align: middle;
            cursor: pointer;
            -webkit-user-select: none;
            -moz-user-select: none;
            user-select: none;
            background-color: transparent;
            border: 1px solid #6c757d;
            padding: .375rem .75rem;
            font-size: 1rem;
            border-radius: .25rem;
            text-decoration: none;
            transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }
        .btn-secondary:hover {
            color: #fff;
            background-color: #5c636a;
            border-color: #565e64;
        }
        .btn-secondary .fas {
            margin-right: 5px;
        }

        /* Print-specific styles */
        @media print {
            html, body {
                height: auto;
                overflow: hidden;
                margin: 0;
                padding: 0;
                background-color: #fff !important;
            }
            .struk-container {
                width: 58mm; /* Ukuran standar untuk printer thermal 58mm */
                margin: 0;
                border: none;
                box-shadow: none;
                padding: 3mm;
            }
            body > *:not(.struk-container) {
                display: none !important;
            }
            .no-print {
                display: none !important;
            }

            @page {
                size: auto;
                margin: 0;
                -webkit-print-color-adjust: exact;
                -moz-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body onload="window.print();">
    <div class="struk-container">
        <div class="struk-header">
            <h3>RM Ampera Abbeey</h3>
            <p>Jl. Lintas Sumatera No.49, Candi MAS</p>
            <p>Tel: (0721) 1234567</p>
            <p>Tanggal: <?= date('d/m/Y H:i', strtotime($tanggal_transaksi)) ?></p>
            <hr style="border-top: 1px dashed #000; margin: 10px 0;">
        </div>

        <div class="struk-details">
            <table>
                <?php if (!empty($transaction_items)): ?>
                    <?php foreach ($transaction_items as $item): ?>
                        <tr class="item-line">
                            <td colspan="2" class="item-name"><?= htmlspecialchars($item['menu_nama']) ?></td>
                        </tr>
                        <tr class="item-line">
                            <td><?= htmlspecialchars($item['jumlah']) ?> x Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                            <td style="text-align:right;">Rp <?= number_format($item['total_harga_item'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2">Tidak ada item dalam transaksi ini.</td>
                    </tr>
                <?php endif; ?>
                <tr class="final-total-row">
                    <td class="total-label">TOTAL</td>
                    <td class="total-value">Rp <?= number_format($total_keseluruhan, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="total-label">Bayar</td>
                    <td class="total-value">Rp <?= number_format($uang_dibayar, 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="total-label">Kembalian</td>
                    <td class="total-value">
                        <?php if ($kembalian < 0): ?>
                            Kurang Rp <?= number_format(abs($kembalian), 0, ',', '.') ?>
                        <?php else: ?>
                            Rp <?= number_format($kembalian, 0, ',', '.') ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="struk-footer">
            <p>Terima kasih atas kunjungan Anda!</p>
            <p>Semoga Anda menikmati hidangan kami.</p>
            <p style="font-size:0.8em; margin-top:10px;">ID Transaksi: <?= htmlspecialchars($id_transaksi) ?></p>
        </div>
    </div>

    <div class="no-print">
        <a href="transaksi_list.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Transaksi</a>
    </div>

    <script>
        window.onafterprint = function() {
            window.location.href = 'transaksi_list.php';
        };

        // Fallback in case onafterprint doesn't fire immediately (e.g., if print dialog is cancelled)
        setTimeout(function() {
            if (document.visibilityState === 'visible') { 
                window.location.href = 'transaksi_list.php';
            }
        }, 3000); 
    </script>
</body>
</html>