<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "happy_snack";

$conn = @mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    // Coba connect tanpa database dulu, lalu buat database-nya
    $conn_temp = @mysqli_connect($host, $user, $pass);
    if ($conn_temp) {
        mysqli_query($conn_temp, "CREATE DATABASE IF NOT EXISTS `happy_snack` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        mysqli_close($conn_temp);
        $conn = mysqli_connect($host, $user, $pass, $db);
    }
    if (!$conn) {
        die("Koneksi gagal: " . mysqli_connect_error());
    }
}

mysqli_set_charset($conn, "utf8mb4");
?>