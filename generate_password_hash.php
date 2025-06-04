<?php
// generate_password_hash.php
$password = "admin123"; // Ini password plaintext yang Anda inginkan
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo "Password '{$password}' di-hash menjadi: " . $hashed_password;
?>