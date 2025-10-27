<h1 align="center">🏃‍♂️ Brimob Sport</h1>

<p align="center">
  <i>Website e-commerce perlengkapan olahraga modern berbasis PHP & MySQL, dengan panel Admin, manajemen stok ukuran, sistem pre-order, dan integrasi filament Laravel.</i><br>
  Dibangun dengan ❤️ menggunakan <b>Composer</b> untuk backend dan <b>npm + TailwindCSS</b> untuk frontend.
</p>

<p align="center">
  <a href="#">
    <img src="https://img.shields.io/badge/Project-Brimob%20Sport-blue?style=flat-square">
  </a>
  <a href="#">
    <img src="https://img.shields.io/badge/PHP-8.1%2B-777BB4?style=flat-square&logo=php&logoColor=white">
  </a>
  <a href="#">
    <img src="https://img.shields.io/badge/License-MIT-green?style=flat-square">
  </a>
  <a href="#">
    <img src="https://img.shields.io/badge/Build-Passing-brightgreen?style=flat-square&logo=githubactions&logoColor=white">
  </a>
  <a href="#">
    <img src="https://img.shields.io/badge/Made%20with-PHP%20%26%20TailwindCSS-blue?style=flat-square">
  </a>
</p>

---

## ✨ Tentang Brimob Sport

**Brimob Sport** adalah platform e-commerce yang dikembangkan untuk memudahkan pengguna membeli sepatu, perlengkapan lari, dan kebutuhan olahraga lainnya secara online.  
Aplikasi ini memiliki sistem **login multi-role (Admin & User)**, dukungan **pre-order produk**, serta **manajemen stok per ukuran** yang terintegrasi dengan sistem transaksi.

---

## 📦 Fitur Utama

- 🛒 **Sistem E-Commerce Lengkap:** Pengguna dapat melihat produk, menambahkan ke keranjang, melakukan checkout, dan pembayaran.
- 👤 **Login Multi-User:** Pemisahan akses untuk *Admin* dan *User*.
- 📦 **Manajemen Produk (ADMIN):** Tambah, ubah, hapus, dan kelola kategori, stok, serta ukuran produk.
- 🧾 **Sistem Transaksi & Pre-Order:** Admin dapat menyetujui/menolak pre-order, dan sistem otomatis mengatur stok.
- 💬 **Notifikasi Flash:** Memberi umpan balik langsung pada pengguna setelah setiap tindakan.
- 🔎 **Pencarian Produk Dinamis:** Filter berdasarkan kategori atau nama produk secara real-time.
- 🖼️ **Upload Gambar Produk:** Dukungan untuk gambar dengan pratinjau langsung.
- ⚙️ **Konfigurasi Mudah:** Struktur file sederhana dan mudah dipasang di XAMPP atau Laragon.

---

## 🧩 Persyaratan Sistem

| 🧰 Komponen | 💡 Versi Disarankan | 🔗 Keterangan |
|-------------|--------------------|---------------|
| 🐘 **PHP** | ≥ 8.1 | Backend utama |
| 🗄️ **MySQL** | 5.7 + | Basis data utama |
| 🎼 **Composer** | 2.x | Manajer dependensi PHP |
| 🟢 **Node.js & npm** | Node 18 +/ npm 9 + | Untuk frontend (TailwindCSS / Vite) |
| ⚙️ **Git & Laragon/XAMPP** | Terpasang | Untuk server lokal dan version control |

---

 🚀 Panduan Instalasi Lengkap Proyek Brimob Sport
 Dibuat oleh: Ferdian Egha Kuncoro & Belgi Setiawan
 
## 1️⃣ Clone repository dari GitHub
``` bash
git clone https://github.com/FerdianEgha/BrimobSport.git
```

##  Masuk ke folder project
``` bash
cd BrimobSport
```

## 2️⃣ Instal dependensi PHP (Backend)
 ``` bash
# Pastikan sudah install Composer terlebih dahulu:
# Unduh di: https://getcomposer.org/download/
composer install
```

## 3️⃣ Instal dependensi Frontend (Tailwind / npm)
``` bash
# Pastikan Node.js & npm sudah terpasang:
# Unduh di: https://nodejs.org/

npm install
```

## 4️⃣ Konfigurasi file .env
## Salin template .env.example ke .env baru
``` bash
cp .env.example .env
```
``` bash
# Kemudian ubah konfigurasi database sesuai MySQL kamu:
# ----------------------------------------------
# DB_HOST=localhost
# DB_PORT=3306
# DB_DATABASE=brimob_sport
# DB_USERNAME=root
# DB_PASSWORD=
# ----------------------------------------------
```

## 5️⃣ Migrasi database (Jika menggunakan Laravel / Filament)
## Jika menggunakan PHP Native, cukup import file SQL ke phpMyAdmin

``` bash
php artisan migrate --seed
```

## 6️⃣ Jalankan aplikasi (PHP Built-in Server)
```bash
php -S localhost:8000
```


## 7️⃣ Jalankan Frontend (Tailwind)
 ``` bash
npm run dev
```

## Untuk mode produksi (optimasi build)

```bash
npm run build
```

## 🧠 Tips Tambahan
 ✅ Gunakan Laragon agar mudah mengelola PHP, MySQL, dan Node.js
** 🔐 Pastikan file .env berada di root folder proyek
** 🔄 Jalankan `npm run build` setiap kali ada perubahan besar di frontend
** 💾 Backup database sebelum mengubah struktur tabel

## 🗂️ Struktur Folder Brimob Sport
``` bash
 BrimobSport/
 ├── admin/                 # Panel admin (kelola produk, transaksi, user)
 ├── user/                  # Tampilan user (homepage, keranjang, profil)
 ├── produk/                # Halaman produk & kategori
 ├── pre_order/             # Modul pre-order
 ├── auth/                  # Login & register
 ├── uploads/               # Gambar produk & bukti transfer
 ├── src/                   # Frontend (TailwindCSS, JS, assets)
 │   ├── css/
 │   └── js/
 ├── vendor/                # Dependensi Composer
 ├── config/                # Koneksi database & pengaturan dasar
 ├── .env.example           # Template konfigurasi environment
 ├── composer.json          # Dependensi PHP
 ├── package.json           # Dependensi npm
 └── index.php              # Entry point utama website
```

##  ⚙️ Teknologi yang Digunakan
``` bash
 Backend     : PHP 8.1+, MySQLi / PDO
 Frontend    : TailwindCSS, HTML, JavaScript
 Framework   : Laravel Filament (opsional)
 Autentikasi : PHP Sessions
 Tools       : Composer, Node.js, npm, Laragon/XAMPP
```

**Dibuat dengan 🔥 oleh [Ferdian Egha Kuncoro](https://github.com/FGK07) & [Belgi Setiawan]()**



