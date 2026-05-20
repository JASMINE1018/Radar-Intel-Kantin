<?php
require_once 'seller_auth.php';
require_once 'config.php';
requireSeller();
$seller = currentSeller();
$sid = $seller['id'];

// Pastikan tabel profil seller tersedia 
// Pastikan struktur tabel tersedia 
$conn->query("CREATE TABLE IF NOT EXISTS seller_profiles (
  seller_id INT PRIMARY KEY,
  tanggal_lahir DATE NULL,
  deskripsi TEXT NULL,
  foto_profile VARCHAR(255) NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_seller_profiles_seller FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE
)");

// ── Ambil identitas dasar seller ──
$sellerIdentityStmt = $conn->prepare("SELECT nama, email FROM sellers WHERE id = ? LIMIT 1");
$sellerIdentityStmt->bind_param('i', $sid);
$sellerIdentityStmt->execute();
$sellerIdentity = $sellerIdentityStmt->get_result()->fetch_assoc();
$sellerIdentityStmt->close();

// Ambil biodata tambahan seller 
$sellerProfileStmt = $conn->prepare("SELECT tanggal_lahir, deskripsi, foto_profile FROM seller_profiles WHERE seller_id = ? LIMIT 1");
$sellerProfileStmt->bind_param('i', $sid);
$sellerProfileStmt->execute();
$sellerProfile = $sellerProfileStmt->get_result()->fetch_assoc();
$sellerProfileStmt->close();

// Siapkan data profil untuk ditampilkan di halaman 
$seller_name = $sellerIdentity['nama'] ?? ($seller['nama'] ?? 'Seller');
$seller_email = $sellerIdentity['email'] ?? '';
$seller_tanggal_lahir = $sellerProfile['tanggal_lahir'] ?? '';
$seller_deskripsi = $sellerProfile['deskripsi'] ?? '';
$seller_foto_profile = $sellerProfile['foto_profile'] ?? '';

$seller['nama'] = $seller_name;

// ── Ambil stand milik seller ini ──
$stands_result = $conn->query("SELECT * FROM stands WHERE seller_id = $sid ORDER BY id ASC");
$stands = [];
while ($row = $stands_result->fetch_assoc()) $stands[] = $row;
$stand_ids = array_column($stands, 'id');

// ── Stats global seller ──
$total_stands = count($stands);
$total_menu   = 0;
$total_orders = 0;
$total_reviews = 0;
$avg_rating   = 0;

if ($stand_ids) {
    $ids_str = implode(',', $stand_ids);
    // Ambil data dari database
    $r = $conn->query("SELECT COUNT(*) as c, SUM(total_orders) as o FROM menu_items WHERE stand_id IN ($ids_str)");
    $row = $r->fetch_assoc(); $total_menu = $row['c']; $total_orders = $row['o'] ?? 0;
    $r = $conn->query("SELECT COUNT(*) as c FROM reviews r JOIN menu_items m ON m.id = r.menu_id WHERE m.stand_id IN ($ids_str)");
    $total_reviews = $r->fetch_assoc()['c'];
    $r = $conn->query("SELECT AVG(rating) as avg FROM stands WHERE seller_id = $sid AND rating > 0");
    $avg_rating = round($r->fetch_assoc()['avg'] ?? 0, 1);
}

// ── Reviews terbaru ──
$recent_reviews = [];
if ($stand_ids) {
    $ids_str = implode(',', $stand_ids);
    // Ambil data dari database 
    $r = $conn->query("
        SELECT rv.rating, rv.komentar, rv.created_at, u.nama as user_nama,
               mi.nama as item_nama, s.nama as stand_nama
        FROM reviews rv
        JOIN users u ON u.id = rv.user_id
        JOIN menu_items mi ON mi.id = rv.menu_id
        JOIN stands s ON s.id = mi.stand_id
        WHERE s.id IN ($ids_str)
        ORDER BY rv.created_at DESC LIMIT 5
    ");
    while ($row = $r->fetch_assoc()) $recent_reviews[] = $row;
}

// ── Menu items per stand ──
$menu_by_stand = [];
if ($stand_ids) {
    $ids_str = implode(',', $stand_ids);
    $r = $conn->query("SELECT * FROM menu_items WHERE stand_id IN ($ids_str) ORDER BY stand_id, id ASC");
    while ($row = $r->fetch_assoc()) $menu_by_stand[$row['stand_id']][] = $row;
}

$conn->close();

$kategori_opts = ['berat'=>'Makanan Berat','ringan'=>'Makanan Ringan','minuman'=>'Minuman','dessert'=>'Dessert'];
$icon_map = ['berat'=>'B','ringan'=>'R','minuman'=>'M','dessert'=>'D'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Seller Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body, body *{font-family:'Inter',sans-serif !important;}
body{background:#0a0a0a;color:#e5e7eb;min-height:100vh;display:flex;}

/* ── SIDEBAR ── */
.sidebar{width:220px;background:#111827;border-right:1px solid #1f2937;display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:50;box-shadow:2px 0 18px rgba(0,0,0,0.28);}
.sidebar-logo{padding:24px 20px;border-bottom:1px solid #1f2937;}
.sidebar-logo .brand{font-weight:800;font-size:13px;color:#f9fafb;text-transform:uppercase;letter-spacing:0.08em;}
.sidebar-logo .sub{font-size:11px;color:#86a84a;margin-top:2px;}
.sidebar-seller{padding:16px 20px;border-bottom:1px solid #1f2937;}
.seller-avatar{width:36px;height:36px;background:linear-gradient(135deg,#4b601d,#6f8a34);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:8px;}
.seller-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;}
.seller-name{font-size:12px;font-weight:700;color:#f9fafb;}
.seller-role{font-size:11px;color:#86a84a;text-transform:uppercase;letter-spacing:0.1em;}
.sidebar-nav{flex:1;padding:16px 0;overflow-y:auto;}
.nav-item{display:flex;align-items:center;gap:10px;padding:11px 20px;font-size:11px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:#94a3b8;cursor:pointer;transition:all 0.15s;text-decoration:none;border-left:2px solid transparent;}
.nav-item:hover{color:#f9fafb;background:#1f2937;}
.nav-item.active{color:#f9fafb;background:#1f2937;border-left-color:#6f8a34;}
.nav-icon{font-size:14px;width:18px;text-align:center;}
.sidebar-footer{padding:16px 20px;border-top:1px solid #1f2937;}
.btn-logout{display:block;width:100%;padding:10px;background:#0f172a;color:#cbd5e1;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;border:1px solid #1f2937;cursor:pointer;text-align:center;text-decoration:none;transition:all 0.2s;}
.btn-logout:hover{background:#1f2937;color:#f9fafb;}

/* ── MAIN ── */
.main{margin-left:220px;flex:1;min-height:100vh;}
.topbar{background:#111827;border-bottom:1px solid #1f2937;padding:18px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:40;box-shadow:0 1px 12px rgba(0,0,0,0.18);}
.page-title{font-weight:800;font-size:1.1rem;color:#f9fafb;letter-spacing:0.05em;}
.topbar-right{display:flex;align-items:center;gap:12px;}
.topbar-right a{font-size:11px;color:#86a84a;text-decoration:none;letter-spacing:0.08em;text-transform:uppercase;transition:color 0.2s;}
.topbar-right a:hover{color:#b8d36e;}
.content{padding:28px 32px;}

/* ── SECTIONS ── */
.section{display:none;}
.section.active{display:block;}

/* ── STATS ── */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
.stat-card{background:#111827;border:1px solid #1f2937;padding:20px;transition:border-color 0.2s,transform 0.2s;}
.stat-card:hover{border-color:#6f8a34;transform:translateY(-1px);}
.stat-label{font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#7c8aa0;margin-bottom:10px;}
.stat-value{font-weight:800;font-size:2rem;color:#f9fafb;line-height:1;}
.stat-sub{font-size:12px;color:#7c8aa0;margin-top:6px;}
.stat-icon{font-size:22px;margin-bottom:8px;}

/* ── SECTION HEADER ── */
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.section-title{font-weight:800;font-size:1rem;color:#f9fafb;letter-spacing:0.05em;}
.btn-primary{padding:9px 20px;background:linear-gradient(135deg,#4b601d,#6f8a34);color:#fff;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;border:none;cursor:pointer;transition:opacity 0.2s,transform 0.2s;}
.btn-primary:hover{opacity:0.92;transform:translateY(-1px);}
.btn-sm{padding:6px 14px;font-size:10px;letter-spacing:0.08em;}
.btn-danger{background:#2a0b0b;color:#ffb4b4;border:1px solid #4a1a1a;}
.btn-danger:hover{background:#3a1111;opacity:1;}
.btn-edit{background:#111827;color:#c7d2fe;border:1px solid #27304a;}
.btn-edit:hover{background:#1f2937;opacity:1;}

/* ── STAND CARDS ── */
.stand-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;}
.stand-card{background:#111827;border:1px solid #1f2937;overflow:hidden;}
.stand-card-top{padding:18px 20px;border-bottom:1px solid #1f2937;display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}
.stand-info{}
.stand-name{font-weight:700;font-size:14px;color:#f9fafb;margin-bottom:4px;}
.stand-cat{font-size:10px;font-weight:600;letter-spacing:0.12em;text-transform:uppercase;color:#cbd5e1;background:#0f172a;padding:3px 8px;display:inline-block;border:1px solid #1f2937;}
.stand-rating{display:flex;align-items:center;gap:4px;margin-top:8px;font-size:13px;}
.stand-actions{display:flex;gap:8px;flex-shrink:0;}
.stand-card-body{padding:16px 20px;}
.stand-stats{display:flex;gap:20px;}
.ss-item{text-align:center;}
.ss-val{font-weight:800;font-size:1.2rem;color:#f9fafb;}
.ss-lbl{font-size:9px;color:#7c8aa0;letter-spacing:0.1em;text-transform:uppercase;margin-top:2px;}

/* ── MENU TABLE ── */
.stand-menu-section{margin-bottom:28px;}
.smenu-header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:#111827;border:1px solid #1f2937;border-bottom:none;cursor:pointer;}
.smenu-title{font-weight:700;font-size:12px;color:#f9fafb;letter-spacing:0.08em;text-transform:uppercase;}
.smenu-toggle{font-size:11px;color:#7c8aa0;}
.menu-table{width:100%;border-collapse:collapse;background:#111827;border:1px solid #1f2937;}
.menu-table th{font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#7c8aa0;padding:10px 14px;border-bottom:1px solid #1f2937;text-align:left;}
.menu-table td{padding:12px 14px;border-bottom:1px solid #1f2937;font-size:13px;color:#d1d5db;vertical-align:middle;}
.menu-table tr:last-child td{border-bottom:none;}
.menu-table tr:hover td{background:#0f172a;}
.item-emoji{width:20px;height:20px;display:inline-flex;align-items:center;justify-content:center;border-radius:999px;background:#0f172a;border:1px solid #1f2937;color:#86a84a;font-size:10px;font-weight:800;letter-spacing:0.08em;margin-right:8px;flex-shrink:0;}
.item-name-cell{font-weight:600;font-size:13px;color:#f9fafb;}
.price-cell{font-weight:700;color:#f9fafb;}
.rating-cell{font-size:12px;}
.actions-cell{display:flex;gap:8px;}

/* ── REVIEWS ── */
.review-card{background:#111827;border:1px solid #1f2937;padding:16px 20px;margin-bottom:12px;}
.rc-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:8px;}
.rc-user{font-weight:700;font-size:13px;color:#f9fafb;}
.rc-date{font-size:11px;color:#7c8aa0;}
.rc-item{font-size:12px;color:#9ca3af;letter-spacing:0.05em;margin-bottom:6px;}
.rc-stars{font-size:13px;margin-bottom:6px;}
.rc-comment{font-size:13px;color:#aaa;line-height:1.5;}

/* ── FORMS (MODAL) ── */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:200;align-items:center;justify-content:center;padding:20px;}
.modal-bg.open{display:flex;}
.modal-form{background:#111827;border:1px solid #1f2937;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;padding:28px;}
.mf-title{font-weight:800;font-size:1rem;color:#f9fafb;margin-bottom:24px;letter-spacing:0.05em;}
.form-field{margin-bottom:16px;}
.form-field label{font-size:10px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#94a3b8;display:block;margin-bottom:7px;}
.form-field input,.form-field select,.form-field textarea{width:100%;padding:10px 14px;border:1.5px solid #1f2937;font-size:14px;color:#f9fafb;background:#0f172a;outline:none;transition:border-color 0.2s,box-shadow 0.2s;}
.form-field input:focus,.form-field select:focus,.form-field textarea:focus{border-color:#6f8a34;box-shadow:0 0 0 3px rgba(75,96,29,0.2);}
.form-field select option{background:#0f172a;}
.form-actions{display:flex;gap:10px;margin-top:20px;}
.btn-cancel{padding:10px 20px;background:transparent;color:#94a3b8;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;border:1px solid #1f2937;cursor:pointer;transition:all 0.2s;}
.btn-cancel:hover{color:#f9fafb;border-color:#6f8a34;}
.form-msg{font-size:12px;margin-top:10px;padding:8px 12px;}
.form-msg.ok{background:#052e16;color:#86efac;border:1px solid #14532d;}
.form-msg.err{background:#2a0b0b;color:#ffb4b4;border:1px solid #4a1a1a;}

/* ── EMPTY STATE ── */
.empty-dash{text-align:center;padding:60px 20px;color:#333;}
.empty-dash .ei{font-size:40px;margin-bottom:12px;opacity:0.3;}
.empty-dash p{font-family:'Oxanium',sans-serif;font-size:12px;letter-spacing:0.1em;text-transform:uppercase;}

/* ── SETTINGS ── */
.setting-wrap{max-width:760px;background:#111827;border:1px solid #1f2937;padding:22px;}
.setting-desc{font-size:12px;color:#94a3b8;margin:8px 0 18px;line-height:1.5;}
.setting-form{display:grid;gap:14px;}
.setting-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.setting-field{display:flex;flex-direction:column;gap:7px;}
.setting-field label{font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#9ca3af;}
.setting-field input,.setting-field textarea{width:100%;background:#0f172a;color:#e5e7eb;border:1px solid #1f2937;padding:10px 12px;font-size:13px;outline:none;transition:border-color 0.2s;}
.setting-field input:focus,.setting-field textarea:focus{border-color:#6f8a34;}
.setting-field textarea{min-height:120px;resize:vertical;}
.setting-field input[readonly]{background:#0b1220;color:#94a3b8;cursor:not-allowed;}
.setting-help{font-size:11px;color:#64748b;}
.profile-upload{display:flex;flex-direction:column;align-items:flex-start;gap:10px;margin-bottom:6px;}
.profile-preview{width:96px;height:96px;border-radius:50%;border:2px solid #334155;background:#0f172a;object-fit:cover;display:block;}
.btn-save{width:fit-content;padding:10px 16px;background:linear-gradient(135deg,#4b601d,#6f8a34);color:#fff;border:1px solid #6f8a34;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;}
.btn-save:hover{filter:brightness(1.06);}

/* ── RESPONSIVE ── */
@media(max-width:900px){.stats-grid{grid-template-columns:repeat(2,1fr)}.stand-grid{grid-template-columns:1fr}}
@media(max-width:640px){.sidebar{display:none}.main{margin-left:0}.content{padding:16px}.setting-grid{grid-template-columns:1fr}}

/* ── LIGHT THEME ── */
body.light-theme{background:#ffffff;color:#1f2329;}
body.light-theme .sidebar{background:#f8fafc;border-right-color:#d7dde5;}
body.light-theme .sidebar-logo .brand{color:#0f172a;}
body.light-theme .sidebar-logo .sub{color:#4b601d;}
body.light-theme .seller-name{color:#0f172a;}
body.light-theme .seller-role{color:#4b601d;}
body.light-theme .nav-item{color:#475569;}
body.light-theme .nav-item:hover,body.light-theme .nav-item.active{color:#0f172a;background:#eef2f6;}
body.light-theme .nav-item.active{border-left-color:#4b601d;}
body.light-theme .topbar{background:#f8fafc;border-bottom-color:#d7dde5;}
body.light-theme .page-title{color:#0f172a;}
body.light-theme .topbar-right a{color:#4b601d;}
body.light-theme .stat-card{background:#ffffff;border-color:#d7dde5;}
body.light-theme .stat-card:hover{border-color:#4b601d;}
body.light-theme .stat-label,body.light-theme .stat-sub{color:#475569;}
body.light-theme .stat-value{color:#0f172a;}
body.light-theme .stand-grid,body.light-theme .stand-card{background:#ffffff;border-color:#d7dde5;}
body.light-theme .stand-card-top{border-bottom-color:#d7dde5;}
body.light-theme .stand-name{color:#0f172a;}
body.light-theme .stand-cat{background:#f1f5f9;color:#0f172a;border-color:#d7dde5;}
body.light-theme .stand-stats{color:#0f172a;}
body.light-theme .ss-val{color:#0f172a;}
body.light-theme .ss-lbl{color:#475569;}
body.light-theme .smenu-header{background:#ffffff;border-color:#d7dde5;}
body.light-theme .smenu-title{color:#0f172a;}
body.light-theme .smenu-toggle{color:#475569;}
body.light-theme .menu-table{background:#ffffff;border-color:#d7dde5;}
body.light-theme .menu-table th,body.light-theme .menu-table td{border-color:#d7dde5;}
body.light-theme .menu-table th{color:#475569;}
body.light-theme .menu-table td{color:#1f2329;}
body.light-theme .menu-table tr:hover td{background:#f8fafc;}
body.light-theme .item-emoji{background:#f1f5f9;border-color:#d7dde5;color:#4b601d;}
body.light-theme .item-name-cell{color:#0f172a;}
body.light-theme .review-card{background:#ffffff;border-color:#d7dde5;}
body.light-theme .rc-user{color:#0f172a;}
body.light-theme .rc-date{color:#475569;}
body.light-theme .rc-item{color:#64748b;}
body.light-theme .rc-comment{color:#475569;}
body.light-theme .setting-wrap{background:#ffffff;border-color:#d7dde5;}
body.light-theme .setting-desc{color:#475569;}
body.light-theme .setting-field label{color:#0f172a;}
body.light-theme .setting-field input,body.light-theme .setting-field textarea{background:#ffffff;color:#0f172a;border-color:#d7dde5;}
body.light-theme .setting-field input:focus,body.light-theme .setting-field textarea:focus{border-color:#4b601d;box-shadow:0 0 0 3px rgba(75,96,29,0.15);}
body.light-theme .setting-field input[readonly]{background:#f1f5f9;color:#64748b;}
body.light-theme .setting-help{color:#475569;}
body.light-theme .profile-preview{border-color:#d7dde5;background:#f1f5f9;}
body.light-theme .btn-logout{background:#f8fafc;color:#1f2937;border-color:#d7dde5;}
body.light-theme .btn-logout:hover{background:#eef2f6;color:#0f172a;}
body.light-theme .modal-form{background:#ffffff;border-color:#d7dde5;}
body.light-theme .mf-title{color:#0f172a;}
body.light-theme .form-field label{color:#0f172a;}
body.light-theme .form-field input,body.light-theme .form-field select,body.light-theme .form-field textarea{background:#ffffff;color:#0f172a;border-color:#d7dde5;}
body.light-theme .form-field input:focus,body.light-theme .form-field select:focus,body.light-theme .form-field textarea:focus{border-color:#4b601d;box-shadow:0 0 0 3px rgba(75,96,29,0.15);}
body.light-theme .form-field select option{background:#ffffff;color:#0f172a;}
body.light-theme .btn-cancel{color:#0f172a;border-color:#d7dde5;}
body.light-theme .btn-cancel:hover{color:#0f172a;border-color:#4b601d;}
body.light-theme .section-title{color:#0f172a;}
body.light-theme .empty-dash p{color:#0f172a;}
body.light-theme .theme-wrap{border-color:#d7dde5;}
body.light-theme .theme-title{color:#0f172a;}
body.light-theme .theme-option{color:#0f172a;}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="brand">SMKN 1 SURABAYA</div>
    <div class="sub">Seller Dashboard</div>
  </div>
  <div class="sidebar-seller">
    <div class="seller-avatar" id="sellerAvatarSidebar">
      <?php if (!empty($seller_foto_profile)): ?>
        <img src="<?= htmlspecialchars($seller_foto_profile) ?>" alt="Foto Profil Seller">
      <?php else: ?>
        S
      <?php endif; ?>
    </div>
    <div class="seller-name"><?= htmlspecialchars($seller_name) ?></div>
    <div class="seller-role">Seller</div>
  </div>
  <nav class="sidebar-nav">
    <a class="nav-item active" data-section="overview" onclick="showSection('overview')">
      <span class="nav-icon"></span> Overview
    </a>
    <a class="nav-item" data-section="stands" onclick="showSection('stands')">
      <span class="nav-icon"></span> My Stands
    </a>
    <a class="nav-item" data-section="menu" onclick="showSection('menu')">
      <span class="nav-icon"></span> Menu Items
    </a>
    <a class="nav-item" data-section="reviews" onclick="showSection('reviews')">
      <span class="nav-icon"></span> Reviews
    </a>
    <a class="nav-item" data-section="setting" onclick="showSection('setting')">
      <span class="nav-icon"></span> Setting
    </a>
  </nav>
  <div class="sidebar-footer">
    <a href="api/seller_logout.php" class="btn-logout">Logout →</a>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <div class="page-title" id="topbarTitle">Overview</div>
    <div class="topbar-right">
      <a href="page2.php" target="_blank">Lihat Halaman →</a>
    </div>
  </div>

  <div class="content">

    <!-- ══ OVERVIEW ══ -->
    <div class="section active" id="section-overview">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon"></div>
          <div class="stat-label">Total Stand</div>
          <div class="stat-value"><?= $total_stands ?></div>
          <div class="stat-sub">stand aktif</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"></div>
          <div class="stat-label">Menu Items</div>
          <div class="stat-value"><?= $total_menu ?></div>
          <div class="stat-sub">item di semua stand</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"></div>
          <div class="stat-label">Avg Rating</div>
          <div class="stat-value"><?= $avg_rating ?: '—' ?></div>
          <div class="stat-sub">rata-rata semua stand</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"></div>
          <div class="stat-label">Total Reviews</div>
          <div class="stat-value"><?= $total_reviews ?></div>
          <div class="stat-sub">dari semua item</div>
        </div>
      </div>

      <!-- Recent Reviews -->
      <div class="section-header">
        <div class="section-title">Reviews Terbaru</div>
      </div>
      <?php if (empty($recent_reviews)): ?>
        <div class="empty-dash"><div class="ei"></div><p>Belum ada reviews</p></div>
      <?php else: ?>
        <?php foreach ($recent_reviews as $rv): ?>
        <div class="review-card">
          <div class="rc-top">
            <div>
              <div class="rc-user"><?= htmlspecialchars($rv['user_nama']) ?></div>
              <div class="rc-item"><?= htmlspecialchars($rv['item_nama']) ?> · <?= htmlspecialchars($rv['stand_nama']) ?></div>
            </div>
            <div class="rc-date"><?= date('d M Y', strtotime($rv['created_at'])) ?></div>
          </div>
          <div class="rc-stars"><?= str_repeat('★', $rv['rating']) ?><span style="color:#333"><?= str_repeat('★', 5 - $rv['rating']) ?></span></div>
          <div class="rc-comment"><?= htmlspecialchars($rv['komentar']) ?></div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ══ STANDS ══ -->
    <div class="section" id="section-stands">
      <div class="section-header">
        <div class="section-title">My Stands</div>
        <button class="btn-primary" onclick="openAddStand()">+ Tambah Stand</button>
      </div>
      <?php if (empty($stands)): ?>
        <div class="empty-dash"><div class="ei"></div><p>Belum ada stand. Tambah sekarang!</p></div>
      <?php else: ?>
      <div class="stand-grid">
        <?php foreach ($stands as $stand): ?>
        <div class="stand-card">
          <div class="stand-card-top">
            <div class="stand-info">
              <div class="stand-name"><?= htmlspecialchars($stand['nama']) ?></div>
              <span class="stand-cat"><?= $kategori_opts[$stand['kategori']] ?? $stand['kategori'] ?></span>
              <div class="stand-rating">
                <?php for ($s=1;$s<=5;$s++): ?>
                <span style="color:<?= $s<=round($stand['rating'])?'#fff':'#333' ?>">★</span>
                <?php endfor; ?>
                <span style="font-size:11px;color:#444;margin-left:4px;">(<?= $stand['rating'] ?>)</span>
              </div>
            </div>
            <div class="stand-actions">
              <button class="btn-primary btn-sm btn-edit" onclick="openEditStand(<?= $stand['id'] ?>, '<?= addslashes($stand['nama']) ?>', '<?= $stand['kategori'] ?>', '<?= addslashes($stand['foto'] ?? '') ?>')">Edit</button>
              <button class="btn-primary btn-sm btn-danger" onclick="deleteStand(<?= $stand['id'] ?>)">Hapus</button>
            </div>
          </div>
          <div class="stand-card-body">
            <div class="stand-stats">
              <div class="ss-item">
                <div class="ss-val"><?= count($menu_by_stand[$stand['id']] ?? []) ?></div>
                <div class="ss-lbl">Menu</div>
              </div>
              <div class="ss-item">
                <div class="ss-val"><?= $stand['total_votes'] ?></div>
                <div class="ss-lbl">Votes</div>
              </div>
              <div class="ss-item">
                <div class="ss-val"><?= $stand['rating'] ?></div>
                <div class="ss-lbl">Rating</div>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══ MENU ITEMS ══ -->
    <div class="section" id="section-menu">
      <div class="section-header">
        <div class="section-title">Menu Items</div>
        <button class="btn-primary" onclick="openAddMenu()">+ Tambah Item</button>
      </div>
      <?php if (empty($stands)): ?>
        <div class="empty-dash"><div class="ei"></div><p>Buat stand dulu sebelum tambah menu</p></div>
      <?php else: ?>
        <?php foreach ($stands as $stand): ?>
        <div class="stand-menu-section">
          <div class="smenu-header" onclick="toggleMenuTable(<?= $stand['id'] ?>)">
            <div class="smenu-title"><?= $icon_map[$stand['kategori']] ?? '' ?> <?= htmlspecialchars($stand['nama']) ?></div>
            <div class="smenu-toggle" id="toggle-<?= $stand['id'] ?>">▲ tutup</div>
          </div>
          <div id="menu-table-<?= $stand['id'] ?>">
          <?php $items = $menu_by_stand[$stand['id']] ?? []; ?>
          <?php if (empty($items)): ?>
            <div style="background:#111;border:1px solid #1e1e1e;border-top:none;padding:20px;text-align:center;font-family:'Oxanium',sans-serif;font-size:11px;color:#333;letter-spacing:0.1em;">BELUM ADA ITEM</div>
          <?php else: ?>
          <table class="menu-table">
            <thead><tr>
              <th>Item</th><th>Harga</th><th>Rating</th><th>Votes</th><th>Aksi</th>
            </tr></thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td><span class="item-emoji"><?= $icon_map[$stand['kategori']] ?? '' ?></span><span class="item-name-cell"><?= htmlspecialchars($item['nama']) ?></span></td>
                <td class="price-cell">Rp <?= number_format($item['harga'],0,',','.') ?></td>
                <td class="rating-cell"><?= str_repeat('★', round($item['rating'])) ?><span style="color:#333"><?= str_repeat('★', 5-round($item['rating'])) ?></span> <span style="color:#444;font-size:11px;">(<?= $item['rating'] ?>)</span></td>
                <td style="color:#444;font-family:'Oxanium',sans-serif;font-size:12px;"><?= $item['total_votes'] ?></td>
                <td><div class="actions-cell">
                  <button class="btn-primary btn-sm btn-edit" onclick="openEditMenu(<?= $item['id'] ?>, '<?= addslashes($item['nama']) ?>', <?= $item['harga'] ?>, <?= $item['stand_id'] ?>, '<?= addslashes($item['foto'] ?? '') ?>')">Edit</button>
                  <button class="btn-primary btn-sm btn-danger" onclick="deleteMenu(<?= $item['id'] ?>)">Hapus</button>
                </div></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- ══ REVIEWS ══ -->
    <div class="section" id="section-reviews">
      <div class="section-header">
        <div class="section-title">Semua Reviews</div>
        <span style="font-family:'Oxanium',sans-serif;font-size:11px;color:#444;"><?= $total_reviews ?> total</span>
      </div>
      <div id="reviewsContainer">
        <?php if (empty($stand_ids)): ?>
          <div class="empty-dash"><div class="ei"></div><p>Belum ada reviews</p></div>
        <?php else: ?>
          <?php
          // require_once 'config.php';
          // $ids_str = implode(',', $stand_ids);
          // $r = $conn->query("
          //   SELECT rv.rating, rv.komentar, rv.created_at, u.nama as user_nama,
          //          mi.nama as item_nama, s.nama as stand_nama
          //   FROM reviews rv JOIN users u ON u.id=rv.user_id
          //   JOIN menu_items mi ON mi.id=rv.menu_id JOIN stands s ON s.id=mi.stand_id
          //   WHERE s.id IN ($ids_str) ORDER BY rv.created_at DESC
          // ");
          // $all_reviews = [];
          // while($row=$r->fetch_assoc()) $all_reviews[]=$row;
          // $conn->close();
          ?>
          <?php if (empty($all_reviews)): ?>
            <div class="empty-dash"><div class="ei"></div><p>Belum ada reviews</p></div>
          <?php else: ?>
            <?php foreach ($all_reviews as $rv): ?>
            <div class="review-card">
              <div class="rc-top">
                <div>
                  <div class="rc-user"><?= htmlspecialchars($rv['user_nama']) ?></div>
                  <div class="rc-item"><?= htmlspecialchars($rv['item_nama']) ?> · <?= htmlspecialchars($rv['stand_nama']) ?></div>
                </div>
                <div class="rc-date"><?= date('d M Y', strtotime($rv['created_at'])) ?></div>
              </div>
              <div class="rc-stars"><?= str_repeat('★',$rv['rating']) ?><span style="color:#333"><?= str_repeat('★',5-$rv['rating']) ?></span></div>
              <div class="rc-comment"><?= htmlspecialchars($rv['komentar']) ?></div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- ══ SETTING ══ -->
    <div class="section" id="section-setting">
      <div class="section-header"><div class="section-title">Setting Biodata Seller</div></div>
      <div class="setting-wrap">
        <p class="setting-desc">Atur biodata seller di sini. Email diambil dari akun login dan tidak dapat diubah.</p>
        <form class="setting-form" id="sellerSettingForm" enctype="multipart/form-data">
          <div class="profile-upload">
            <img src="<?= !empty($seller_foto_profile) ? htmlspecialchars($seller_foto_profile) : 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 96 96%22%3E%3Crect width=%2296%22 height=%2296%22 rx=%2248%22 fill=%22%231f2937%22/%3E%3Ccircle cx=%2248%22 cy=%2238%22 r=%2216%22 fill=%22%236f8a34%22/%3E%3Cpath d=%22M20 80c3-14 14-22 28-22s25 8 28 22%22 fill=%22%236f8a34%22/%3E%3C/svg%3E' ?>" alt="Preview Foto Profil" class="profile-preview" id="sellerProfilePreview">
            <div class="setting-field" style="max-width:320px;">
              <label for="sellerFotoProfile">Foto Profil</label>
              <input type="file" id="sellerFotoProfile" name="foto_profile" accept="image/png,image/jpeg,image/webp">
              <span class="setting-help">Upload JPG, PNG, atau WEBP. Maksimum 2MB.</span>
            </div>
          </div>

          <div class="setting-grid">
            <div class="setting-field">
              <label for="sellerNama">Username</label>
              <input type="text" id="sellerNama" name="nama" required maxlength="80" value="<?= htmlspecialchars($seller_name) ?>">
            </div>
            <div class="setting-field">
              <label for="sellerEmail">Gmail</label>
              <input type="email" id="sellerEmail" name="email" readonly value="<?= htmlspecialchars($seller_email) ?>">
              <span class="setting-help">Email akun login tidak dapat diganti.</span>
            </div>
          </div>

          <div class="setting-grid">
            <div class="setting-field">
              <label for="sellerTanggalLahir">Tanggal Lahir (Opsional)</label>
              <input type="date" id="sellerTanggalLahir" name="tanggal_lahir" value="<?= htmlspecialchars($seller_tanggal_lahir) ?>">
            </div>
          </div>

          <div class="setting-field">
            <label for="sellerDeskripsi">Deskripsi Diri</label>
            <textarea id="sellerDeskripsi" name="deskripsi" maxlength="1000" placeholder="Ceritakan singkat tentang dirimu..."><?= htmlspecialchars($seller_deskripsi) ?></textarea>
          </div>

          <button type="submit" class="btn-save">Simpan Biodata</button>
        </form>

        <div class="theme-wrap" style="margin-top:28px;padding-top:28px;border-top:1px solid #1f2937;">
          <div class="theme-title" style="font-weight:700;font-size:13px;color:#f9fafb;margin-bottom:14px;letter-spacing:0.05em;">Mode Tampilan</div>
          <div class="theme-options" id="themeOptions" style="display:flex;gap:16px;">
            <label class="theme-option" for="themeDark" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="radio" id="themeDark" name="theme_mode" value="dark" checked style="cursor:pointer;">
              <span style="font-size:12px;color:#d1d5db;">Mode Gelap</span>
            </label>
            <label class="theme-option" for="themeLight" style="display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="radio" id="themeLight" name="theme_mode" value="light" style="cursor:pointer;">
              <span style="font-size:12px;color:#d1d5db;">Mode Terang</span>
            </label>
          </div>
          <div class="setting-help" style="margin-top:8px;">Pilih Mode Terang untuk mengubah tema dashboard menjadi terang.</div>
        </div>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<!-- ══ MODAL: ADD/EDIT STAND ══ -->
<div class="modal-bg" id="modalStand">
  <div class="modal-form">
    <div class="mf-title" id="modalStandTitle">Tambah Stand</div>
    <input type="hidden" id="standId" value=""/>
    <div class="form-field"><label>Nama Stand</label><input type="text" id="standNama" placeholder="e.g. Warung Nasi Bu Endang"/></div>
    <div class="form-field"><label>Kategori</label>
      <select id="standKategori">
        <?php foreach ($kategori_opts as $k=>$v): ?><option value="<?=$k?>"><?=$v?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-field">
      <label>Foto Stand</label>
      <div id="standImgPreview" style="display:none;margin-bottom:10px;"><img id="standImgThumb" src="" style="width:100%;max-height:160px;object-fit:cover;border:1px solid #222;"/></div>
      <input type="file" id="standFotoFile" accept="image/*" onchange="previewImg(this,'standImgPreview','standImgThumb')" style="width:100%;padding:10px 14px;border:1.5px solid #222;background:#1a1a1a;color:#aaa;font-family:'Nunito',sans-serif;font-size:13px;cursor:pointer;"/>
      <div style="font-size:11px;color:#444;margin-top:5px;font-family:'Oxanium',sans-serif;">JPG/PNG/WEBP, maks 2MB. Kosongkan jika tidak ingin ubah foto.</div>
    </div>
    <div class="form-actions">
      <button class="btn-primary" onclick="saveStand()">Simpan</button>
      <button class="btn-cancel" onclick="closeModal('modalStand')">Batal</button>
    </div>
    <div class="form-msg" id="standMsg" style="display:none"></div>
  </div>
</div>

<!-- ══ MODAL: ADD/EDIT MENU ══ -->
<div class="modal-bg" id="modalMenu">
  <div class="modal-form">
    <div class="mf-title" id="modalMenuTitle">Tambah Menu Item</div>
    <input type="hidden" id="menuId" value=""/>
    <div class="form-field"><label>Stand</label>
      <select id="menuStandId">
        <?php foreach ($stands as $s): ?><option value="<?=$s['id']?>"><?= htmlspecialchars($s['nama']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-field"><label>Nama Item</label><input type="text" id="menuNama" placeholder="e.g. Nasi Rames Spesial"/></div>
    <div class="form-field"><label>Harga (Rp)</label><input type="number" id="menuHarga" placeholder="15000" min="0"/></div>
    <div class="form-field">
      <label>Foto Item</label>
      <div id="menuImgPreview" style="display:none;margin-bottom:10px;"><img id="menuImgThumb" src="" style="width:100%;max-height:160px;object-fit:cover;border:1px solid #222;"/></div>
      <input type="file" id="menuFotoFile" accept="image/*" onchange="previewImg(this,'menuImgPreview','menuImgThumb')" style="width:100%;padding:10px 14px;border:1.5px solid #222;background:#1a1a1a;color:#aaa;font-family:'Nunito',sans-serif;font-size:13px;cursor:pointer;"/>
      <div style="font-size:11px;color:#444;margin-top:5px;font-family:'Oxanium',sans-serif;">JPG/PNG/WEBP, maks 2MB. Kosongkan jika tidak ingin ubah foto.</div>
    </div>
    <div class="form-actions">
      <button class="btn-primary" onclick="saveMenu()">Simpan</button>
      <button class="btn-cancel" onclick="closeModal('modalMenu')">Batal</button>
    </div>
    <div class="form-msg" id="menuMsg" style="display:none"></div>
  </div>
</div>

<script>
// ── SECTION NAVIGATION ──
function showSection(name) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('section-' + name).classList.add('active');
  document.querySelector(`[data-section="${name}"]`).classList.add('active');
  const titles = { overview:'Overview', stands:'My Stands', menu:'Menu Items', reviews:'Reviews', setting:'Setting Biodata' };
  document.getElementById('topbarTitle').textContent = titles[name] || name;
}

function openSectionFromHash() {
  const hash = (window.location.hash || '').replace('#', '').toLowerCase();
  if (hash === 'setting' || hash === 'section-setting') {
    showSection('setting');
  }
}

openSectionFromHash();
window.addEventListener('hashchange', openSectionFromHash);

// document.querySelector("#tes").addEventListener("click", () => {
//   alert("hi");
// })

// ── TOGGLE MENU TABLE ──
function toggleMenuTable(standId) {
  const el = document.getElementById('menu-table-' + standId);
  const tog = document.getElementById('toggle-' + standId);
  const hidden = el.style.display === 'none';
  el.style.display = hidden ? 'block' : 'none';
  tog.textContent = hidden ? '▲ tutup' : '▼ buka';
}

// ── MODAL ──
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openModal(id)  { document.getElementById(id).classList.add('open'); }

// ── IMAGE PREVIEW ──
function previewImg(input, previewId, thumbId) {
  const file = input.files[0];
  if (!file) return;
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById(thumbId).src = e.target.result;
    document.getElementById(previewId).style.display = 'block';
  };
  reader.readAsDataURL(file);
}

// ── STAND CRUD ──
function openAddStand() {
  document.getElementById('standId').value = '';
  document.getElementById('standNama').value = '';
  document.getElementById('standFotoFile').value = '';
  document.getElementById('standImgPreview').style.display = 'none';
  document.getElementById('standMsg').style.display = 'none';
  document.getElementById('modalStandTitle').textContent = 'Tambah Stand';
  openModal('modalStand');
}
function openEditStand(id, nama, kat, foto) {
  document.getElementById('standId').value = id;
  document.getElementById('standNama').value = nama;
  document.getElementById('standKategori').value = kat;
  document.getElementById('standFotoFile').value = '';
  document.getElementById('standMsg').style.display = 'none';
  document.getElementById('modalStandTitle').textContent = 'Edit Stand';
  // Tampilkan foto lama kalau ada
  if (foto) {
    document.getElementById('standImgThumb').src = 'uploads/' + foto;
    document.getElementById('standImgPreview').style.display = 'block';
  } else {
    document.getElementById('standImgPreview').style.display = 'none';
  }
  openModal('modalStand');
}
function saveStand() {
  const id    = document.getElementById('standId').value;
  const nama  = document.getElementById('standNama').value.trim();
  const kat   = document.getElementById('standKategori').value;
  const fileInput = document.getElementById('standFotoFile');
  const msgEl = document.getElementById('standMsg');
  if (!nama) { showMsg(msgEl, 'Nama stand wajib diisi!', 'err'); return; }
  const fd = new FormData();
  fd.append('action', id ? 'edit' : 'add');
  fd.append('id', id); fd.append('nama', nama); fd.append('kategori', kat);
  if (fileInput.files[0]) fd.append('foto', fileInput.files[0]);
  fetch('api/toko_crud.php', { method:'POST', body:fd })
    .then(r=>r.json()).then(data => {
      if (data.error) { showMsg(msgEl, data.error, 'err'); return; }
      showMsg(msgEl, data.message, 'ok');
      setTimeout(() => { closeModal('modalStand'); location.reload(); }, 800);
    }).catch(() => showMsg(msgEl, 'Gagal menyimpan.', 'err'));
}
function deleteStand(id) {
  if (!confirm('Hapus stand ini? Semua menu item di dalamnya juga akan terhapus!')) return;
  const fd = new FormData(); fd.append('action','delete'); fd.append('id', id);
  fetch('api/toko_crud.php', { method:'POST', body:fd })
    .then(r=>r.json()).then(data => { if (data.success) location.reload(); else alert(data.error); });
}

// ── MENU CRUD ──
function openAddMenu() {
  document.getElementById('menuId').value = '';
  document.getElementById('menuNama').value = '';
  document.getElementById('menuHarga').value = '';
  document.getElementById('menuFotoFile').value = '';
  document.getElementById('menuImgPreview').style.display = 'none';
  document.getElementById('menuMsg').style.display = 'none';
  document.getElementById('modalMenuTitle').textContent = 'Tambah Menu Item';
  openModal('modalMenu');
}
function openEditMenu(id, nama, harga, standId, foto) {
  document.getElementById('menuId').value = id;
  document.getElementById('menuNama').value = nama;
  document.getElementById('menuHarga').value = harga;
  document.getElementById('menuStandId').value = standId;
  document.getElementById('menuFotoFile').value = '';
  document.getElementById('menuMsg').style.display = 'none';
  document.getElementById('modalMenuTitle').textContent = 'Edit Menu Item';
  if (foto) {
    document.getElementById('menuImgThumb').src = 'uploads/' + foto;
    document.getElementById('menuImgPreview').style.display = 'block';
  } else {
    document.getElementById('menuImgPreview').style.display = 'none';
  }
  openModal('modalMenu');
}
function saveMenu() {
  const id      = document.getElementById('menuId').value;
  const standId = document.getElementById('menuStandId').value;
  const nama    = document.getElementById('menuNama').value.trim();
  const harga   = document.getElementById('menuHarga').value;
  const fileInput = document.getElementById('menuFotoFile');
  const msgEl   = document.getElementById('menuMsg');
  if (!nama || !harga) { showMsg(msgEl, 'Nama dan harga wajib diisi!', 'err'); return; }
  const fd = new FormData();
  fd.append('action', id ? 'edit' : 'add');
  fd.append('id', id); fd.append('stand_id', standId);
  fd.append('nama', nama); fd.append('harga', harga);
  if (fileInput.files[0]) fd.append('foto', fileInput.files[0]);
  fetch('api/item_crud.php', { method:'POST', body:fd })
    .then(r=>r.json()).then(data => {
      if (data.error) { showMsg(msgEl, data.error, 'err'); return; }
      showMsg(msgEl, data.message, 'ok');
      setTimeout(() => { closeModal('modalMenu'); location.reload(); }, 800);
    }).catch(() => showMsg(msgEl, 'Gagal menyimpan.', 'err'));
}
function deleteMenu(id) {
  if (!confirm('Hapus menu item ini?')) return;
  const fd = new FormData(); fd.append('action','delete'); fd.append('id', id);
  fetch('api/item_crud.php', { method:'POST', body:fd })
    .then(r=>r.json()).then(data => { if (data.success) location.reload(); else alert(data.error); });
}

// ── SELLER SETTINGS ──
const sellerSettingForm = document.getElementById('sellerSettingForm');
const sellerProfileInput = document.getElementById('sellerFotoProfile');
const sellerProfilePreview = document.getElementById('sellerProfilePreview');

if (sellerProfileInput && sellerProfilePreview) {
  sellerProfileInput.addEventListener('change', () => {
    const file = sellerProfileInput.files && sellerProfileInput.files[0];
    if (!file) return;
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
      alert('Format foto harus JPG, PNG, atau WEBP.');
      sellerProfileInput.value = '';
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      alert('Ukuran foto maksimal 2MB.');
      sellerProfileInput.value = '';
      return;
    }
    sellerProfilePreview.src = URL.createObjectURL(file);
  });
}

if (sellerSettingForm) {
  sellerSettingForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const nama = document.getElementById('sellerNama').value.trim();
    const tanggal_lahir = document.getElementById('sellerTanggalLahir').value;
    const deskripsi = document.getElementById('sellerDeskripsi').value.trim();
    const fotoFile = sellerProfileInput && sellerProfileInput.files ? sellerProfileInput.files[0] : null;

    if (!nama) {
      alert('Username wajib diisi.');
      return;
    }

    const fd = new FormData();
    fd.append('nama', nama);
    fd.append('tanggal_lahir', tanggal_lahir);
    fd.append('deskripsi', deskripsi);
    if (fotoFile) fd.append('foto_profile', fotoFile);

    fetch('api/seller_profile.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (!d.success) {
          alert(d.error || 'Gagal menyimpan biodata.');
          return;
        }

        alert(d.message || 'Biodata seller berhasil disimpan.');
        const sellerNameEl = document.querySelector('.seller-name');
        if (sellerNameEl) sellerNameEl.textContent = nama;
        if (d.photo_url) {
          sellerProfilePreview.src = d.photo_url;
          const sellerAvatar = document.getElementById('sellerAvatarSidebar');
          if (sellerAvatar) sellerAvatar.innerHTML = `<img src="${d.photo_url}" alt="Foto Profil Seller">`;
        }
      })
      .catch(() => alert('Koneksi gagal.'));
  });
}

function showMsg(el, msg, type) {
  el.textContent = msg; el.className = 'form-msg ' + type; el.style.display = 'block';
}

// Close modal on overlay click
document.querySelectorAll('.modal-bg').forEach(bg => {
  bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
});

// ── THEME ──
const THEME_KEY = 'seller_dashboard_theme_<?= $sid ?>';

function applyTheme(mode) {
  if (mode === 'light') {
    document.body.classList.add('light-theme');
  } else {
    document.body.classList.remove('light-theme');
  }

  const darkRadio = document.getElementById('themeDark');
  const lightRadio = document.getElementById('themeLight');
  if (darkRadio && lightRadio) {
    darkRadio.checked = mode !== 'light';
    lightRadio.checked = mode === 'light';
  }
}

const savedTheme = localStorage.getItem(THEME_KEY) || 'dark';
applyTheme(savedTheme);

const themeOptionsWrap = document.getElementById('themeOptions');
if (themeOptionsWrap) {
  themeOptionsWrap.addEventListener('change', (e) => {
    const selected = e.target && e.target.value === 'light' ? 'light' : 'dark';
    localStorage.setItem(THEME_KEY, selected);
    applyTheme(selected);
  });
}
</script>
</body>
</html>