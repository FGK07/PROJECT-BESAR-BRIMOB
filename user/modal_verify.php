<?php
session_start();
include "../koneksi.php";
// var_dump($_SESSION['user']);
// echo '<img src="'.$_SESSION['user']['foto'].'">';
$result = $koneksi->query("SELECT nama, slug FROM kategori");
while ($row = $result->fetch_assoc()) {
    var_dump($row['nama']);
}
// <?= $_SESSION['user']['foto'] 

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <!-- <img src="" alt="" class="size-10 rounded-full"> -->
    <div class="flex flex-row gap-10">
        <?php
        $result = $koneksi->query("SELECT nama, slug FROM kategori");
        while ($row = $result->fetch_assoc()): ?>
            <a href="kategori_produk/kategori.php?nama=<?= urlencode($row['slug']) ?>"
                class="px-2 h-12 flex-1 rounded-2xl text-white font-semibold text-lg bg-gray-400 cursor-pointer hover:bg-gray-500 shadow-md hover:shadow-lg hover:scale-105 transition-transform duration-200 ease-in-out">
                <?= htmlspecialchars($row['nama']) ?>
            </a>
        <?php endwhile; ?>
    </div>

    <div class="flex flex-row gap-10">
        <?php while ($row = $result->fetch_assoc()): ?>
            <a href="kategori_produk/kategori.php?nama=<?= urlencode($row['slug']) ?>"
                class="px-2 h-12 flex-1 rounded-2xl text-white font-semibold text-lg bg-gray-400 cursor-pointer hover:bg-gray-500 shadow-md hover:shadow-lg hover:scale-105 transition-transform duration-200 ease-in-out">
                <?= htmlspecialchars($row['nama']) ?>
            </a>
        <?php endwhile; ?>
    </div>



</body>

</html>