<?php
session_start(); // Memulai sesi
session_unset(); // Menghapus semua variabel sesi
session_destroy(); // Menghancurkan sesi

header("Location: login.php"); // Mengarahkan pengguna kembali ke halaman login.php
exit(); // Penting untuk menghentikan eksekusi script setelah redirect
?>