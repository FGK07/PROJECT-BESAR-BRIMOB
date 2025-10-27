<?php
// memanggil file autoload.php
require __DIR__ . '/vendor/autoload.php';

// cari file .env di direktori yang sama 
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ );

// Membaca isi file .env
$dotenv->load();

// membuat object client dari class google client / library goople api client
$client = new Google\Client();

// set client id dari file .env
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);

// set client id secret file .env
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);

//set redirect URI (URL tujuan setelah login google berhasil)
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);

// scope untuk meminta akses email user
$client ->addScope("email");

// scope untuk meminta akses profil (nama, foto, dsb)
$client ->addScope("profile");
?>