<?php
// Konfigurasi Database
$servername = "localhost"; // Biasanya 'localhost' untuk server lokal seperti XAMPP
$username = "root";       // Username default untuk MySQL di XAMPP/WAMP/MAMP
$password = "";           // Password default kosong untuk MySQL di XAMPP/WAMP/MAMP
$dbname = "rmampera_db";  // Nama database yang sudah Anda buat di phpMyAdmin

// Buat koneksi ke database menggunakan MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Set charset ke utf8mb4 (disarankan untuk dukungan emoji dan karakter khusus)
$conn->set_charset("utf8mb4");

// Anda bisa menambahkan baris ini untuk debugging, tapi hapus di produksi
// echo "Koneksi database berhasil!";

?>