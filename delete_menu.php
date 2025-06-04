<?php
session_start();
// Pastikan admin sudah login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php"); // Redirect ke login.php jika belum login
    exit();
}

require_once 'db_config.php'; // Menggunakan file koneksi database terpusat

// Pastikan ID menu ada di URL dan merupakan angka
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID menu tidak valid atau tidak ditemukan untuk dihapus.";
    header("Location: dashboard.php");
    exit();
}

$id_menu_to_delete = intval($_GET['id']); // Pastikan ID adalah integer

// Gunakan prepared statement untuk DELETE
$sql_delete_menu = "DELETE FROM menu WHERE id = ?";
$stmt_delete_menu = $conn->prepare($sql_delete_menu);

if ($stmt_delete_menu) {
    $stmt_delete_menu->bind_param("i", $id_menu_to_delete); // i: integer
    if ($stmt_delete_menu->execute()) {
        if ($stmt_delete_menu->affected_rows > 0) {
            $_SESSION['success_message'] = "Menu dengan ID " . htmlspecialchars($id_menu_to_delete) . " berhasil dihapus!";
        } else {
            $_SESSION['error_message'] = "Menu dengan ID " . htmlspecialchars($id_menu_to_delete) . " tidak ditemukan.";
        }
    } else {
        $_SESSION['error_message'] = "Gagal menghapus menu: " . $stmt_delete_menu->error;
    }
    $stmt_delete_menu->close();
} else {
    $_SESSION['error_message'] = "Gagal menyiapkan query hapus: " . $conn->error;
}

$conn->close(); // Tutup koneksi setelah semua operasi selesai

header("Location: dashboard.php"); // Redirect kembali ke dashboard
exit();
?>