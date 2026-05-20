<?php
require_once 'config.php';
require_once 'auth.php';

$user = currentUser();

$isUserLoggedIn = isset($_SESSION['user_id']);
$isSellerLoggedIn = isset($_SESSION['seller_id']);
$isOwnerLoggedIn = isset($_SESSION['owner_id']);
$isAnyLoggedIn = $isUserLoggedIn || $isSellerLoggedIn || $isOwnerLoggedIn;

$profileName = 'User';
$profileHref = 'page2.php';
$logoutHref = 'login.php';
$profileIconSrc = '';

if ($isOwnerLoggedIn) {
  $profileName = $_SESSION['owner_nama'] ?? 'Owner';
  $profileHref = 'owner_panel.php#setting';
  $logoutHref = 'api/owner_logout.php';

  $profileTable = $conn->query("SHOW TABLES LIKE 'owner_profiles'");
  if ($profileTable && $profileTable->num_rows > 0) {
    $ownerId = (int)$_SESSION['owner_id'];
    $profilePhotoRes = $conn->query("SELECT foto_profile FROM owner_profiles WHERE owner_id = $ownerId LIMIT 1");
    if ($profilePhotoRes && $profilePhotoRes->num_rows > 0) {
      $profilePhotoRow = $profilePhotoRes->fetch_assoc();
      $profileIconSrc = $profilePhotoRow['foto_profile'] ?? '';
    }
  }
} elseif ($isSellerLoggedIn) {
  $profileName = $_SESSION['seller_nama'] ?? 'Seller';
  $profileHref = 'dashboard.php#setting';
  $logoutHref = 'api/seller_logout.php';

  $profileTable = $conn->query("SHOW TABLES LIKE 'seller_profiles'");
  if ($profileTable && $profileTable->num_rows > 0) {
    $sellerId = (int)$_SESSION['seller_id'];
    $profilePhotoRes = $conn->query("SELECT foto_profile FROM seller_profiles WHERE seller_id = $sellerId LIMIT 1");
    if ($profilePhotoRes && $profilePhotoRes->num_rows > 0) {
      $profilePhotoRow = $profilePhotoRes->fetch_assoc();
      $profileIconSrc = $profilePhotoRow['foto_profile'] ?? '';
    }
  }
} elseif ($isUserLoggedIn) {
  $profileName = $_SESSION['user_nama'] ?? ($user['nama'] ?? 'User');
  $profileHref = 'myTray.php';
  $logoutHref = 'api/logout.php';
}

// Ambil semua stand dari DB + jumlah menu items
$result = $conn->query("
    SELECT s.*, COUNT(m.id) as item_count
    FROM stands s
    LEFT JOIN menu_items m ON m.stand_id = s.id
    GROUP BY s.id
    ORDER BY s.kategori, s.id ASC
");
$stands = [];
while ($row = $result->fetch_assoc()) $stands[] = $row;

// Rating user untuk semua stand (kalau login)
$userStandRatings = [];
if ($isUserLoggedIn || $isOwnerLoggedIn) {
    $uid = $isUserLoggedIn ? (int)$_SESSION['user_id'] : (int)$_SESSION['owner_id'];
    $r = $conn->query("SELECT stand_id, rating FROM ratings_stand WHERE user_id = $uid");
    while ($row = $r->fetch_assoc()) $userStandRatings[$row['stand_id']] = (int)$row['rating'];
}

$conn->close();

// Map foto berdasarkan urutan stand (image1.jpeg dst)
// Sesuaikan kalau nama file beda
$fotoMap = [1=>'image1.jpeg',2=>'image2.jpeg',3=>'image3 .jpeg',4=>'image4.jpeg',5=>'image5.jpeg'];

$emojiMap = ['berat'=>'🍛','ringan'=>'🧆','minuman'=>'🧋','dessert'=>'🧇'];
$labelMap = ['berat'=>'Makanan Berat','ringan'=>'Makanan Ringan','minuman'=>'Minuman','dessert'=>'Dessert'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu Kantin — School Cafeteria</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body, body * { font-family: 'Inter', sans-serif !important; }
:root {
  --bg: #f3efe6;
  --paper: #fffdf9;
  --paper-strong: #fff;
  --ink: #1f2329;
  --muted: #6e7884;
  --line: #e4ddd1;
  --brand: #4b601d;
  --brand-2: #6f8a34;
  --brand-soft: #eaf1de;
  --shadow: 0 18px 40px rgba(31, 35, 41, 0.12);
}
body {
  background: #ffffff;
  overflow-x: hidden;
  color: var(--ink);
}
nav {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 40px;
  background: rgba(255, 253, 249, 0.86);
  backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(139, 79, 50, 0.14);
}
.nav-logo {
  display: flex;
  align-items: center;
  gap: 10px;
  font-family: 'Space Grotesk', sans-serif;
  font-size: 13px;
  font-weight: 700;
  color: var(--ink);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  line-height: 1.2;
  text-decoration: none;
}
.logo-icon {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  /* background: linear-gradient(160deg, #23310d, #4b601d); */
  box-shadow: 0 10px 24px rgba(55, 32, 20, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}
.nav-logo .sub { color: var(--muted); }
.nav-links { display: flex; align-items: center; gap: 34px; list-style: none; }
.nav-links a {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  color: #7f7b74;
  letter-spacing: 0.16em;
  text-transform: uppercase;
  transition: color 0.2s;
}
.nav-links a:hover, .nav-links a.active { color: var(--ink); }
.nav-links a.active { border-bottom: 2px solid var(--brand); padding-bottom: 2px; }
.btn-join {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  padding: 10px 22px;
  border-radius: 10px;
  background: linear-gradient(135deg, #4b601d, #6a8630);
  color: #fff;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: transform 0.18s, box-shadow 0.18s;
}
.btn-join:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(139, 79, 50, 0.34);
}
.nav-actions { display: flex; align-items: center; gap: 12px; }
.hello-name {
  font-family: 'Oxanium', sans-serif;
  font-size: 11px;
  color: #888;
  letter-spacing: 0.08em;
}
.profile-icon-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid rgba(75, 96, 29, 0.42);
  background: linear-gradient(135deg, #4b601d, #6f8a34);
  color: #fff;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  font-size: 18px;
  overflow: hidden;
  transition: transform 0.18s, box-shadow 0.18s;
}
.profile-icon-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(75, 96, 29, 0.28);
}
.profile-icon-btn img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.hamburger { display: none; flex-direction: column; gap: 5px; cursor: pointer; background: none; border: none; padding: 4px; }
.hamburger span { display: block; width: 24px; height: 2px; background: var(--ink); transition: all 0.3s; }
.hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
.hamburger.open span:nth-child(2) { opacity: 0; }
.hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
.mobile-menu {
  display: none;
  position: fixed;
  top: 69px;
  left: 0;
  right: 0;
  background: #ffffff;
  border-bottom: 1px solid rgba(139, 79, 50, 0.14);
  padding: 20px 0;
  z-index: 99;
  flex-direction: column;
  align-items: center;
}
.mobile-menu.open { display: flex; }
.mobile-menu a {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 13px;
  font-weight: 700;
  color: #7b756b;
  text-decoration: none;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  padding: 14px 0;
  width: 100%;
  text-align: center;
  border-bottom: 1px solid rgba(139, 79, 50, 0.1);
}
.mobile-menu a:hover { color: var(--ink); }
.mobile-menu .btn-join { margin-top: 16px; border-radius: 8px; color: #ffffff; }

/* ===== PAGE HEADER ===== */
.page-header {
  padding: 112px 40px 36px;
  background: #ffffff;
  border-bottom: 1px solid #4b601d;
}
.page-header-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
}
.page-title-tag {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.22em;
  text-transform: uppercase;
  color: #4b601d;
  margin-bottom: 10px;
}
.page-title {
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  font-size: clamp(2rem, 4vw, 3rem);
  color: var(--ink);
  line-height: 1.08;
}
.page-title span {
  color: transparent;
  -webkit-text-stroke: 2px var(--brand);
  text-shadow: 0 8px 20px rgba(139, 79, 50, 0.2);
}
.page-count {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 11px;
  color: #4b601d;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  white-space: nowrap;
}
.page-count strong {
  color: var(--brand);
  font-size: 2rem;
  display: block;
  text-align: right;
  line-height: 1;
}


.gallery-bar {
  background: #ffffff;
  border-bottom: 1px solid #eadfce;
  padding: 16px 40px;
  position: sticky;
  top: 69px;
  z-index: 50;
  box-shadow: 0 10px 28px rgba(106, 83, 63, 0.08);
  backdrop-filter: blur(10px);
}
.gallery-bar-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  gap: 16px;
  flex-wrap: wrap;
}
.search-wrap {
  flex: 1;
  min-width: 220px;
  display: flex;
  align-items: center;
  gap: 10px;
  border: 1px solid #dccfbd;
  border-radius: 12px;
  padding: 11px 16px;
  background: rgba(255, 255, 255, 0.72);
  transition: border-color 0.2s, box-shadow 0.2s;
}
.search-wrap:focus-within {
  border-color: var(--brand);
  box-shadow: 0 0 0 3px rgba(139, 79, 50, 0.15);
  background: #fff;
}
.search-wrap svg { flex-shrink: 0; color: #a08e7a; }
.search-wrap input {
  border: none;
  background: transparent;
  outline: none;
  font-family: 'Manrope', sans-serif;
  font-size: 14px;
  color: #30343b;
  width: 100%;
}
.search-wrap input::placeholder { color: #af9f8f; }
.filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
.ftab {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  padding: 9px 18px;
  cursor: pointer;
  border: 1px solid #dacbb8;
  border-radius: 999px;
  background: #ffffff;
  color: #816d5c;
  transition: all 0.16s ease;
}
.ftab:hover { background: #4b601d; border-color: #4b601d; color: #ffffff; }
.ftab.active {
  background: linear-gradient(135deg, #4b601d, #6f8a34);
  color: #fff;
  border-color: transparent;
  box-shadow: 0 8px 18px rgba(139, 79, 50, 0.3);
}

/* ===== CARD GRID ===== */
.gallery-inner { max-width: 1200px; margin: 38px auto 84px; padding: 0 40px; }
.card-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; }
.gcard {
  background: var(--paper);
  border: 1px solid #e8ddce;
  border-radius: 18px;
  overflow: hidden;
  cursor: pointer;
  box-shadow: 0 10px 28px rgba(106, 83, 63, 0.08);
  transition: transform 0.24s, box-shadow 0.24s;
  animation: fadeUp 0.48s ease both;
}
.gcard:hover {
  transform: translateY(-6px) rotate(-0.2deg);
  box-shadow: 0 22px 36px rgba(106, 83, 63, 0.2);
}
.gcard.hidden { display: none; }
.gcard-img {
  position: relative;
  aspect-ratio: 4/3;
  overflow: hidden;
  background: linear-gradient(160deg, #24320e, #4b601d);
  display: flex;
  align-items: center;
  justify-content: center;
}
.gcard-photo {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  opacity: 0;
  transform: scale(1.04);
  transition: opacity 0.3s, transform 0.5s;
}
.gcard:hover .gcard-photo { transform: scale(1.08); }
.gcard-photo.loaded { opacity: 1; }
.gcard-placeholder { font-size: 40px; opacity: 0.28; color: #fff; z-index: 0; user-select: none; }
.gcard-tag {
  position: absolute;
  top: 12px;
  left: 12px;
  z-index: 2;
  font-family: 'Space Grotesk', sans-serif;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  background: rgba(255, 250, 244, 0.9);
  color: #5f3823;
  border-radius: 999px;
  padding: 5px 11px;
}
.gcard-body { padding: 16px 18px 20px; }
.gcard-title {
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  font-size: 1rem;
  color: var(--ink);
  margin-bottom: 10px;
  line-height: 1.35;
}
.gcard-meta { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 8px; }
.gcard-stars { font-size: 12px; color: #a26a2c; display: flex; align-items: center; gap: 2px; }
.gcard-count {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 11px;
  color: #9a8c7f;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
.gcard-link {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #6f3f27;
  text-decoration: none;
  border-bottom: 1.5px solid #a4613b;
  padding-bottom: 2px;
  display: inline-block;
  transition: color 0.2s, border-color 0.2s;
}
.gcard-link:hover { color: #372014; border-color: #372014; }
.empty-state { text-align: center; padding: 80px 0; }
.empty-icon { width: 48px; height: 48px; margin-bottom: 14px; opacity: 0.35; display: inline-block; }
.empty-text {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 13px;
  color: #998a7e;
  letter-spacing: 0.14em;
  text-transform: uppercase;
}
@keyframes fadeUp {
  from { opacity: 0; transform: translateY(22px); }
  to { opacity: 1; transform: translateY(0); }
}

.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 200;
  background: rgba(33, 23, 18, 0.62);
  backdrop-filter: blur(8px);
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.modal-overlay.open { display: flex; }
.modal-box {
  background: var(--paper-strong);
  width: 100%;
  max-width: 620px;
  max-height: 90vh;
  overflow-y: auto;
  position: relative;
  animation: modalIn 0.3s ease both;
  border-radius: 16px;
  border: 1px solid #e7dbca;
  box-shadow: var(--shadow);
}
@keyframes modalIn {
  from { opacity: 0; transform: translateY(16px) scale(0.97); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.modal-hero {
  position: relative;
  aspect-ratio: 16/7;
  overflow: hidden;
  background: linear-gradient(150deg, #22300f, #4b601d);
  display: flex;
  align-items: flex-end;
}
.modal-close {
  position: absolute;
  top: 12px;
  right: 12px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(31, 35, 41, 0.56);
  color: #fff;
  border: none;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 20;
}
.modal-close:hover { background: rgba(31, 35, 41, 0.9); }
.modal-hero-img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.modal-hero-placeholder { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 56px; opacity: 0.16; color: #fff; }
.modal-hero::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(34, 48, 15, 0.84) 0%, transparent 55%);
  z-index: 1;
}
.modal-hero-info { position: relative; z-index: 2; padding: 20px 24px; }
.modal-tag {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  background: rgba(255, 255, 255, 0.9);
  color: #5f3823;
  padding: 4px 10px;
  border-radius: 999px;
  display: inline-block;
  margin-bottom: 8px;
}
.modal-title {
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  font-size: clamp(1.3rem, 4vw, 1.9rem);
  color: #fff;
  line-height: 1.1;
  margin-bottom: 6px;
}
.modal-stars { font-size: 13px; color: #fff; opacity: 0.9; display: flex; align-items: center; gap: 2px; }
.modal-rating-note {
  margin-top: 6px;
  font-family: 'Space Grotesk', sans-serif;
  font-size: 12px;
  color: rgba(255,255,255,0.9);
  letter-spacing: 0.04em;
}
.modal-body { padding: 24px; }
.modal-section-label {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: #6f3f27;
  border-left: 3px solid #b56d45;
  padding-left: 10px;
  margin-bottom: 16px;
}
.modal-items { display: flex; flex-direction: column; gap: 12px; }
.menu-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px;
  border: 1px solid #e9ddce;
  background: #fffdf9;
  border-radius: 12px;
  transition: border-color 0.18s, box-shadow 0.18s;
}
.menu-item:hover {
  border-color: #c78962;
  box-shadow: 0 8px 20px rgba(111, 63, 39, 0.14);
}
.menu-item-img {
  width: 64px;
  height: 64px;
  flex-shrink: 0;
  background: linear-gradient(160deg, #283611, #4b601d);
  border-radius: 10px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
}
.menu-item-img img { width: 100%; height: 100%; object-fit: cover; }
.menu-item-info { flex: 1; }
.menu-item-name {
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  font-size: 14px;
  color: var(--ink);
  margin-bottom: 4px;
}
.menu-item-right { text-align: right; flex-shrink: 0; }
.menu-item-price {
  font-family: 'Space Grotesk', sans-serif;
  font-weight: 700;
  font-size: 14px;
  color: #6f3f27;
  margin-bottom: 8px;
  display: block;
}
.btn-order {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 8px 14px;
  background: linear-gradient(135deg, #4b601d, #6f8a34);
  color: #fff;
  border: none;
  border-radius: 999px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}
.btn-order:hover { filter: brightness(1.04); }
.modal-loading {
  text-align: center;
  padding: 40px;
  font-family: 'Space Grotesk', sans-serif;
  font-size: 12px;
  color: #9a8b7c;
  letter-spacing: 0.1em;
}
.modal-empty-actions {
  margin-top: 16px;
  display: flex;
  justify-content: center;
  gap: 10px;
  flex-wrap: wrap;
}
.empty-action-btn {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 10px 16px;
  border-radius: 999px;
  border: 1px solid #d7c2ac;
  background: #fffdf9;
  color: #7a533a;
  cursor: pointer;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.empty-action-btn.primary {
  background: linear-gradient(135deg, #4b601d, #6f8a34);
  border-color: #4b601d;
  color: #fff;
}
.empty-action-btn:hover { filter: brightness(0.98); }

/* ===== STAR RATING ===== */
.star { font-size: 14px; cursor: default; color: #dccfc1; transition: color 0.15s; user-select: none; }
.star.filled { color: #c67b35; }
.menu-star { font-size: 16px; color: #d9cab8; transition: color 0.15s; user-select: none; }
.menu-star.filled { color: #f6cc87; }
<?php if (isLoggedIn()): ?>
.star[onclick], .menu-star[onclick] { cursor: pointer; }
.gcard-stars:hover .star[onclick] { color: #c67b35; }
.gcard-stars .star[onclick]:hover ~ .star[onclick] { color: #dccfc1; }
<?php else: ?>
.star { cursor: not-allowed; }
<?php endif; ?>
.rating-num { font-size: 11px; color: #9f9080; margin-left: 3px; }

/* ===== RESPONSIVE ===== */
@media (max-width: 1024px) { .card-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) {
  nav { padding: 14px 20px; }
  .nav-links, .btn-join.desktop { display: none; }
  .hamburger { display: flex; }
  .gallery-bar { padding: 14px 20px; top: 68px; }
  .gallery-bar-inner { flex-direction: column; align-items: stretch; gap: 12px; }
  .search-wrap { min-width: 100%; }
  .page-header { padding: 85px 20px 18px; }
  .page-header-inner { flex-direction: column; align-items: flex-start; gap: 12px; }
  .page-title { font-size: clamp(1.2rem, 3vw, 2rem); }
  .page-count { width: 100%; }
  .gallery-inner { padding: 0 20px; margin-top: 20px; }
  .card-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
  .gcard { animation: none; }
  .gcard-title { font-size: 13px; }
  .gcard-meta { gap: 8px; flex-wrap: wrap; }
  .filter-tabs { gap: 6px; flex-wrap: wrap; }
  .ftab { padding: 6px 12px; font-size: 10px; }
  .modal-hero { aspect-ratio: 16/9; }
  .modal-title { font-size: clamp(1rem, 3vw, 1.5rem); }
  .modal-body { padding: 16px; }
  .modal-section-label { font-size: 10px; padding-left: 8px; }
  .modal-box { max-width: 95vw; }
  .menu-item { gap: 10px; padding: 10px; }
  .menu-item-img { width: 52px; height: 52px; font-size: 20px; }
  .menu-item-name { font-size: 13px; }
  .btn-order { padding: 7px 12px; font-size: 10px; }
}
@media (max-width: 480px) {
  nav { padding: 12px 14px; }
  .nav-logo { font-size: 11px; gap: 6px; }
  .btn-join.desktop { display: none; }
  .hello-name { display: none; }
  .profile-icon-btn {
    width: 34px;
    height: 34px;
    font-size: 16px;
  }
  .hamburger { gap: 4px; padding: 2px; }
  .hamburger span { width: 20px; height: 1.5px; }
  .page-header { padding: 75px 14px 14px; }
  .page-title { font-size: 1.3rem; }
  .page-count strong { font-size: 18px; }
  .gallery-bar { padding: 12px 14px; top: 60px; }
  .gallery-bar-inner { gap: 10px; }
  .search-wrap { padding: 8px 12px; font-size: 13px; }
  .ftab { padding: 5px 10px; font-size: 9px; }
  .gallery-inner { padding: 0 14px; margin-top: 16px; margin-bottom: 60px; }
  .card-grid { grid-template-columns: 1fr; gap: 12px; }
  .gcard-img { aspect-ratio: 3/2; }
  .gcard-tag {
    top: 8px;
    left: 8px;
    right: auto;
    bottom: auto;
    width: fit-content;
    max-width: calc(100% - 16px);
    white-space: nowrap;
    font-size: 8px;
    letter-spacing: 0.08em;
    padding: 4px 8px;
  }
  .gcard-body { padding: 12px 14px 14px; }
  .gcard-title { font-size: 12px; margin-bottom: 8px; }
  .gcard-meta { font-size: 11px; gap: 6px; }
  .gcard-stars { gap: 1px; }
  .star { font-size: 12px; }
  .rating-num { font-size: 10px; margin-left: 2px; }
  .gcard-count { font-size: 10px; }
  .gcard-link { font-size: 10px; }
  .empty-icon { width: 36px; height: 36px; }
  .empty-text { font-size: 11px; }
  .modal-overlay { padding: 12px; }
  .modal-box { border: none; border-radius: 8px 8px 0 0; max-height: 88vh; }
  .modal-hero { aspect-ratio: 2/1; }
  .modal-close { width: 32px; height: 32px; font-size: 18px; }
  .modal-hero-info { padding: 16px; }
  .modal-tag { font-size: 8px; padding: 3px 8px; }
  .modal-title { font-size: 1.3rem; }
  .modal-stars { font-size: 12px; }
  .modal-rating-note { font-size: 11px; margin-top: 4px; }
  .modal-body { padding: 14px; }
  .modal-section-label { font-size: 9px; margin-bottom: 12px; }
  .modal-items { gap: 10px; }
  .menu-item { flex-direction: column; gap: 8px; padding: 10px; }
  .menu-item-img { width: 100%; height: 120px; margin-right: 0; }
  .menu-item-info { flex: 1; }
  .menu-item-right { width: 100%; flex-direction: row; justify-content: space-between; align-items: center; gap: 8px; }
  .menu-item-price { margin-bottom: 0; font-size: 13px; }
  .btn-order { padding: 6px 10px; font-size: 9px; width: auto; }
  .menu-star { font-size: 14px; }
}
</style>
</head>
<body>
<nav>
  <a href="index.php" class="nav-logo">
    <div class="logo-icon"><img src = "assets/img/logosmkn-transparent.png" height = "50px"></div>
    <div><div>SMKN 1 SURABAYA</div><div class="sub">Kantin</div></div>
  </a>
  <ul class="nav-links">
    <li><a href="index.php">Home</a></li>
    <li><a href="page2.php" class="active">Menu</a></li>
    <li><a href="myTray.php">My Tray</a></li>
  </ul>
  <div class="nav-actions">
    <?php if ($isAnyLoggedIn): ?>
      <span class="hello-name">
        Hi, <strong style="color:#111"><?= htmlspecialchars($profileName) ?></strong>
      </span>
      <a href="<?= htmlspecialchars($profileHref) ?>" class="profile-icon-btn" title="Buka Pengaturan Profil" aria-label="Buka Pengaturan Profil">
        <?php if (!empty($profileIconSrc)): ?>
          <img src="<?= htmlspecialchars($profileIconSrc) ?>" alt="Profile">
        <?php else: ?>
          👤
        <?php endif; ?>
      </a>
      <a href="<?= htmlspecialchars($logoutHref) ?>" class="btn-join desktop" style="background:#555;">Logout</a>
    <?php else: ?>
      <a href="login.php" class="btn-join desktop">Login</a>
    <?php endif; ?>
    <button class="hamburger" id="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
  </div>
</nav>

<div class="mobile-menu" id="mobileMenu">
  <a href="index.php">Home</a>
  <a href="page2.php">Menu</a>
  <a href="myTray.php">My Tray</a>
  <?php if ($isAnyLoggedIn): ?>
    <a href="<?= htmlspecialchars($logoutHref) ?>" class="btn-join">Logout</a>
  <?php else: ?>
    <a href="login.php" class="btn-join">Login</a>
  <?php endif; ?>
</div>

<div class="page-header">
  <div class="page-header-inner">
    <div>
      <div class="page-title-tag">✦ Menu Kantin</div>
      <h1 class="page-title">Pilihan <span>Makanan</span></h1>
    </div>
    <div class="page-count">
      <strong id="visibleCount"><?= count($stands) ?></strong>
      stand tersedia
    </div>
  </div>
</div>


<div class="gallery-bar">
  <div class="gallery-bar-inner">
    <div class="search-wrap">
      <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
      <input type="text" id="searchInput" placeholder="Cari makanan atau stand..."/>
    </div>
    <div class="filter-tabs">
      <button class="ftab active" data-cat="all">Semua</button>
      <button class="ftab" data-cat="berat">Makanan Berat</button>
      <button class="ftab" data-cat="ringan">Makanan Ringan</button>
      <button class="ftab" data-cat="minuman">Minuman</button>
      <button class="ftab" data-cat="dessert">Dessert</button>
    </div>
  </div>
</div>


<div class="gallery-inner">
  <div class="card-grid" id="cardGrid">
    <?php if (empty($stands)): ?>
      <p style="grid-column:1/-1;text-align:center;color:#aaa;font-family:'Oxanium',sans-serif;padding:60px 0;">
        Belum ada stand.
      </p>
    <?php else: ?>
      <?php foreach ($stands as $i => $stand):
        $emoji  = $emojiMap[$stand['kategori']] ?? '🍽️';
        $label  = $labelMap[$stand['kategori']] ?? $stand['kategori'];
        $foto = $stand['foto'] ? 'uploads/' . $stand['foto'] : ($fotoMap[$i+1] ?? '');
        $myRating = $userStandRatings[$stand['id']] ?? 0;
      ?>
      <div class="gcard"
        data-cat="<?= htmlspecialchars($stand['kategori']) ?>"
        data-title="<?= htmlspecialchars(strtolower($stand['nama'])) ?>"
        data-id="<?= $stand['id'] ?>"
        style="animation-delay:<?= $i * 0.05 ?>s">
        <div class="gcard-img" style="background:#1a1a1a;">
          <?php if ($foto): ?>
            <img src="<?= htmlspecialchars($foto) ?>" alt="<?= htmlspecialchars($stand['nama']) ?>" class="gcard-photo"/>
          <?php endif; ?>
          <div class="gcard-placeholder"></div>
          <span class="gcard-tag"><?= $label ?></span>
        </div>
        <div class="gcard-body">
          <div class="gcard-title"><?= htmlspecialchars($stand['nama']) ?></div>
          <div class="gcard-meta">
            <div class="gcard-stars" id="stand-stars-<?= $stand['id'] ?>">
              <?php for ($s = 1; $s <= 5; $s++):
                $filled = $s <= ($myRating ?: round($stand['rating'])) ? 'filled' : '';
              ?>
              <span class="star <?= $filled ?>"
                data-val="<?= $s ?>"
                data-type="stand"
                data-id="<?= $stand['id'] ?>"
                <?= isLoggedIn() ? 'onclick="submitRating(this)"' : 'title="Login dulu untuk rating"' ?>>★</span>
              <?php endfor; ?>
              <span class="rating-num" id="stand-num-<?= $stand['id'] ?>">(<?= $stand['rating'] ?>)</span>
            </div>
            <div class="gcard-count"><?= $stand['item_count'] ?> Items</div>
          </div>
          <a href="#" class="gcard-link">View Menu →</a>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="empty-state" id="emptyState" style="display:none;">
    <img class="empty-icon" src="https://api.iconify.design/mdi:magnify.svg?color=%23c8a7d6" alt="" aria-hidden="true">
    <div class="empty-text">Tidak ada hasil ditemukan</div>
  </div>
</div>

<!-- MODAL -->
<div class="modal-overlay" id="modalOverlay">
  <div class="modal-box" id="modalBox">
    <div class="modal-hero">
      <button class="modal-close" id="modalClose">✕</button>
      <img src="image1.jpg" alt="" class="modal-hero-img" id="modalHeroImg" style="display:none"/>
      <div class="modal-hero-placeholder" id="modalHeroPlaceholder"></div>
      <div class="modal-hero-info">
        <span class="modal-tag" id="modalTag"></span>
        <h2 class="modal-title" id="modalTitle"></h2>
        <div class="modal-stars" id="modalStars"></div>
        <div class="modal-rating-note" id="modalRatingNote"></div>
      </div>
    </div>
    <div class="modal-body">
      <div class="modal-section-label">Menu Items</div>
      <div class="modal-items" id="modalItems"></div>
    </div>
  </div>
</div>

<!-- ITEM DETAIL MODAL -->
<div class="modal-overlay" id="itemOverlay">
  <div class="modal-box item-modal-box" id="itemModalBox">
    <!-- Kiri: Foto -->
    <div class="item-modal-left" id="itemModalLeft">
      <button class="modal-close" id="itemModalClose">✕</button>
      <img src="" alt="" id="itemModalImg" style="width:100%;height:100%;object-fit:cover;display:none;"/>
      <div class="item-modal-img-placeholder" id="itemModalPlaceholder"></div>
    </div>
    <!-- Kanan: Detail -->
    <div class="item-modal-right" id="itemModalRight">
      <div class="item-detail-scroll">
        <div class="item-tag" id="itemTag"></div>
        <h2 class="item-name" id="itemName"></h2>
        <div class="item-price-row">
          <span class="item-price" id="itemPrice"></span>
          <div class="item-avg-stars" id="itemAvgStars"></div>
        </div>
        <button class="btn-add-tray" id="btnAddTray">🛒 Add to Tray</button>

        <div class="review-section">
          <div class="review-header">
            <span class="review-title">Reviews &amp; Feedback</span>
            <span class="review-count" id="reviewCount">0 reviews</span>
          </div>

          <?php if (isLoggedIn()): ?>
          <!-- Form Review -->
          <div class="review-form" id="reviewForm">
            <div class="rf-label">Your Rating</div>
            <div class="rf-stars" id="rfStars">
              <?php for ($s=1;$s<=5;$s++): ?>
              <span class="rf-star" data-val="<?=$s?>" onclick="setReviewStar(this)">★</span>
              <?php endfor; ?>
            </div>
            <textarea id="rfKomentar" class="rf-textarea" placeholder="Share your experience..."></textarea>
            <button class="rf-submit" onclick="submitReview()">Post Review</button>
            <div class="rf-msg" id="rfMsg"></div>
          </div>
          <?php else: ?>
          <div class="review-login-prompt">
            <a href="login.php">Login</a> dulu untuk menulis review.
          </div>
          <?php endif; ?>

          <!-- Daftar Review -->
          <div class="review-list" id="reviewList"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
/* ===== ITEM MODAL ===== */
.item-modal-box {
  max-width: 860px !important;
  display: flex !important;
  flex-direction: row;
  max-height: 90vh;
  overflow: hidden;
}
.item-modal-left {
  position: relative;
  width: 46%;
  flex-shrink: 0;
  background: linear-gradient(160deg, #22300f, #4b601d);
  min-height: 420px;
  display: flex; align-items: center; justify-content: center;
}
.item-modal-img-placeholder { font-size: 64px; opacity: 0.2; color: #fff; }
.item-modal-right { flex: 1; overflow-y: auto; }
.item-detail-scroll { padding: 28px 24px; }

.item-tag { font-family: 'Space Grotesk', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.15em; text-transform: uppercase; background: #f4e8dd; color: #6f3f27; border-radius: 999px; padding: 4px 10px; display: inline-block; margin-bottom: 10px; }
.item-name { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 1.4rem; color: #1f2329; margin-bottom: 12px; line-height: 1.2; }
.item-price-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.item-price { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 1.3rem; color: #6f3f27; }
.item-avg-stars { display: flex; align-items: center; gap: 2px; font-size: 15px; }
.item-avg-stars span.s { color: #c67b35; }
.item-avg-stars span.e { color: #dccfc1; }
.item-avg-stars .avg-num { font-size: 12px; color: #8f8478; margin-left: 4px; }

.btn-add-tray { width: 100%; padding: 13px; background: linear-gradient(135deg, #4b601d, #6f8a34); color: #fff; font-family: 'Space Grotesk', sans-serif; font-size: 12px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; border: none; border-radius: 999px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 24px; }
.btn-add-tray:hover { filter: brightness(1.05); }

/* Review section */
.review-section { border-top: 1px solid #eee1d1; padding-top: 20px; }
.review-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.review-title { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 13px; letter-spacing: 0.08em; text-transform: uppercase; color: #6f3f27; }
.review-count { font-size: 12px; color: #9a8b7d; font-family: 'Space Grotesk', sans-serif; }

/* Form */
.review-form { background: #fff8f1; border: 1px solid #ecdcc7; border-radius: 12px; padding: 16px; margin-bottom: 20px; }
.rf-label { font-family: 'Space Grotesk', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase; color: #7d695a; margin-bottom: 8px; }
.rf-stars { display: flex; gap: 4px; margin-bottom: 12px; }
.rf-star { font-size: 22px; color: #d8c8b7; cursor: pointer; transition: color 0.15s; user-select: none; }
.rf-star.active { color: #c67b35; }
.rf-textarea { width: 100%; border: 1.5px solid #dcc9b2; border-radius: 10px; padding: 10px 12px; font-family: 'Manrope', sans-serif; font-size: 13px; color: #333; background: #fff; outline: none; resize: vertical; min-height: 80px; transition: border-color 0.2s, box-shadow 0.2s; }
.rf-textarea:focus { border-color: #b56d45; box-shadow: 0 0 0 3px rgba(181, 109, 69, 0.14); }
.rf-submit { margin-top: 10px; padding: 10px 20px; background: linear-gradient(135deg, #4b601d, #6f8a34); color: #fff; font-family: 'Space Grotesk', sans-serif; font-size: 11px; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; border: none; border-radius: 999px; cursor: pointer; }
.rf-submit:hover { filter: brightness(1.05); }
.rf-msg { font-size: 12px; margin-top: 8px; color: #555; font-family: 'Space Grotesk', sans-serif; }
.rf-msg.ok { color: #1a7a3a; }
.rf-msg.err { color: #cc0000; }
.review-login-prompt { font-size: 13px; color: #8b7d6f; margin-bottom: 16px; }
.review-login-prompt a { color: #4b601d; font-weight: 700; }

/* Review items */
.review-item { padding: 14px 0; border-bottom: 1px solid #f0e5d6; }
.review-item:last-child { border-bottom: none; }
.ri-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
.ri-name { font-family: 'Space Grotesk', sans-serif; font-weight: 700; font-size: 13px; color: #1f2329; }
.ri-date { font-size: 11px; color: #aa9a8b; font-family: 'Space Grotesk', sans-serif; }
.ri-stars { font-size: 13px; color: #c67b35; margin-bottom: 6px; }
.ri-stars span { color: #dfcfbe; }
.ri-komentar { font-size: 13px; color: #62584f; line-height: 1.5; }
.review-empty { text-align: center; padding: 24px 0; font-family: 'Space Grotesk', sans-serif; font-size: 12px; color: #b3a496; letter-spacing: 0.1em; }

@media (max-width: 640px) {
  .item-modal-box { flex-direction: column; }
  .item-modal-left { width: 100%; min-height: 220px; }
}
</style>

<script>
const IS_LOGGED_IN = <?= $isAnyLoggedIn ? 'true' : 'false' ?>;
const USER_ID = <?= ($isUserLoggedIn || $isOwnerLoggedIn || $isSellerLoggedIn) ? (int)($isUserLoggedIn ? $_SESSION['user_id'] : ($isOwnerLoggedIn ? $_SESSION['owner_id'] : $_SESSION['seller_id'])) : 0 ?>;
const emojiMap = { berat:'🍛', ringan:'🧆', minuman:'🧋', dessert:'🧇' };
const labelMap = { berat:'Makanan Berat', ringan:'Makanan Ringan', minuman:'Minuman', dessert:'Dessert' };
const btnAddTray = document.getElementById('btnAddTray');
let currentTrayItem = null;

// Hamburger
const hamburger  = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');
if (hamburger && mobileMenu) {
  hamburger.addEventListener('click', () => { hamburger.classList.toggle('open'); mobileMenu.classList.toggle('open'); });
  mobileMenu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => { hamburger.classList.remove('open'); mobileMenu.classList.remove('open'); }));
}

// Lazy load foto
document.querySelectorAll('.gcard-photo').forEach(img => {
  if (img.complete && img.naturalWidth) img.classList.add('loaded');
  img.addEventListener('load', () => img.classList.add('loaded'));
});

// Filter + Search
const ftabs = document.querySelectorAll('.ftab');
const cards = document.querySelectorAll('.gcard');
const emptyState = document.getElementById('emptyState');
const searchInput = document.getElementById('searchInput');
const visibleCount = document.getElementById('visibleCount');
function filterCards() {
  const cat = document.querySelector('.ftab.active').dataset.cat;
  const query = searchInput.value.toLowerCase();
  let visible = 0;
  cards.forEach(card => {
    const ok = (cat === 'all' || card.dataset.cat === cat) && card.dataset.title.includes(query);
    card.classList.toggle('hidden', !ok);
    if (ok) visible++;
  });
  visibleCount.textContent = visible;
  emptyState.style.display = visible === 0 ? 'block' : 'none';
}
ftabs.forEach(tab => { tab.addEventListener('click', () => { ftabs.forEach(t => t.classList.remove('active')); tab.classList.add('active'); filterCards(); }); });
searchInput.addEventListener('input', filterCards);

// ===== STAND RATING =====
function submitRating(starEl) {
  if (!IS_LOGGED_IN) { window.location = 'login.php'; return; }
  const val = parseInt(starEl.dataset.val), type = starEl.dataset.type, id = starEl.dataset.id;
  const fd = new FormData();
  fd.append('type', type); fd.append('id', id); fd.append('rating', val);
  fetch('api/rate.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
    if (data.error) { alert(data.error); return; }
    if (type === 'stand') {
      document.getElementById(`stand-stars-${id}`).querySelectorAll('.star').forEach(s => s.classList.toggle('filled', parseInt(s.dataset.val) <= data.your_rating));
      document.getElementById(`stand-num-${id}`).textContent = `(${data.new_rating})`;
      if (currentOpenId == id) {
        document.getElementById('modalStars').innerHTML = buildStarsHtml(data.new_rating, 'stand', id, data.your_rating, true);
        modalRatingNote.textContent = formatStandRatingNote(data.new_rating, data.new_total);
      }
    }
  }).catch(() => alert('Gagal kirim rating.'));
}

function buildStarsHtml(avg, type, id, myR, white) {
  let html = '';
  for (let i = 1; i <= 5; i++) {
    const f = i <= (myR || Math.round(avg));
    const cls = white ? 'menu-star' : 'star';
    const col = white ? (f ? '#fff' : 'rgba(255,255,255,0.3)') : (f ? '#111' : '#ddd');
    const click = IS_LOGGED_IN ? `onclick="submitRating(this)"` : `title="Login dulu"`;
    html += `<span class="${cls}${f?' filled':''}" data-val="${i}" data-type="${type}" data-id="${id}" ${click} style="color:${col}">★</span>`;
  }
  if (white) return html;
  const ns = white ? 'opacity:.7;margin-left:4px;font-size:13px;color:#fff' : 'font-size:11px;color:#bbb;margin-left:3px';
  html += `<span id="${type==='menu'?'menu':'modal'}-num-${id}" style="${ns}">(${avg})</span>`;
  return html;
}

// ===== STAND MODAL =====
let currentOpenId = null;
const modalOverlay = document.getElementById('modalOverlay');
const modalClose   = document.getElementById('modalClose');
const modalTitle   = document.getElementById('modalTitle');
const modalTag     = document.getElementById('modalTag');
const modalStars   = document.getElementById('modalStars');
const modalRatingNote = document.getElementById('modalRatingNote');
const modalItems   = document.getElementById('modalItems');
const modalHeroImg = document.getElementById('modalHeroImg');
const modalHeroPlaceholder = document.getElementById('modalHeroPlaceholder');

function formatStandRatingNote(avg, totalVotes) {
  const numericAvg = Number(avg) || 0;
  const votes = Number(totalVotes) || 0;
  if (numericAvg <= 0 || votes <= 0) {
    return 'Belum ada rating';
  }
  return `${numericAvg.toFixed(1)} (${votes} ulasan)`;
}

function shareExperienceFromModal() {
  if (!IS_LOGGED_IN) {
    window.location = 'login.php';
    return;
  }
  window.location = 'pages/testimonial.php';
}

function renderModalEmptyState() {
  modalItems.innerHTML = `
    <div class="modal-loading">Belum ada menu item.</div>
    <div class="modal-empty-actions">
      <a class="empty-action-btn primary" href="myTray.php">Tambahkan keranjang</a>
      <button type="button" class="empty-action-btn" onclick="shareExperienceFromModal()">Bagikan pengalamanmu</button>
    </div>
  `;
}

function openModal(standId) {
  currentOpenId = standId;
  modalTitle.textContent = 'Memuat...'; modalTag.textContent = ''; modalStars.innerHTML = ''; modalRatingNote.textContent = '';
  modalHeroImg.style.display = 'none'; modalHeroPlaceholder.textContent = '⏳';
  modalItems.innerHTML = '<div class="modal-loading">Memuat menu...</div>';
  modalOverlay.classList.add('open'); document.body.style.overflow = 'hidden';

  fetch(`api/menu.php?stand_id=${standId}&user_id=${USER_ID}`)
    .then(r => r.json()).then(data => {
      if (data.error) { modalItems.innerHTML = `<div class="modal-loading">${data.error}</div>`; return; }
      const stand = data.stand, emoji = emojiMap[stand.kategori] || '🍽️';
      const cardEl = document.querySelector(`.gcard[data-id="${standId}"]`);
      const cp = cardEl ? cardEl.querySelector('.gcard-photo') : null;
      if (cp && cp.complete && cp.naturalWidth) { modalHeroImg.src = cp.src; modalHeroImg.style.display = 'block'; modalHeroPlaceholder.textContent = ''; }
      else if (stand.foto) { modalHeroImg.src = stand.foto; modalHeroImg.style.display = 'block'; modalHeroPlaceholder.textContent = ''; }
      else { modalHeroImg.style.display = 'none'; modalHeroPlaceholder.textContent = emoji; }
      modalTag.textContent = labelMap[stand.kategori] || stand.kategori;
      modalTitle.textContent = stand.nama;
      modalStars.innerHTML = buildStarsHtml(stand.rating, 'stand', stand.id, data.my_stand_rating || 0, true);
      modalRatingNote.textContent = formatStandRatingNote(stand.rating, stand.total_votes);
      if (data.items.length === 0) { renderModalEmptyState(); return; }
      modalItems.innerHTML = data.items.map(item => `
        <div class="menu-item" onclick="openItemModal(${item.id}, '${item.nama.replace(/'/g,"\\'")}', ${item.harga}, '${stand.kategori}', '${item.foto||''}')">
          <div class="menu-item-img">${item.foto ? `<img src="${item.foto}" alt="${item.nama}"/>` : emoji}</div>
          <div class="menu-item-info">
            <div class="menu-item-name">${item.nama}</div>
            <div style="display:flex;align-items:center;gap:2px;margin-top:4px;font-size:12px;">
              ${[1,2,3,4,5].map(i=>`<span style="color:${i<=Math.round(item.rating)?'#111':'#ddd'}">★</span>`).join('')}
              <span style="font-size:11px;color:#bbb;margin-left:2px;">(${item.rating})</span>
            </div>
          </div>
          <div class="menu-item-right">
            <span class="menu-item-price">Rp ${Number(item.harga).toLocaleString('id-ID')}</span>
            <button class="btn-order" onclick="event.stopPropagation();openItemModal(${item.id},'${item.nama.replace(/'/g,"\\'")}',${item.harga},'${stand.kategori}','${item.foto||''}')">Detail →</button>
          </div>
        </div>
      `).join('');
    }).catch(() => { modalItems.innerHTML = '<div class="modal-loading">Gagal memuat data.</div>'; });
}
function closeModal() { modalOverlay.classList.remove('open'); document.body.style.overflow = ''; currentOpenId = null; }
const gcardEls = document.querySelectorAll('.gcard');
if (gcardEls && gcardEls.length) {
  gcardEls.forEach(card => { card.addEventListener('click', e => { if (e.target.closest('.gcard-link')) e.preventDefault(); openModal(card.dataset.id); }); });
}
if (modalClose) modalClose.addEventListener('click', closeModal);
if (modalOverlay) modalOverlay.addEventListener('click', e => { if (e.target === modalOverlay) closeModal(); });

// ===== ITEM DETAIL MODAL =====
let currentItemId = null;
let reviewStarVal = 0;
const itemOverlay    = document.getElementById('itemOverlay');
const itemModalClose = document.getElementById('itemModalClose');

function openItemModal(menuId, nama, harga, kategori, foto) {
  currentItemId = menuId;
  reviewStarVal = 0;
  currentTrayItem = { menuId, nama, harga, foto, kategori };
  console.log('openItemModal - currentTrayItem set:', currentTrayItem);
  // Safe DOM access helpers
  const $ = id => document.getElementById(id);

  // Set basic info (guard if elements missing)
  const tagEl = $('itemTag'); if (tagEl) tagEl.textContent = labelMap[kategori] || kategori;
  const nameEl = $('itemName'); if (nameEl) nameEl.textContent = nama;
  const priceEl = $('itemPrice'); if (priceEl) priceEl.textContent = 'Rp ' + Number(harga).toLocaleString('id-ID');
  const avgEl = $('itemAvgStars'); if (avgEl) avgEl.innerHTML = '<span style="color:#aaa;font-size:13px;">Memuat...</span>';
  const rcEl = $('reviewCount'); if (rcEl) rcEl.textContent = '0 reviews';
  const rlEl = $('reviewList'); if (rlEl) rlEl.innerHTML = '<div class="review-empty">Memuat reviews...</div>';
  if (btnAddTray) { btnAddTray.disabled = false; btnAddTray.textContent = 'Tambahkan keranjang'; }

  // Set foto (guard)
  const imgEl = $('itemModalImg');
  const phEl  = $('itemModalPlaceholder');
  if (imgEl && phEl) {
    if (foto) { imgEl.src = foto; imgEl.style.display = 'block'; phEl.style.display = 'none'; }
    else { imgEl.style.display = 'none'; phEl.style.display = 'flex'; phEl.textContent = emojiMap[kategori] || '🍽️'; }
  }

  // Reset form (guard)
  if (IS_LOGGED_IN) {
    document.querySelectorAll('.rf-star').forEach(s => s.classList.remove('active'));
    const kom = $('rfKomentar'); if (kom) kom.value = '';
    const msg = $('rfMsg'); if (msg) { msg.textContent = ''; msg.className = 'rf-msg'; }
  }

  if (itemOverlay) itemOverlay.classList.add('open');
  document.body.style.overflow = 'hidden';

  // Fetch detail + reviews
  fetch(`api/get_item.php?menu_id=${menuId}&user_id=${USER_ID}`)
    .then(r => r.json()).then(data => {
      if (data.error) return;
      const item = data.item;

      // Rating bintang rata-rata
      const avgStars = [1,2,3,4,5].map(i =>
        `<span class="${i <= Math.round(item.rating) ? 's' : 'e'}">★</span>`
      ).join('') + `<span class="avg-num">(${item.rating}) · ${item.total_votes} votes</span>`;
      document.getElementById('itemAvgStars').innerHTML = avgStars;
      document.getElementById('reviewCount').textContent = `${data.reviews.length} reviews`;

      // Isi rating user yg sudah ada
      if (IS_LOGGED_IN && data.my_rating > 0) {
        reviewStarVal = data.my_rating;
        document.querySelectorAll('.rf-star').forEach(s => {
          s.classList.toggle('active', parseInt(s.dataset.val) <= data.my_rating);
        });
        if (data.my_komentar) document.getElementById('rfKomentar').value = data.my_komentar;
      }

      // Render reviews
      if (data.reviews.length === 0) {
        document.getElementById('reviewList').innerHTML = '<div class="review-empty">No reviews yet. Be the first to review!</div>';
      } else {
        document.getElementById('reviewList').innerHTML = data.reviews.map(rv => `
          <div class="review-item">
            <div class="ri-top">
              <span class="ri-name">${rv.nama}</span>
              <span class="ri-date">${rv.waktu}</span>
            </div>
           <div class="ri-stars">${[1,2,3,4,5].map(i=>`<span style="color:${i<=Number(rv.rating)?'#111':'#ddd'}">★</span>`).join('')}</div>
            <div class="ri-komentar">${rv.komentar}</div>
          </div>
        `).join('');
      }
    }).catch(() => {});
}

async function addCurrentItemToTray() {
    if (!currentTrayItem || !currentTrayItem.menuId) {
        alert('Item tidak valid, silakan refresh page');
        return;
    }

    if (!IS_LOGGED_IN) {
        alert('Login dulu untuk menambah item ke tray!');
        window.location = 'login.php';
        return;
    }

    btnAddTray.disabled = true;
    btnAddTray.textContent = 'Menambahkan...';

    try {
        const fd = new FormData();
        fd.append('action', 'add');
        fd.append('menu_id', currentTrayItem.menuId);
        fd.append('qty', 1);

        const res = await fetch('api/tray.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (!res.ok || data.error) throw new Error(data.error || 'Gagal menambah item');

        btnAddTray.textContent = '✓ Ditambahkan!';
        setTimeout(() => {
            btnAddTray.disabled = false;
            btnAddTray.textContent = '🛒 Tambahkan keranjang';
        }, 1500);
    } catch (err) {
        btnAddTray.disabled = false;
        btnAddTray.textContent = '🛒 Tambahkan keranjang';
        alert(err.message || 'Gagal menambah item ke tray');
    }
}

	// btnAddTray.disabled = true;
	// btnAddTray.textContent = 'Menambahkan...';

	// try {
	// 	// Simpan ke localStorage
	// 	let tray = JSON.parse(localStorage.getItem('cafeteriaTray') || '[]');
	// 	const existingItem = tray.find(item => item.menuId === currentTrayItem.menuId);
		
	// 	if (existingItem) {
	// 		existingItem.qty += 1;
  //     existingItem.nama = currentTrayItem.nama;
  //     existingItem.harga = currentTrayItem.harga;
  //     existingItem.foto = currentTrayItem.foto || existingItem.foto || '';
  //     existingItem.kategori = currentTrayItem.kategori || existingItem.kategori || '';
	// 	} else {
	// 		tray.push({
	// 			menuId: currentTrayItem.menuId,
	// 			nama: currentTrayItem.nama,
	// 			harga: currentTrayItem.harga,
  //       foto: currentTrayItem.foto || '',
  //       kategori: currentTrayItem.kategori || '',
	// 			qty: 1,
	// 			addedAt: new Date().toISOString()
	// 		});
	// 	}
		
	// 	localStorage.setItem('cafeteriaTray', JSON.stringify(tray));
	// 	console.log('Item saved to localStorage:', tray);

	// 	btnAddTray.textContent = '✓ Ditambahkan ke Tray';
	// 	setTimeout(() => {
	// 		btnAddTray.disabled = false;
	// 		btnAddTray.textContent = '🛒 Tambahkan keranjang';
	// 	}, 1500);
	//  catch (err) {
	// 	btnAddTray.disabled = false;
	// 	btnAddTray.textContent = '🛒 Tambahkan keranjang';
	// 	console.error('Error adding to tray:', err);
	// 	alert(err.message || 'Gagal menambah item ke tray');
	// }


if (btnAddTray) btnAddTray.addEventListener('click', addCurrentItemToTray);

function closeItemModal() {
  itemOverlay.classList.remove('open');
  document.body.style.overflow = '';
  // Refresh stand modal supaya rating item ikut update
  if (currentOpenId) openModal(currentOpenId);
  currentItemId = null;
}
if (itemModalClose) itemModalClose.addEventListener('click', closeItemModal);
if (itemOverlay) itemOverlay.addEventListener('click', e => { if (e.target === itemOverlay) closeItemModal(); });

// Review star select
function setReviewStar(el) {
  reviewStarVal = parseInt(el.dataset.val);
  document.querySelectorAll('.rf-star').forEach(s => s.classList.toggle('active', parseInt(s.dataset.val) <= reviewStarVal));
}

// Submit review
function submitReview() {
  if (!IS_LOGGED_IN) { window.location = 'login.php'; return; }
  const komentar = document.getElementById('rfKomentar').value.trim();
  const msgEl    = document.getElementById('rfMsg');
  if (reviewStarVal === 0) { msgEl.className = 'rf-msg err'; msgEl.textContent = 'Pilih rating dulu!'; return; }
  if (!komentar) { msgEl.className = 'rf-msg err'; msgEl.textContent = 'Tulis komentar dulu!'; return; }
  msgEl.className = 'rf-msg'; msgEl.textContent = 'Mengirim...';
  const fd = new FormData();
  fd.append('menu_id', currentItemId); fd.append('rating', reviewStarVal); fd.append('komentar', komentar);
  fetch('api/review.php', { method: 'POST', body: fd }).then(r => r.json()).then(data => {
    if (data.error) { msgEl.className = 'rf-msg err'; msgEl.textContent = data.error; return; }
    msgEl.className = 'rf-msg ok'; msgEl.textContent = '✓ Review terkirim!';
    // Update rating display
    const avgStars = [1,2,3,4,5].map(i => `<span class="${i<=Math.round(data.new_rating)?'s':'e'}">★</span>`).join('')
      + `<span class="avg-num">(${data.new_rating}) · ${data.total} votes</span>`;
    document.getElementById('itemAvgStars').innerHTML = avgStars;
    document.getElementById('reviewCount').textContent = `${data.total} reviews`;
    // Prepend review baru
    const rv = data.review;
    const newItem = `<div class="review-item">
      <div class="ri-top"><span class="ri-name">${rv.nama}</span><span class="ri-date">${rv.waktu}</span></div>
      <div class="ri-stars">${[1,2,3,4,5].map(i=>`<span style="color:${i<=Number(rv.rating)?'#111':'#ddd'}">★</span>`).join('')}</div>
      <div class="ri-komentar">${rv.komentar}</div>
    </div>`;
    const rl = document.getElementById('reviewList');
    if (rl.querySelector('.review-empty')) rl.innerHTML = newItem;
    else rl.insertAdjacentHTML('afterbegin', newItem);
  }).catch(() => { msgEl.className = 'rf-msg err'; msgEl.textContent = 'Gagal mengirim.'; });
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeItemModal(); closeModal(); } });
</script>
</body>
</html>