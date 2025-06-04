<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    echo "ID transaksi tidak ditemukan.";
    exit;
}

$id = intval($_GET['id']);

$conn = new mysqli("localhost", "root", "", "rmampera_db");

$conn->query("DELETE FROM penjualan WHERE id=$id");

header("Location: transaksi_list.php");
exit;
