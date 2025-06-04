<?php
session_start();
// Pastikan admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php"); // Redirect ke login.php jika belum login
    exit();
}

require_once 'db_config.php'; // Menggunakan file koneksi database terpusat

date_default_timezone_set('Asia/Jakarta'); // Pastikan timezone sesuai

$tgl_mulai = $_GET['tgl_mulai'] ?? '';
$tgl_selesai = $_GET['tgl_selesai'] ?? '';

// Filter tanggal
$where_clause = "";
$bind_params = [];
$bind_types = "";

if ($tgl_mulai && $tgl_selesai) {
    // Memastikan format tanggal valid
    if (strtotime($tgl_mulai) === false || strtotime($tgl_selesai) === false) {
        // Jika tanggal tidak valid, redirect kembali atau tampilkan pesan error
        $_SESSION['error_message'] = "Format tanggal tidak valid untuk export.";
        header("Location: laporan_penjualan.php");
        exit();
    } else {
        $where_clause = "WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?";
        $bind_params[] = $tgl_mulai;
        $bind_params[] = $tgl_selesai;
        $bind_types = "ss"; // s for string (date)
    }
} else {
    // Jika tidak ada filter tanggal, defaultkan ke hari ini
    $today_date = date('Y-m-d');
    $tgl_mulai = $today_date;
    $tgl_selesai = $today_date;
    $where_clause = "WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ?";
    $bind_params[] = $tgl_mulai;
    $bind_params[] = $tgl_selesai;
    $bind_types = "ss";
}


// Query untuk mengambil data laporan penjualan (detail per item)
$sql = "SELECT 
            t.tanggal_transaksi,
            m.nama AS menu_nama,
            dt.jumlah,
            dt.harga_satuan,
            dt.total_harga_item,
            t.uang_dibayar,
            t.kembalian
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
        // Menggunakan call_user_func_array untuk bind_param
        // ini lebih fleksibel daripada ...$bind_params di PHP versi lama atau jika PHP tidak mendukung unpack operator
        $stmt->bind_param($bind_types, ...$bind_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Siapkan header untuk file Excel
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=laporan_penjualan_" . date('Ymd', strtotime($tgl_mulai)) . "_sd_" . date('Ymd', strtotime($tgl_selesai)) . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // Mulai output tabel HTML yang akan dibaca sebagai Excel
    echo "<table border='1' cellpadding='4' cellspacing='0'>"; // Tambahkan border di sini
    echo "<tr>";
    // Merge cell untuk judul utama
    echo "<th colspan='7' style='text-align:center; background-color:#F0F0F0; font-weight:bold; font-size:16px; padding:8px;'>LAPORAN PENJUALAN RM AMPERA ABBEEY</th>";
    echo "</tr>";
    echo "<tr>";
    // Merge cell untuk periode
    echo "<th colspan='7' style='text-align:center; background-color:#F0F0F0; font-weight:bold; font-size:12px; padding:5px;'>Periode: " . htmlspecialchars(date('d/m/Y', strtotime($tgl_mulai))) . " s/d " . htmlspecialchars(date('d/m/Y', strtotime($tgl_selesai))) . "</th>";
    echo "</tr>";
    echo "<tr><td colspan='7' style='height:10px;'></td></tr>"; // Baris kosong sebagai pemisah

    // Header Kolom Tabel Data
    echo "<tr style='background-color:#E0E0E0; font-weight:bold; text-align:center;'>
            <th style='width: 150px;'>Tanggal & Waktu</th>
            <th style='width: 200px;'>Nama Menu</th>
            <th style='width: 80px;'>Jumlah</th>
            <th style='width: 120px;'>Harga Satuan (Rp)</th>
            <th style='width: 150px;'>Total Harga Item (Rp)</th>
            <th style='width: 150px;'>Uang Dibayar (Transaksi)</th>
            <th style='width: 150px;'>Kembalian (Transaksi)</th>
          </tr>";

    $total_penjualan_excel = 0;
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='text-align:left;'>" . date('d/m/Y H:i:s', strtotime($row['tanggal_transaksi'])) . "</td>";
            echo "<td style='text-align:left;'>" . htmlspecialchars($row['menu_nama']) . "</td>";
            echo "<td style='text-align:center;'>" . htmlspecialchars($row['jumlah']) . "</td>";
            echo "<td style='text-align:right;'>" . number_format($row['harga_satuan'], 0, ',', '.') . "</td>";
            echo "<td style='text-align:right;'>" . number_format($row['total_harga_item'], 0, ',', '.') . "</td>";
            echo "<td style='text-align:right;'>" . number_format($row['uang_dibayar'], 0, ',', '.') . "</td>";
            echo "<td style='text-align:right;'>" . number_format($row['kembalian'], 0, ',', '.') . "</td>";
            echo "</tr>";
            $total_penjualan_excel += $row['total_harga_item'];
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center;'>Tidak ada data penjualan untuk periode yang dipilih.</td></tr>";
    }
    echo "<tr><td colspan='7' style='height:10px;'></td></tr>"; // Baris kosong
    echo "<tr>";
    echo "<td colspan='4' style='text-align:right; font-weight:bold; background-color:#F0F0F0; padding:8px;'>TOTAL PENJUALAN PERIODE:</td>";
    echo "<td colspan='3' style='font-weight:bold; text-align:left; background-color:#F0F0F0; padding:8px;'>" . number_format($total_penjualan_excel, 0, ',', '.') . "</td>";
    echo "</tr>";
    echo "</table>";

    $stmt->close();
} else {
    // Jika ada error pada prepare statement
    $_SESSION['error_message'] = "Terjadi kesalahan saat menyiapkan export data: " . $conn->error;
    header("Location: laporan_penjualan.php");
    exit();
}

$conn->close();
?>