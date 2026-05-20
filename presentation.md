# Presentasi Website Intel Kantin

## Slide 1 - Judul
**Intel Kantin SMKN 1 Surabaya**  
Website informasi dan manajemen kantin sekolah

Disusun untuk memaparkan:
- Awal mula pengembangan website
- Alur penggunaan website dari sisi user, seller, owner, dan admin

---

## Slide 2 - Latar Belakang
Permasalahan awal di lingkungan kantin:
- Siswa sering tidak tahu menu apa yang tersedia sebelum ke kantin
- Informasi stok dan harga belum terpusat
- Proses penilaian stand dan menu masih manual
- Pemilik stand membutuhkan dashboard sederhana untuk mengelola menu

Website Intel Kantin dibuat sebagai solusi digital agar informasi kantin lebih cepat, rapi, dan mudah diakses.

---

## Slide 3 - Awal Mulanya Proyek
Ide awal proyek berangkat dari kebutuhan internal sekolah:
- Membuat platform katalog jajanan kantin berbasis web
- Menyediakan akses multi-peran: **user, seller, owner, admin**
- Menyatukan data stand, menu, ulasan, dan rating dalam satu sistem

Tahapan pengembangan awal:
1. Membuat landing page dan halaman katalog/menu
2. Menambahkan autentikasi (login/register)
3. Mengembangkan dashboard seller untuk kelola stand dan menu
4. Mengembangkan panel owner untuk approval seller dan monitoring
5. Menyiapkan API untuk integrasi CRUD data

---

## Slide 4 - Tujuan Website
Tujuan utama Intel Kantin:
- Memudahkan siswa melihat dan memilih menu kantin
- Membantu seller mengelola stand dan item menu secara mandiri
- Memberi owner kontrol terhadap seller, stand, dan kualitas layanan
- Menghadirkan ekosistem kantin sekolah yang lebih transparan

---

## Slide 5 - Struktur Umum Sistem
Komponen inti website:
- **Frontend**: halaman publik, login, dashboard, panel owner/admin
- **Backend PHP**: proses autentikasi, session, validasi, business logic
- **Database MySQL**: users, sellers, owners, stands, menu_items, reviews
- **API Layer**: endpoint CRUD dan aksi penting (menu, stand, tray, review, rating)

Direktori penting:
- `index.php`, `page2.php`: halaman publik dan katalog
- `login.php`: login multi-role (user/seller/owner)
- `dashboard.php`: seller dashboard
- `owner_panel.php`: owner panel
- `api/`: endpoint backend

---

## Slide 6 - Alur Website (Gambaran Besar)
Alur end-to-end:
1. Pengunjung membuka **landing page**
2. Pengunjung melihat informasi kantin dan daftar menu
3. Pengguna memilih peran lalu login/register
4. Sistem mengarahkan user ke halaman sesuai role:
	- User -> halaman menu/tray/review
	- Seller -> dashboard seller
	- Owner -> panel owner
	- Admin -> panel admin
5. Semua aktivitas data diproses melalui endpoint API dan database

---

## Slide 7 - Alur Pengguna (User)
Alur dari sisi siswa/pembeli:
1. User membuka halaman menu (`page2.php`)
2. User login atau register jika belum punya akun
3. User melihat daftar stand dan item menu
4. User menambahkan item ke tray/keranjang
5. User memberi rating dan review setelah mencoba menu

Output yang didapat user:
- Informasi menu lebih cepat
- Bisa membandingkan stand berdasarkan rating/review

---

## Slide 8 - Alur Seller
Alur dari sisi penjual:
1. Seller mendaftar akun (`seller_register.php`)
2. Status akun masuk **pending** menunggu approval owner
3. Setelah disetujui, seller login (`login.php` role seller atau `seller_login.php`)
4. Seller mengakses `dashboard.php`
5. Seller mengelola stand, menu item, profil, dan memantau review

Nilai tambah untuk seller:
- Kontrol data stand secara mandiri
- Monitoring performa menu dari ulasan user

---

## Slide 9 - Alur Owner
Alur dari sisi owner:
1. Owner login ke `owner_panel.php`
2. Owner melihat statistik utama (users, sellers, stands, reviews)
3. Owner melakukan approval/reject seller baru
4. Owner memantau stand, data pengguna, dan aktivitas platform
5. Owner melakukan manajemen profil dan pengaturan panel

Peran owner adalah quality control dan governance platform.

---

## Slide 10 - Alur Admin
Alur dari sisi admin:
- Login melalui modul `admin/`
- Memantau dashboard admin
- Menjaga stabilitas sistem dan pengelolaan operasional

Admin berperan sebagai penjaga operasional teknis harian.

---

## Slide 11 - Alur Data dan API
Contoh endpoint API yang digunakan:
- `api/menu.php`, `api/get_item.php` -> akses data menu
- `api/item_crud.php`, `api/stand_crud.php` -> CRUD item dan stand
- `api/review.php`, `api/rate.php` -> ulasan dan rating
- `api/tray.php` -> manajemen tray/keranjang
- `api/owner_action.php` -> approval/reject seller

Ringkas alur data:
1. UI mengirim request ke API
2. API memvalidasi input dan session
3. API membaca/menulis ke MySQL
4. Response dikirim kembali ke UI

---

## Slide 12 - Dampak dan Manfaat
Manfaat implementasi Intel Kantin:
- Siswa lebih mudah menentukan pilihan makanan
- Seller lebih efisien mengelola menu
- Owner memiliki kontrol dan visibilitas lebih baik
- Sekolah memiliki digitalisasi layanan kantin yang terstruktur

---

## Slide 13 - Rencana Pengembangan Lanjutan
Pengembangan berikutnya yang disarankan:
1. Integrasi notifikasi stok habis dan rekomendasi menu
2. Peningkatan keamanan autentikasi (hashing konsisten, hardening session)
3. Laporan analitik penjualan per stand
4. UI/UX mobile-first yang lebih interaktif
5. Integrasi pembayaran digital (opsional)

---

## Slide 14 - Penutup
Intel Kantin bukan hanya katalog makanan, tetapi fondasi ekosistem digital kantin sekolah.

Dengan alur multi-peran yang jelas, website ini membantu:
- Pengguna mendapatkan informasi cepat
- Seller mengelola usaha lebih rapi
- Owner dan admin menjaga kualitas layanan

**Terima kasih.**
