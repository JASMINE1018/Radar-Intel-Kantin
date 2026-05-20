# Presentasi Berbasis Role - Intel Kantin

## Slide 1 - Judul
**Intel Kantin SMKN 1 Surabaya**  
Pendekatan pengembangan berbasis peran: UI/UX Designer, Frontend Developer, Backend Developer, dan Fullstack

---

## Slide 2 - Tujuan Presentasi
Presentasi ini menjawab:
- Kenapa setiap role dibutuhkan dalam proyek Intel Kantin
- Bagaimana tiap role bekerja dari proses sampai hasil
- Apa output konkret dari tiap role
- Bagaimana semuanya terhubung menjadi solusi fullstack

---

## Slide 3 - Gambaran Produk
Intel Kantin adalah website kantin sekolah yang mendukung multi-peran:
- User: melihat menu, memberi rating/review
- Seller: mengelola stand dan menu
- Owner: approval seller dan monitoring sistem
- Admin: operasional dan kontrol teknis

Fokus produk:
- Informasi menu yang mudah diakses
- Alur manajemen yang rapi
- Data terpusat melalui sistem web + API + database

---

## Slide 4 - UI/UX Designer (Mengapa)
**Mengapa role UI/UX penting?**

Karena masalah utama pengguna bukan hanya fitur, tapi pengalaman:
- User bingung memilih menu jika informasi tidak jelas
- Seller kesulitan jika dashboard terlalu rumit
- Owner butuh panel yang cepat dibaca untuk ambil keputusan

Tujuan UI/UX:
- Mengurangi kebingungan pengguna
- Mempercepat task utama di setiap role
- Menciptakan pengalaman yang konsisten dan mudah dipahami

---

## Slide 5 - UI/UX Designer (Bagaimana)
**Bagaimana proses UI/UX dijalankan?**

1. Memetakan persona: user, seller, owner, admin
2. Menyusun user journey dari landing page sampai dashboard
3. Mendesain information architecture (navigasi dan hierarki konten)
4. Membuat wireframe lalu high-fidelity mockup
5. Validasi cepat: apakah flow login, lihat menu, kelola stand sudah intuitif

Prinsip desain yang dipakai:
- Clarity over complexity
- Task-first layout
- Visual feedback untuk aksi penting (status, error, sukses)

---

## Slide 6 - UI/UX Designer (Apa)
**Apa output UI/UX dalam proyek ini?**

- Struktur halaman publik: beranda, tentang, menu/katalog
- Struktur autentikasi multi-role: user/seller/owner
- Pola dashboard seller dan owner berbasis sidebar + section
- Komponen reusable: card menu, table data, status badge, form modal

Contoh implementasi desain terlihat pada:
- [index.php](index.php)
- [login.php](login.php)
- [dashboard.php](dashboard.php)
- [owner_panel.php](owner_panel.php)

---

## Slide 7 - Frontend Developer (Mengapa)
**Mengapa frontend krusial?**

Karena frontend adalah titik interaksi langsung pengguna dengan sistem.

Tanpa frontend yang baik:
- Fitur backend tidak terasa manfaatnya
- Pengguna cepat salah klik atau gagal menyelesaikan tugas
- Pengalaman antar halaman menjadi tidak konsisten

Peran frontend: menerjemahkan desain menjadi antarmuka interaktif yang responsif.

---

## Slide 8 - Frontend Developer (Bagaimana)
**Bagaimana frontend dibangun?**

1. Implementasi layout halaman dan navigasi
2. Styling visual dengan CSS sesuai hierarki informasi
3. Interaksi dasar dengan JavaScript (toggle menu, filter, feedback)
4. Integrasi form login/register dan dashboard state
5. Menjaga responsive behavior desktop dan mobile

Contoh area kerja frontend:
- Landing page dan catalog experience di [index.php](index.php)
- Interaksi dan style global di [assets/js/script.js](assets/js/script.js) dan [assets/css/style.css](assets/css/style.css)
- Halaman autentikasi di [login.php](login.php)

---

## Slide 9 - Frontend Developer (Apa)
**Apa output frontend?**

- Halaman yang bisa digunakan end-user secara nyata
- Komponen visual yang konsisten antar fitur
- Flow interaktif yang membuat user menyelesaikan task lebih cepat

Deliverable frontend pada proyek:
- Public pages (beranda, menu, informasi)
- Seller dashboard UI
- Owner panel UI
- Admin UI basic

---

## Slide 10 - Backend Developer (Mengapa)
**Mengapa backend dibutuhkan?**

Karena sistem membutuhkan logika, keamanan, dan pengelolaan data.

Tanpa backend:
- Login tidak bisa memverifikasi akun
- Data menu/review tidak tersimpan konsisten
- Hak akses role tidak bisa dibedakan

Backend memastikan website bukan sekadar tampilan, tetapi aplikasi yang benar-benar bekerja.

---

## Slide 11 - Backend Developer (Bagaimana)
**Bagaimana backend bekerja pada Intel Kantin?**

1. Menyusun skema data: users, sellers, owners, stands, menu_items, reviews
2. Membangun autentikasi berbasis session
3. Menentukan authorization per role (user/seller/owner/admin)
4. Menyediakan endpoint API untuk CRUD data
5. Menangani validasi input, status akun, dan respons error

Contoh endpoint backend:
- [api/menu.php](api/menu.php)
- [api/item_crud.php](api/item_crud.php)
- [api/stand_crud.php](api/stand_crud.php)
- [api/review.php](api/review.php)
- [api/rate.php](api/rate.php)
- [api/owner_action.php](api/owner_action.php)

---

## Slide 12 - Backend Developer (Apa)
**Apa output backend?**

- Sistem login multi-role yang berfungsi
- Data stand dan menu yang bisa dikelola seller
- Mekanisme approval seller oleh owner
- Sistem rating/review yang tersimpan dan dapat dianalisis

Pusat konfigurasi backend/database:
- [config.php](config.php)
- [config/database.php](config/database.php)
- [config/koneksi.php](config/koneksi.php)

---

## Slide 13 - Integrasi Frontend + Backend
Nilai utama proyek muncul saat dua sisi ini menyatu:

Alur integrasi:
1. Frontend kirim request (form/aksi pengguna)
2. Backend validasi role, session, dan data
3. Database menyimpan atau mengambil data
4. Backend kirim response
5. Frontend menampilkan status terbaru ke pengguna

Contoh integrasi nyata:
- Login role-based di [login.php](login.php)
- Data management seller di [dashboard.php](dashboard.php)
- Approval seller di [owner_panel.php](owner_panel.php)

---

## Slide 14 - Fullstack (Peran Penyatu)
**Apa itu fullstack pada konteks proyek ini?**

Fullstack adalah peran yang memahami dan menyambungkan:
- Perspektif pengguna (UX)
- Implementasi antarmuka (frontend)
- Logika + data + keamanan (backend)

Tanggung jawab fullstack:
- Menjaga alur fitur tetap utuh dari UI ke database
- Memastikan perubahan di satu sisi tidak merusak sisi lain
- Mempercepat delivery karena memahami konteks end-to-end

---

## Slide 15 - Mengapa Fullstack Menjadi Penutup
Fullstack menjadi penutup karena ini tahap integrasi hasil semua role.

Hasil akhirnya bukan sekadar:
- UI yang bagus, atau
- API yang lengkap,

Tetapi produk yang benar-benar dipakai dan memberi dampak:
- User nyaman memilih menu
- Seller mudah mengelola stand
- Owner cepat memantau kualitas layanan
- Sistem siap dikembangkan ke tahap berikutnya

---

## Slide 16 - Kesimpulan
Proyek Intel Kantin menunjukkan bahwa produk digital yang baik lahir dari kolaborasi role:
1. UI/UX menentukan arah pengalaman pengguna
2. Frontend menerjemahkan desain menjadi interaksi nyata
3. Backend membangun logika, data, dan keamanan
4. Fullstack menyatukan semuanya menjadi sistem end-to-end

**Intinya: satu produk, banyak peran, satu tujuan - pengalaman kantin sekolah yang lebih baik.**
