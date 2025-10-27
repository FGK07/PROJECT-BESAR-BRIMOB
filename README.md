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

## 🚀 Instalasi Lengkap
composer install
npm install
cp .env.example .env
php -S localhost:8000
npm run dev
BrimobSport/
├── admin/               # Panel Admin & manajemen produk
├── user/                # Halaman utama pengguna
├── produk/              # Tampilan produk & kategori
├── auth/                # Sistem login/register
├── uploads/             # Gambar produk & bukti transfer
├── src/                 # Sumber frontend (TailwindCSS/Vite)
├── config/              # Koneksi database & konstanta
├── vendor/              # Dependensi Composer
└── index.php




### 1️⃣ Clone Repository
```bash
git clone https://github.com/FerdianEgha/BrimobSport.git
cd BrimobSport
