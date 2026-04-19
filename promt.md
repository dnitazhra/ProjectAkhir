Mantap, ini udah kayak PRD beneran 👍
Aku bantu bikinin **struktur PRD (Proyek Requirement Development)** yang rapi + siap kamu pakai buat tugas atau proyek.

---

# 📄 PRD WEBSITE TOKO ONLINE

## 1. 📌 Deskripsi Umum

Website toko online berbasis **PHP Native + MySQL (XAMPP)** yang memungkinkan pengguna membeli produk secara online dan admin mengelola sistem.

---

## 2. 🎯 Tujuan

* Memudahkan user membeli produk secara online
* Memudahkan admin mengelola produk, pesanan, dan user
* Menyediakan sistem transaksi sederhana tapi lengkap

---

## 3. 👤 Role Pengguna

### 1. Admin

* Login ke sistem
* Mengelola data website

### 2. User (Pelanggan)

* Registrasi & login
* Melakukan pembelian produk

---

## 4. 🔐 Fitur Autentikasi

### User:

* Register
* Login
* Logout

### Admin:

* Login
* Logout

---

## 5. 🧑‍💻 Fitur Admin

### 1. Dashboard

Menampilkan:

* Total penjualan
* Jumlah pesanan baru
* Produk aktif
* Jumlah user

---

### 2. Manajemen Pengguna

* Melihat data user
* Menghapus user (opsional)
* Edit data user (opsional)

---

### 3. Manajemen Pesanan

* Melihat semua pesanan
* Update status pesanan:

  * Pending
  * Diproses
  * Dikirim
  * Selesai

---

### (Opsional tapi bagus kalau ditambah)

### 4. Manajemen Produk

* Tambah produk
* Edit produk
* Hapus produk
* Upload gambar produk
* Atur kategori

---

## 6. 🛍️ Fitur User

### 1. Halaman Utama

* Banner promosi
* Kategori produk
* Produk rekomendasi

---

### 2. Navigasi

* Sidebar kanan/kiri:

  * Home
  * Kategori
  * Keranjang
  * Pesanan
  * Profil

---

### 3. Kategori Produk

* Produk ditampilkan berdasarkan kategori
* Contoh:

  * Snack
  * Minuman
  * Dessert

---

### 4. Detail Produk

* Nama produk
* Harga
* Deskripsi
* Gambar
* Tombol "Tambah ke Keranjang"

---

### 5. Keranjang Belanja

* List produk yang dipilih
* Jumlah item
* Total harga
* Hapus item

---

### 6. Checkout

* Input alamat
* Pilih metode pembayaran (opsional)
* Konfirmasi pesanan

---

### 7. Detail Pesanan

* Status pesanan
* Daftar produk
* Total harga

---

### 8. Ulasan

* User bisa kasih rating
* User bisa kasih komentar produk

---

## 7. 🗄️ Struktur Database (Sederhana)

### Tabel user

* id_user
* nama
* email
* password
* role (admin/user)

---

### Tabel produk

* id_produk
* nama_produk
* harga
* deskripsi
* gambar
* kategori

---

### Tabel pesanan

* id_pesanan
* id_user
* tanggal
* total_harga
* status

---

### Tabel detail_pesanan

* id_detail
* id_pesanan
* id_produk
* jumlah
* subtotal

---

### Tabel ulasan

* id_ulasan
* id_user
* id_produk
* rating
* komentar

---

## 8. 🔄 Alur Sistem

### User Flow:

1. Register / Login
2. Pilih produk
3. Masukkan ke keranjang
4. Checkout
5. Lihat status pesanan
6. Beri ulasan

---

### Admin Flow:

1. Login
2. Lihat dashboard
3. Kelola pesanan
4. Kelola user / produk

---

## 9. 🎨 UI/UX (Figma)

* Desain mengikuti file Figma yang kamu punya
* Warna, layout, dan komponen harus konsisten
* Responsive (kalau bisa)

---

## 10. ⚙️ Teknologi

* Frontend: HTML, CSS, (opsional JS)
* Backend: PHP Native
* Database: MySQL
* Server: XAMPP

---

## 11. 🚀 Catatan Pengembangan

* Gunakan session untuk login
* Password pakai hashing (`password_hash`)
* Pisahkan file:

  * config (koneksi DB)
  * auth
  * user
  * admin

---

Kalau kamu mau, next step aku bisa bantu:
✅ Bikin **flow diagram / use case**
✅ Bikin **struktur folder project PHP**
✅ Bikin **database SQL langsung**
✅ Atau **ubah PRD ini jadi laporan tugas yang lebih formal**

Tinggal bilang aja 👍
