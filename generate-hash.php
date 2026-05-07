<?php
// Jalankan file ini SEKALI di browser: http://localhost/PROJECTAKHIR/generate-hash.php
// Lalu copy hash-nya ke database.sql

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h3>Hash untuk password: <code>$password</code></h3>";
echo "<p>Copy hash ini:</p>";
echo "<textarea style='width:100%;padding:10px;font-family:monospace;' rows='3'>$hash</textarea>";
echo "<hr>";
echo "<p>Lalu jalankan query ini di phpMyAdmin:</p>";
echo "<textarea style='width:100%;padding:10px;font-family:monospace;' rows='3'>";
echo "UPDATE user SET password='$hash' WHERE email='admin@happysnack.com';";
echo "</textarea>";
?>
