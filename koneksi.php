<?php

$servername = "localhost";
$username = "root";
$password = "";
$database = "toko_online";
$port = "3307";

$koneksi = new mysqli($servername, $username, $password, $database, $port);

if ($koneksi->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

date_default_timezone_set('Asia/Jakarta');
mysqli_query($koneksi, "SET time_zone = '+07:00'");

?>