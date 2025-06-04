<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.html");
    exit;
}

$conn = new mysqli("localhost", "root", "", "rmampera_db");

// Ambil data menu untuk dropdown
$menuResult = $conn->query("SELECT * FROM menu");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tanggal = $_POST['tanggal'];
    $menu_id = $_POST['menu_id'];
    $jumlah = $_POST['jumlah'];

    // Ambil harga menu
    $menu = $conn->query("SELECT harga FROM menu WHERE id=$menu_id")->fetch_assoc();
    $total_harga = $menu['harga'] * $jumlah;

    $conn->query("INSERT INTO penjualan (tanggal, menu_id, jumlah, total_harga) VALUES ('$tanggal', $menu_id, $jumlah, $total_harga)");

    header("Location: laporan_penjualan.php");
    exit;
}
?>

<h2>Tambah Data Penjualan</h2>
<form method="POST" action="">
  <label>Tanggal:</label><br>
  <input type="date" name="tanggal" required><br>
  <label>Menu:</label><br>
  <select name="menu_id" required>
    <?php while($menu = $menuResult->fetch_assoc()): ?>
      <option value="<?= $menu['id'] ?>"><?= htmlspecialchars($menu['nama']) ?></option>
    <?php endwhile; ?>
  </select><br>
  <label>Jumlah:</label><br>
  <input type="number" name="jumlah" required><br><br>
  <button type="submit">Simpan</button>
</form>

<a href="dashboard.php">Kembali ke Dashboard</a>
