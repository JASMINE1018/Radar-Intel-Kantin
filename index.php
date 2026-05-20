<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Intel Kantin</title>
<?php $css_v = file_exists(__DIR__ . '/assets/css/style.css') ? filemtime(__DIR__ . '/assets/css/style.css') : time(); ?>
<link rel="stylesheet" href="assets/css/style.css?v=<?=$css_v?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Oxanium:wght@400;600;700;800&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">

</head>
<body>
<!-- -- Navigasi utama halaman landing -- -->
<nav>
  <div class="nav-logo">
    <div class="logo-icon"><img src = "assets/img/logosmkn-transparent.png" height = "50px"></div>
    <div>
      <div>Intel</div>
      <div class="sub">Kantin</div>
    </div>
  </div>
  <ul class="nav-links">
    <li><a href="#home"></a></li>
    <li><a href="#about"></a></li>
    <li><a href="#gallery"></a></li>
  </ul>
  <a href="login.php" class="btn-join desktop">Daftar</a>
  <button class="hamburger" id="hamburger" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
</nav>

<!-- -- Menu mobile halaman landing -- -->
<div class="mobile-menu" id="mobileMenu">
  <a href="#home">Beranda</a>
  <a href="page2.php">Pesan Sekarang</a>
  <a href="#">Credit</a>
  <!-- <a href="#" class="btn-join">Join Us</a> -->
</div>

<!-- -- Hero utama halaman landing -- -->
<section class="hero" id="home">
  <div class="stars"></div>
  <div class="glow-ring"></div>
  <div class="geo geo-triangle"></div>
  <div class="geo geo-triangle2"></div>
  <div class="geo geo-chevron"></div>

  <div class="hero-inner">
    <div class="hero-left">
      <div class="hero-badge">Welcome to Kantin</div>
      <h1 class="hero-title">
        <span class="line1">SMKN 1</span>
        <span class="line2">SURABAYA</span>
      </h1>
      <p class="hero-desc">
        Kantin <span>Jajanan Sekolah</span> SMKN 1 SURABAYA
      </p>
      <div class="hero-buttons">
        <a href="#about" class="btn-outline">Tentang Kami ›</a>
        <a href="page2.php" class="btn-outline">Pesan sekarang ›</a>
        <a href="#gallery" class="btn-primary">"Halo warga kantin Smeas"</a>
      </div>
    </div>

    <div class="hero-right">
      <div class="char-frame">
        <div class="char-svg">
          <!-- <img id="heroImg" src="cara-membuat-mie-ayam-4-removebg-preview.png" alt="" style="width: 150%; height: 150%;"/> -->
        </div>
      </div>
    </div>
  </div>
</section>

<!-- -- Section informasi tentang kantin -- -->
<section class="intro" id="about">
  <div class="intro-inner">
    <div class="intro-tag">✦ Tentang Kami</div>
    <h2 class="intro-heading">
      Radar Intel Kantin<br>
      <span class="hl">SMKN 1 SURABAYA</span>
    </h2>

    <div class="intro-body">
      <div>
        <div class="intro-text">
          <p><strong>Intel Kantin</strong> menyediakan<strong> JAJANAN ENAK  </strong> yang berbasis aplikasi tanpa Internet</p>
          <p style="margin-top:16px">Gak cuma sekadar jualan, info stok jajanan paling update, sampai jadi temen mabar (makan bareng) yang suportif biar gak sendirian di pojokan kantin.</p>
          <p style="margin-top:16px">Kalian Baru di SMKN 1 Surabaya? Tenang aja Kami bisa bantu kamu pilih jajan di kantin sebelum datengin kantin nya!!</p>
        </div>
        <!-- <a href="#" class="intro-cta">Gabung Sekarang →</a> -->
      </div>

      <div class="intro-stats">
        <div class="stat-card featured">
          <div class="stat-num">500<span class="unit">+</span></div>
          <div class="stat-label">Total Pengguna Aktif</div>
          <div class="stat-desc">dan terus bertumbuh setiap harinya</div>
        </div>
        <div class="stat-card">
          <div class="stat-num">50+ <span class="unit">Tahun</span></div>
          <div class="stat-label">Berdiri Sejak</div>
        </div>
        <div class="stat-card">
          <div class="stat-num">13<span class="unit">+</span></div>
          <div class="stat-label">Stand Tersedia!!!</div>
        </div>
      </div>
    </div>
  </section>

  <section class="benefits" id="benefits">
    <div class="section-header">
      <p class="section-label">Kenapa Pilih Intel Kantin?</p>
      <h2>Mudah, cepat, dan selalu tersedia</h2>
    </div>
    <div class="benefit-grid">
      <article class="benefit-card">
        <div class="benefit-icon icon-internet" aria-hidden="true"></div>
        <h3>Tanpa Internet</h3>
        <p>Bisa digunakan tanpa koneksi internet.</p>
      </article>
      <article class="benefit-card">
        <div class="benefit-icon icon-realtime" aria-hidden="true"></div>
        <h3>Info Real-time</h3>
        <p>Stok jajanan selalu update sehingga kamu tidak ketinggalan.</p>
      </article>
      <article class="benefit-card">
        <div class="benefit-icon icon-friend" aria-hidden="true"></div>
        <h3>Teman Mabar</h3>
        <p>Temukan teman makan bareng dan nikmati kantin bersama.</p>
      </article>
    </div>
  </section>

  <section class="how-it-works">
    <div class="section-header">
      <p class="section-label">Cara Kerja</p>
    </div>
    <div class="steps-grid">
      <article class="step-card">
        <div class="step-number">1</div>
        <h3>Buka aplikasi</h3>
        <p>Buka Intel Kantin dan lihat pilihan jajanan yang tersedia.</p>
      </article>
      <article class="step-card">
        <div class="step-number">2</div>
        <h3>Pilih jajanan</h3>
        <p>Pilih menu favoritmu dan cek ketersediaan secara langsung.</p>
      </article>
      <article class="step-card">
        <div class="step-number">3</div>
        <h3>Datang & ambil</h3>
        <p>Datang ke kantin dan ambil pesananmu dengan cepat.</p>
      </article>
    </div>
  </section>

  <footer class="site-footer">
    <div class="footer-inner">
      <div class="footer-brand">
        <div class="footer-logo"></div>
        <div>
          <div class="footer-title">Intel Kantin</div>
          <div class="footer-subtitle">SMKN 1 Surabaya</div>
        </div>
      </div>
      <div class="footer-links">
        <a href="#home">Beranda</a>
        <a href="#about">Tentang</a>
        <a href="#benefits">Kenapa</a>
        <a href="#">Hubungi</a>
      </div>
      <p class="footer-note">© 2026 Intel Kantin. Platform info jajanan tanpa internet untuk SMKN 1 Surabaya.</p>
    </div>
  </footer>

  <script src="assets/js/script.js"></script>
</body>
</html>