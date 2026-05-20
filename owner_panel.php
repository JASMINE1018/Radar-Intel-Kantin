<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config.php';

// Cek login owner
// -- Cek akses atau request masuk
if (!isset($_SESSION['owner_id'])) { header('Location: login.php'); exit; }
$owner_id = (int)$_SESSION['owner_id'];

// Pastikan tabel profil owner tersedia
// Pastikan struktur tabel tersedia 
$conn->query("CREATE TABLE IF NOT EXISTS owner_profiles (
  owner_id INT PRIMARY KEY,
  tanggal_lahir DATE NULL,
  deskripsi TEXT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_owner_profiles_owner FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
)");

$fotoCol = $conn->query("SHOW COLUMNS FROM owner_profiles LIKE 'foto_profile'");
if ($fotoCol && $fotoCol->num_rows === 0) {
  $conn->query("ALTER TABLE owner_profiles ADD COLUMN foto_profile VARCHAR(255) NULL AFTER deskripsi");
}

// Data owner login
$ownerStmt = $conn->prepare("SELECT id, nama, email FROM owners WHERE id = ? LIMIT 1");
$ownerStmt->bind_param('i', $owner_id);
$ownerStmt->execute();
$ownerData = $ownerStmt->get_result()->fetch_assoc();
$ownerStmt->close();

$profileStmt = $conn->prepare("SELECT tanggal_lahir, deskripsi, foto_profile FROM owner_profiles WHERE owner_id = ? LIMIT 1");
$profileStmt->bind_param('i', $owner_id);
$profileStmt->execute();
$ownerProfile = $profileStmt->get_result()->fetch_assoc();
$profileStmt->close();

$owner_nama = $ownerData['nama'] ?? ($_SESSION['owner_nama'] ?? 'Owner');
$owner_email = $ownerData['email'] ?? '';
$owner_tanggal_lahir = $ownerProfile['tanggal_lahir'] ?? '';
$owner_deskripsi = $ownerProfile['deskripsi'] ?? '';
$owner_foto_profile = $ownerProfile['foto_profile'] ?? '';
$defaultProfilePreview = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 96 96'%3E%3Crect width='96' height='96' rx='48' fill='%231f2937'/%3E%3Ccircle cx='48' cy='38' r='16' fill='%236f8a34'/%3E%3Cpath d='M20 80c3-14 14-22 28-22s25 8 28 22' fill='%236f8a34'/%3E%3C/svg%3E";

// ── Data sellers ──
$sellers_pending = [];
$sellers_active  = [];
$sellers_rejected = [];
// -- Ambil data dari database --
$r = $conn->query("SELECT s.*, COUNT(st.id) as total_stands FROM sellers s LEFT JOIN stands st ON st.seller_id = s.id GROUP BY s.id ORDER BY s.created_at DESC");
while ($row = $r->fetch_assoc()) {
    if ($row['status'] === 'pending')  $sellers_pending[]  = $row;
    elseif ($row['status'] === 'active') $sellers_active[] = $row;
    else $sellers_rejected[] = $row;
}

// ── Data users ──
$users = [];
$r = $conn->query("SELECT u.*, COUNT(DISTINCT rv.id) as total_reviews, COUNT(DISTINCT rs.id) as total_ratings FROM users u LEFT JOIN reviews rv ON rv.user_id = u.id LEFT JOIN ratings_stand rs ON rs.user_id = u.id GROUP BY u.id ORDER BY u.created_at DESC");
while ($row = $r->fetch_assoc()) $users[] = $row;

// ── Data stands ──
$stands = [];
// ambil data dari database
$r = $conn->query("SELECT s.*, sl.nama as seller_nama, COUNT(m.id) as total_menu FROM stands s LEFT JOIN sellers sl ON sl.id = s.seller_id LEFT JOIN menu_items m ON m.stand_id = s.id GROUP BY s.id ORDER BY s.id DESC");
while ($row = $r->fetch_assoc()) $stands[] = $row;

// ── Stats ──
$total_users    = count($users);
$total_sellers  = count($sellers_active) + count($sellers_pending);
$total_pending  = count($sellers_pending);
$total_stands   = count($stands);
$r = $conn->query("SELECT COUNT(*) as c FROM reviews"); 
$total_reviews  = $r->fetch_assoc()['c'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Owner Panel</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body, body *{font-family:'Inter',sans-serif !important;}
body{background:#0a0a0a;color:#e5e7eb;min-height:100vh;display:flex;}
/* SIDEBAR */
.sidebar{width:220px;background:#111827;border-right:1px solid #1f2937;display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:50;box-shadow:2px 0 18px rgba(0,0,0,0.28);}
.sidebar-logo{padding:24px 20px;border-bottom:1px solid #1f2937;}
.brand{font-weight:800;font-size:13px;color:#f9fafb;text-transform:uppercase;letter-spacing:0.08em;}
.brand-sub{font-size:11px;color:#86a84a;margin-top:2px;letter-spacing:0.1em;}
.sidebar-owner{padding:16px 20px;border-bottom:1px solid #1f2937;}
.owner-avatar{width:36px;height:36px;background:linear-gradient(135deg,#4b601d,#6f8a34);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:8px;}
.owner-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;}
.owner-name{font-size:12px;font-weight:700;color:#f9fafb;}
.owner-role{font-size:10px;color:#86a84a;text-transform:uppercase;letter-spacing:0.12em;}
.sidebar-nav{flex:1;padding:16px 0;}
.nav-item{display:flex;align-items:center;gap:10px;padding:11px 20px;font-size:11px;font-weight:600;letter-spacing:0.1em;text-transform:uppercase;color:#94a3b8;cursor:pointer;transition:all 0.15s;border-left:2px solid transparent;}
.nav-item:hover{color:#f9fafb;background:#1f2937;}
.nav-item.active{color:#f9fafb;background:#1f2937;border-left-color:#6f8a34;}
.nav-icon{font-size:14px;width:18px;text-align:center;}
.badge{background:#4b601d;color:#fff;font-size:9px;padding:2px 6px;border-radius:10px;margin-left:auto;font-weight:700;}
.badge.red{background:#c2410c;}
.sidebar-footer{padding:16px 20px;border-top:1px solid #1f2937;}
.btn-logout{display:block;width:100%;padding:10px;background:#0f172a;color:#cbd5e1;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;border:1px solid #1f2937;cursor:pointer;text-align:center;text-decoration:none;transition:all 0.2s;}
.btn-logout:hover{background:#1f2937;color:#f9fafb;}
/* MAIN */
.main{margin-left:220px;flex:1;}
.topbar{background:#111827;border-bottom:1px solid #1f2937;padding:18px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:40;box-shadow:0 1px 12px rgba(0,0,0,0.18);}
.page-title{font-weight:800;font-size:1.1rem;color:#f9fafb;}
.topbar-link{font-size:11px;color:#86a84a;text-decoration:none;letter-spacing:0.08em;text-transform:uppercase;transition:color 0.2s;}
.topbar-link:hover{color:#b8d36e;}
.content{padding:28px 32px;}
/* SECTIONS */
.section{display:none;}
.section.active{display:block;}
/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:28px;}
.stat-card{background:#111827;border:1px solid #1f2937;padding:18px;box-shadow:0 8px 24px rgba(0,0,0,0.18);}
.stat-label{font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#7c8aa0;margin-bottom:8px;}
.stat-value{font-weight:800;font-size:1.8rem;color:#f9fafb;line-height:1;}
.stat-sub{font-size:11px;color:#7c8aa0;margin-top:5px;}
.stat-icon{font-size:18px;margin-bottom:6px;}
.stat-card.highlight{border-color:#6f8a34;}
.stat-card.highlight .stat-value{color:#b8d36e;}
/* SECTION HEADER */
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;}
.section-title{font-weight:800;font-size:1rem;color:#f9fafb;}
/* TABLES */
.data-table{width:100%;border-collapse:collapse;background:#111827;border:1px solid #1f2937;margin-bottom:24px;}
.data-table th{font-size:9px;font-weight:700;letter-spacing:0.15em;text-transform:uppercase;color:#7c8aa0;padding:12px 16px;border-bottom:1px solid #1f2937;text-align:left;}
.data-table td{padding:13px 16px;border-bottom:1px solid #1f2937;font-size:13px;color:#d1d5db;vertical-align:middle;}
.data-table tr:last-child td{border-bottom:none;}
.data-table tr:hover td{background:#0f172a;}
.name-cell{font-weight:700;font-size:13px;color:#f9fafb;}
.email-cell{font-size:12px;color:#7c8aa0;}
/* STATUS BADGES */
.badge-status{font-size:9px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;padding:4px 10px;display:inline-block;}
.badge-pending{background:#2b2110;color:#fbbf24;border:1px solid #4a3b1f;}
.badge-active{background:#052e16;color:#86efac;border:1px solid #14532d;}
.badge-rejected{background:#2a0b0b;color:#ffb4b4;border:1px solid #4a1a1a;}
/* ACTION BUTTONS */
.btn-approve{padding:6px 14px;background:linear-gradient(135deg,#4b601d,#6f8a34);color:#fff;font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:1px solid #6f8a34;cursor:pointer;transition:all 0.2s;}
.btn-approve:hover{filter:brightness(1.06);}
.btn-reject{padding:6px 14px;background:#111827;color:#fecaca;font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:1px solid #4a1a1a;cursor:pointer;transition:all 0.2s;}
.btn-reject:hover{background:#1f2937;}
.btn-delete{padding:6px 14px;background:#111827;color:#fecaca;font-size:10px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:1px solid #4a1a1a;cursor:pointer;transition:all 0.2s;}
.btn-delete:hover{background:#1f2937;}
.actions-cell{display:flex;gap:6px;align-items:center;}
/* EMPTY */
.empty-row td{text-align:center;color:#7c8aa0 !important;font-size:11px;letter-spacing:0.1em;padding:32px !important;}
/* PENDING ALERT */
.pending-alert{background:#2b2110;border:1px solid #4a3b1f;color:#fbbf24;padding:14px 18px;margin-bottom:20px;font-size:12px;letter-spacing:0.06em;display:flex;align-items:center;gap:10px;}
/* TABS inside section */
.sub-tabs{display:flex;gap:0;margin-bottom:20px;border:1px solid #1f2937;overflow:hidden;width:fit-content;background:#111827;}
.sub-tab{padding:9px 20px;font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;border:none;background:transparent;color:#94a3b8;transition:all 0.2s;}
.sub-tab:not(:last-child){border-right:1px solid #1f2937;}
.sub-tab.active{background:#4b601d;color:#fff;}
.sub-tab:hover:not(.active){color:#f9fafb;background:#1f2937;}
.sub-section{display:none;}
.sub-section.active{display:block;}
/* SETTINGS */
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
.btn-save{width:fit-content;padding:10px 16px;background:linear-gradient(135deg,#4b601d,#6f8a34);color:#fff;border:1px solid #6f8a34;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;}
.btn-save:hover{filter:brightness(1.06);}
.profile-upload{display:flex;flex-direction:column;align-items:flex-start;gap:10px;margin-bottom:6px;}
.profile-preview{width:96px;height:96px;border-radius:50%;border:2px solid #334155;background:#0f172a;object-fit:cover;display:block;}
.profile-upload input[type="file"]{font-size:12px;color:#cbd5e1;}
.theme-wrap{margin-top:20px;padding-top:18px;border-top:1px solid #1f2937;}
.theme-title{font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#9ca3af;margin-bottom:10px;}
.theme-options{display:flex;gap:10px;flex-wrap:wrap;}
.theme-option{display:flex;align-items:center;gap:8px;background:#0f172a;border:1px solid #1f2937;padding:10px 12px;cursor:pointer;font-size:12px;color:#cbd5e1;}
.theme-option input{accent-color:#6f8a34;}

/* LIGHT THEME */
body.light-theme{background:#f4f6f8;color:#1f2937;}
body.light-theme .sidebar,
body.light-theme .topbar,
body.light-theme .stat-card,
body.light-theme .data-table,
body.light-theme .setting-wrap,
body.light-theme .sub-tabs,
body.light-theme .theme-option{background:#ffffff;border-color:#d7dde5;box-shadow:none;}
body.light-theme .sidebar{border-right-color:#d7dde5;}
body.light-theme .sidebar-logo,
body.light-theme .sidebar-owner,
body.light-theme .sidebar-footer,
body.light-theme .topbar,
body.light-theme .data-table th,
body.light-theme .data-table td,
body.light-theme .sub-tab:not(:last-child),
body.light-theme .theme-wrap{border-color:#d7dde5;}
body.light-theme .brand,
body.light-theme .owner-name,
body.light-theme .page-title,
body.light-theme .section-title,
body.light-theme .name-cell,
body.light-theme .stat-value{color:#0f172a;}
body.light-theme .brand-sub,
body.light-theme .owner-role,
body.light-theme .nav-item,
body.light-theme .stat-label,
body.light-theme .stat-sub,
body.light-theme .email-cell,
body.light-theme .setting-desc,
body.light-theme .setting-help,
body.light-theme .topbar-link{color:#475569;}
body.light-theme .nav-item:hover,
body.light-theme .nav-item.active,
body.light-theme .sub-tab:hover:not(.active){background:#f1f5f9;color:#0f172a;}
body.light-theme .nav-item.active{border-left-color:#4b601d;}
body.light-theme .sub-tab.active{background:#4b601d;color:#ffffff;}
body.light-theme .btn-logout,
body.light-theme .btn-reject,
body.light-theme .btn-delete{background:#f8fafc;color:#1f2937;border-color:#d7dde5;}
body.light-theme .btn-logout:hover,
body.light-theme .btn-reject:hover,
body.light-theme .btn-delete:hover{background:#eef2f6;}
body.light-theme .setting-field input,
body.light-theme .setting-field textarea,
body.light-theme .profile-preview{background:#ffffff;color:#0f172a;border-color:#d7dde5;}
body.light-theme .setting-field input[readonly]{background:#f1f5f9;color:#64748b;}
body.light-theme .pending-alert{background:#fff9db;border-color:#e8d68c;color:#7a5a00;}
body.light-theme .badge-pending{background:#fff7e6;color:#8a5b00;border-color:#edc177;}
body.light-theme .badge-active{background:#ecfdf3;color:#166534;border-color:#9ad8b5;}
body.light-theme .badge-rejected{background:#fff1f2;color:#9f1239;border-color:#f4b4c2;}
body.light-theme .data-table tr:hover td{background:#f8fafc;}
body.light-theme .hamburger-btn{color:#0f172a;}
body.light-theme .hamburger-btn span{background:#0f172a;}
/* NOTIFY */
.notify{position:fixed;bottom:24px;right:24px;padding:12px 20px;font-size:12px;font-weight:700;letter-spacing:0.08em;z-index:999;transition:opacity 0.3s;opacity:0;}
.notify.show{opacity:1;}
.notify.ok{background:#052e16;color:#86efac;border:1px solid #14532d;}
.notify.err{background:#2a0b0b;color:#ffb4b4;border:1px solid #4a1a1a;}
/* CONFIRM POPUP */
.confirm-popup-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:998;animation:overlayFadeIn 0.2s ease-out;}
.confirm-popup-overlay.show{display:flex;}
.confirm-popup{background:#111827;border:1px solid #1f2937;border-radius:8px;padding:28px;max-width:420px;width:90%;box-shadow:0 10px 40px rgba(0,0,0,0.4);animation:modalIn 0.28s cubic-bezier(0.2,0.9,0.2,1);}
.confirm-popup-title{font-weight:700;font-size:1rem;color:#f9fafb;margin-bottom:16px;}
.confirm-popup-msg{font-size:13px;color:#d1d5db;margin-bottom:24px;line-height:1.5;}
.confirm-popup-buttons{display:flex;gap:12px;justify-content:flex-end;}
.confirm-popup-btn{padding:10px 18px;font-size:12px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;border:1px solid #1f2937;cursor:pointer;transition:all 0.2s;border-radius:4px;}
.confirm-popup-btn.confirm{background:linear-gradient(135deg,#4b601d,#6f8a34);color:#fff;border-color:#6f8a34;}
.confirm-popup-btn.confirm:hover{filter:brightness(1.06);}
.confirm-popup-btn.cancel{background:#111827;color:#cbd5e1;border-color:#1f2937;}
.confirm-popup-btn.cancel:hover{background:#1f2937;color:#f9fafb;}
@keyframes overlayFadeIn{from{opacity:0;}to{opacity:1;}}
@keyframes modalIn{from{opacity:0;transform:scale(0.95);}to{opacity:1;transform:scale(1);}}
body.light-theme .confirm-popup{background:#ffffff;border-color:#d7dde5;}
body.light-theme .confirm-popup-title{color:#0f172a;}
body.light-theme .confirm-popup-msg{color:#475569;}
body.light-theme .confirm-popup-btn.cancel{background:#f8fafc;color:#0f172a;border-color:#d7dde5;}
body.light-theme .confirm-popup-btn.cancel:hover{background:#eef2f6;color:#1f2937;}
/* MOBILE MENU TOGGLE */
.hamburger-btn{display:none;flex-direction:column;gap:5px;cursor:pointer;background:none;border:none;padding:8px;color:#f9fafb;position:absolute;left:12px;top:14px;z-index:51;}
.hamburger-btn span{display:block;width:24px;height:2px;background:#f9fafb;transition:all 0.3s;}
.hamburger-btn.open span:nth-child(1){transform:translateY(7px) rotate(45deg);}
.hamburger-btn.open span:nth-child(2){opacity:0;}
.hamburger-btn.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg);}
@media(max-width:1100px){.stats-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:900px){.stats-grid{grid-template-columns:repeat(2,1fr)}.sub-tabs{overflow-x:auto;flex-wrap:nowrap;}}
@media(max-width:768px){.hamburger-btn{display:flex}.sidebar{position:fixed;left:0;top:0;height:100vh;z-index:49;border-right:1px solid #1e1e33;width:220px;transform:translateX(-100%);transition:transform 0.3s}.sidebar.open{transform:translateX(0)}.sidebar::after{content:'';position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:-1;display:none}.sidebar.open::after{display:block;z-index:-1;left:220px;right:0}.main{margin-left:0}.topbar{padding:14px 16px;position:relative}.topbar-link{font-size:10px}.page-title{font-size:0.95rem;margin-left:40px}.content{padding:16px}.pending-alert{padding:10px 12px;font-size:11px;gap:6px}.section-header{flex-direction:column;align-items:flex-start;gap:12px}.data-table{font-size:12px}.data-table th{padding:10px 12px;font-size:8px}.data-table td{padding:10px 12px;font-size:12px}.name-cell{font-size:12px}.email-cell{font-size:11px}.actions-cell{flex-wrap:wrap;gap:4px}.btn-approve,.btn-reject,.btn-delete{padding:5px 10px;font-size:9px}.stat-value{font-size:1.4rem}.stat-label{font-size:8px}.stat-icon{font-size:16px}.sub-tabs{width:100%;overflow-x:auto}.sub-tab{padding:8px 14px;font-size:9px}}
@media(max-width:768px){.setting-grid{grid-template-columns:1fr}.setting-wrap{padding:16px}}
@media(max-width:768px){.theme-options{flex-direction:column;align-items:stretch}}
@media(max-width:480px){.stats-grid{grid-template-columns:1fr}.topbar{padding:12px;gap:6px}.page-title{font-size:0.85rem;margin-left:38px}.topbar-link{font-size:9px}.content{padding:12px}.data-table{display:block;overflow-x:auto;border:none}.data-table thead{display:none}.data-table tbody{display:block}.data-table tr{display:block;border:1px solid #1e1e33;margin-bottom:12px;padding:12px}.data-table td{display:grid;grid-template-columns:100px 1fr;gap:8px;padding:8px 0;border:none}.data-table td:before{content:attr(data-label);font-family:'Oxanium',sans-serif;font-weight:700;color:#444;font-size:9px;text-transform:uppercase}.actions-cell{flex-direction:column;gap:6px}.btn-approve,.btn-reject,.btn-delete{width:100%;padding:8px 6px;font-size:8px}.section-title{font-size:0.95rem}.stat-value{font-size:1.2rem}.sub-tab{padding:6px 10px;font-size:8px}.pending-alert{font-size:10px;padding:8px}.name-cell{font-size:11px}}
</style>
</head>
<body>
<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="brand">SMKN 1 Surabaya</div>
    <div class="brand-sub">Owner Panel</div>
  </div>
  <div class="sidebar-owner">
    <div class="owner-avatar" id="ownerAvatarSidebar">
      <?php if (!empty($owner_foto_profile)): ?>
        <img src="<?= htmlspecialchars($owner_foto_profile) ?>" alt="Foto Profil Owner">
      <?php else: ?>
        !
      <?php endif; ?>
    </div>
    <div class="owner-name"><?= htmlspecialchars($owner_nama) ?></div>
    <div class="owner-role">Owner</div>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-item active" data-section="overview" onclick="showSection('overview',this)">
      <span class="nav-icon"></span> Overview
    </div>
    <div class="nav-item" data-section="sellers" onclick="showSection('sellers',this)">
      <span class="nav-icon"></span> Penjualan
      <?php if ($total_pending > 0): ?><span class="badge red"><?= $total_pending ?></span><?php endif; ?>
    </div>
    <div class="nav-item" data-section="users" onclick="showSection('users',this)">
      <span class="nav-icon"></span> Pengguna
    </div>
    <div class="nav-item" data-section="stands" onclick="showSection('stands',this)">
      <span class="nav-icon"></span> Stands
    </div>
    <div class="nav-item" data-section="setting" onclick="showSection('setting',this)">
      <span class="nav-icon"></span> Pengaturan
    </div>
  </nav>
  <div class="sidebar-footer">
    <a href="api/owner_logout.php" class="btn-logout">Logout →</a>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <div class="topbar">
    <button class="hamburger-btn" id="sidebarToggle" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
    <div class="page-title" id="topbarTitle">Ringkasan</div>
    <a href="page2.php" target="_blank" class="topbar-link">Lihat Website →</a>
  </div>
  <div class="content">

    <!-- ══ OVERVIEW ══ -->
    <div class="section active" id="section-overview">
      <?php if ($total_pending > 0): ?>
      <div class="pending-alert">
        ⚠️ Ada <strong><?= $total_pending ?> seller</strong> menunggu persetujuan!
        <span style="cursor:pointer;text-decoration:underline;margin-left:8px;" onclick="showSection('sellers',document.querySelector('[data-section=sellers]'));showSubTab('pending')">Review sekarang →</span>
      </div>
      <?php endif; ?>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon"></div>
          <div class="stat-label">Total Users</div>
          <div class="stat-value"><?= $total_users ?></div>
          <div class="stat-sub">terdaftar</div>
        </div>
        <div class="stat-card highlight">
          <div class="stat-icon"></div>
          <div class="stat-label">Sellers Active</div>
          <div class="stat-value"><?= count($sellers_active) ?></div>
          <div class="stat-sub"><?= $total_pending ?> pending</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"></div>
          <div class="stat-label">Total Stands</div>
          <div class="stat-value"><?= $total_stands ?></div>
          <div class="stat-sub">semua seller</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"></div>
          <div class="stat-label">Total Reviews</div>
          <div class="stat-value"><?= $total_reviews ?></div>
          <div class="stat-sub">dari users</div>
        </div>
        <div class="stat-card">
          <div class="stat-icon"></div>
          <div class="stat-label">Pending</div>
          <div class="stat-value" style="color:<?= $total_pending>0?'#ffcc44':'#fff' ?>"><?= $total_pending ?></div>
          <div class="stat-sub">seller request</div>
        </div>
      </div>

      <!-- Pending sellers di overview -->
      <?php if (!empty($sellers_pending)): ?>
      <div class="section-header"><div class="section-title">⏳ Seller Menunggu Approve</div></div>
      <table class="data-table">
        <thead><tr><th>Nama</th><th>Email</th><th>Daftar</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach ($sellers_pending as $s): ?>
          <tr id="seller-row-<?= $s['id'] ?>">
            <td><div class="name-cell"><?= htmlspecialchars($s['nama']) ?></div></td>
            <td class="email-cell"><?= htmlspecialchars($s['email']) ?></td>
            <td style="color:#444;font-size:12px;"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
            <td><div class="actions-cell">
              <button class="btn-approve" onclick="updateSeller(<?= $s['id'] ?>,'active')">✓ Approve</button>
              <button class="btn-reject"  onclick="updateSeller(<?= $s['id'] ?>,'rejected')">✗ Reject</button>
            </div></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <!-- ══ SELLERS ══ -->
    <div class="section" id="section-sellers">
      <div class="section-header"><div class="section-title">Manajemen Sellers</div></div>
      <div class="sub-tabs">
        <button class="sub-tab active" onclick="showSubTab('pending',this)">⏳ Pending <span style="color:#ffaa00">(<?= count($sellers_pending) ?>)</span></button>
        <button class="sub-tab" onclick="showSubTab('active',this)">✓ Active (<?= count($sellers_active) ?>)</button>
        <button class="sub-tab" onclick="showSubTab('rejected',this)">✗ Rejected (<?= count($sellers_rejected) ?>)</button>
      </div>

      <!-- PENDING -->
      <div class="sub-section active" id="sub-pending">
        <table class="data-table">
          <thead><tr><th>Nama</th><th>Email</th><th>Daftar</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php if (empty($sellers_pending)): ?>
            <tr class="empty-row"><td colspan="4">TIDAK ADA SELLER PENDING</td></tr>
            <?php else: foreach ($sellers_pending as $s): ?>
            <tr id="seller-row-<?= $s['id'] ?>">
              <td><div class="name-cell"><?= htmlspecialchars($s['nama']) ?></div></td>
              <td class="email-cell"><?= htmlspecialchars($s['email']) ?></td>
              <td style="color:#444;font-size:12px;"><?= date('d M Y', strtotime($s['created_at'])) ?></td>
              <td><div class="actions-cell">
                <button class="btn-approve" onclick="updateSeller(<?= $s['id'] ?>,'active')">✓ Approve</button>
                <button class="btn-reject"  onclick="updateSeller(<?= $s['id'] ?>,'rejected')">✗ Reject</button>
              </div></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- ACTIVE -->
      <div class="sub-section" id="sub-active">
        <table class="data-table">
          <thead><tr><th>Nama</th><th>Email</th><th>Stands</th><th>Status</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php if (empty($sellers_active)): ?>
            <tr class="empty-row"><td colspan="5">BELUM ADA SELLER ACTIVE</td></tr>
            <?php else: foreach ($sellers_active as $s): ?>
            <tr id="seller-row-<?= $s['id'] ?>">
              <td><div class="name-cell"><?= htmlspecialchars($s['nama']) ?></div></td>
              <td class="email-cell"><?= htmlspecialchars($s['email']) ?></td>
              <td style="color:#aaa;font-family:'Oxanium',sans-serif;font-size:12px;"><?= $s['total_stands'] ?> stand</td>
              <td><span class="badge-status badge-active">Active</span></td>
              <td><div class="actions-cell">
                <button class="btn-reject"  onclick="updateSeller(<?= $s['id'] ?>,'rejected')">Suspend</button>
                <button class="btn-delete"  onclick="deleteSeller(<?= $s['id'] ?>)">Hapus</button>
              </div></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- REJECTED -->
      <div class="sub-section" id="sub-rejected">
        <table class="data-table">
          <thead><tr><th>Nama</th><th>Email</th><th>Status</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php if (empty($sellers_rejected)): ?>
            <tr class="empty-row"><td colspan="4">TIDAK ADA SELLER REJECTED</td></tr>
            <?php else: foreach ($sellers_rejected as $s): ?>
            <tr id="seller-row-<?= $s['id'] ?>">
              <td><div class="name-cell"><?= htmlspecialchars($s['nama']) ?></div></td>
              <td class="email-cell"><?= htmlspecialchars($s['email']) ?></td>
              <td><span class="badge-status badge-rejected">Rejected</span></td>
              <td><div class="actions-cell">
                <button class="btn-approve" onclick="updateSeller(<?= $s['id'] ?>,'active')">Re-Approve</button>
                <button class="btn-delete"  onclick="deleteSeller(<?= $s['id'] ?>)">Hapus</button>
              </div></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ══ USERS ══ -->
    <div class="section" id="section-users">
      <div class="section-header">
        <div class="section-title">Semua Users</div>
        <span style="font-family:'Oxanium',sans-serif;font-size:11px;color:#333;"><?= $total_users ?> terdaftar</span>
      </div>
      <table class="data-table">
        <thead><tr><th>Nama</th><th>Email</th><th>Reviews</th><th>Ratings</th><th>Bergabung</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php if (empty($users)): ?>
          <tr class="empty-row"><td colspan="6">BELUM ADA USER</td></tr>
          <?php else: foreach ($users as $u): ?>
          <tr id="user-row-<?= $u['id'] ?>">
            <td><div class="name-cell"><?= htmlspecialchars($u['nama']) ?></div></td>
            <td class="email-cell"><?= htmlspecialchars($u['email']) ?></td>
            <td style="font-family:'Oxanium',sans-serif;font-size:12px;color:#aaa;"><?= $u['total_reviews'] ?></td>
            <td style="font-family:'Oxanium',sans-serif;font-size:12px;color:#aaa;"><?= $u['total_ratings'] ?></td>
            <td style="font-size:12px;color:#444;"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td><button class="btn-delete" onclick="deleteUser(<?= $u['id'] ?>)">Hapus</button></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ══ STANDS ══ -->
    <div class="section" id="section-stands">
      <div class="section-header">
        <div class="section-title">Semua Stands</div>
        <span style="font-family:'Oxanium',sans-serif;font-size:11px;color:#333;"><?= $total_stands ?> stand</span>
      </div>
      <table class="data-table">
        <thead><tr><th>Nama Stand</th><th>Seller</th><th>Kategori</th><th>Menu</th><th>Rating</th><th>Votes</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php if (empty($stands)): ?>
          <tr class="empty-row"><td colspan="7">BELUM ADA STAND</td></tr>
          <?php else: foreach ($stands as $s): ?>
          <tr id="stand-row-<?= $s['id'] ?>">
            <td><div class="name-cell"><?= htmlspecialchars($s['nama']) ?></div></td>
            <td style="font-size:12px;color:#aaa;"><?= $s['seller_nama'] ? htmlspecialchars($s['seller_nama']) : '<span style="color:#333">—</span>' ?></td>
            <td><span class="badge-status" style="background:#1a1a2a;color:#aaaaff;border:1px solid #222244;"><?= ucfirst($s['kategori']) ?></span></td>
            <td style="font-family:'Oxanium',sans-serif;font-size:12px;color:#aaa;"><?= $s['total_menu'] ?></td>
            <td style="font-family:'Oxanium',sans-serif;font-size:12px;color:#aaa;"><?= $s['rating'] ?></td>
            <td style="font-family:'Oxanium',sans-serif;font-size:12px;color:#444;"><?= $s['total_votes'] ?></td>
            <td><button class="btn-delete" onclick="deleteStand(<?= $s['id'] ?>)">Hapus</button></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ══ SETTING ══ -->
    <div class="section" id="section-setting">
      <div class="section-header"><div class="section-title">Setting Biodata Owner</div></div>
      <div class="setting-wrap">
        <p class="setting-desc">
          Lengkapi Biodata ini.
        </p>
        <form class="setting-form" id="ownerSettingForm" enctype="multipart/form-data">
          <div class="profile-upload">
            <img
              src="<?= !empty($owner_foto_profile) ? htmlspecialchars($owner_foto_profile) : $defaultProfilePreview ?>"
              alt="Preview Foto Profil"
              class="profile-preview"
              id="ownerProfilePreview"
            >
            <div class="setting-field" style="max-width:320px;">
              <label for="ownerFotoProfile">Foto Profil</label>
              <input type="file" id="ownerFotoProfile" name="foto_profile" accept="image/png,image/jpeg,image/webp">
              <span class="setting-help">Upload JPG, PNG, atau WEBP. Maksimum 2MB.</span>
            </div>
          </div>
          <div class="setting-grid">
            <div class="setting-field">
              <label for="ownerNama">Username</label>
              <input type="text" id="ownerNama" name="nama" required maxlength="80" value="<?= htmlspecialchars($owner_nama) ?>">
            </div>
            <div class="setting-field">
              <label for="ownerEmail">Gmail</label>
              <input type="email" id="ownerEmail" name="email" readonly value="<?= htmlspecialchars($owner_email) ?>">
              <span class="setting-help">Email akun login tidak dapat diganti.</span>
            </div>
          </div>
          <div class="setting-grid">
            <div class="setting-field">
              <label for="ownerTanggalLahir">Tanggal Lahir (Opsional)</label>
              <input type="date" id="ownerTanggalLahir" name="tanggal_lahir" value="<?= htmlspecialchars($owner_tanggal_lahir) ?>">
            </div>
          </div>
          <div class="setting-field">
            <label for="ownerDeskripsi">Deskripsi Diri</label>
            <textarea id="ownerDeskripsi" name="deskripsi" maxlength="1000" placeholder="Ceritakan singkat tentang dirimu..."><?= htmlspecialchars($owner_deskripsi) ?></textarea>
          </div>
          <button type="submit" class="btn-save">Simpan Biodata</button>
        </form>

        <div class="theme-wrap">
          <div class="theme-title">Mode Tampilan</div>
          <div class="theme-options" id="themeOptions">
            <label class="theme-option" for="themeDark">
              <input type="radio" id="themeDark" name="theme_mode" value="dark" checked>
              Mode Gelap
            </label>
            <label class="theme-option" for="themeLight">
              <input type="radio" id="themeLight" name="theme_mode" value="light">
              Mode Terang
            </label>
          </div>
          <div class="setting-help" style="margin-top:8px;">Pilih Mode Terang untuk mengubah tema panel menjadi terang.</div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- NOTIFY -->
<div class="notify" id="notify"></div>

<!-- CONFIRM POPUP -->
<div class="confirm-popup-overlay" id="confirmPopupOverlay">
  <div class="confirm-popup">
    <div class="confirm-popup-title">Konfirmasi</div>
    <div class="confirm-popup-msg" id="confirmPopupMsg"></div>
    <div class="confirm-popup-buttons">
      <button class="confirm-popup-btn cancel" onclick="closeConfirmPopup()">Batal</button>
      <button class="confirm-popup-btn confirm" onclick="confirmPopupAction()">Lanjutkan</button>
    </div>
  </div>
</div>

<script>
// ── SECTION NAV ──
const sectionTitles = {overview:'Overview', sellers:'Manajemen Sellers', users:'Semua Users', stands:'Semua Stands', setting:'Setting Biodata'};
const THEME_KEY = 'owner_panel_theme_<?= $owner_id ?>';

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
function showSection(name, el) {
  document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('section-' + name).classList.add('active');
  if (el) el.classList.add('active');
  else document.querySelector(`[data-section="${name}"]`).classList.add('active');
  document.getElementById('topbarTitle').textContent = sectionTitles[name] || name;
}

function openSectionFromHash() {
  const hash = (window.location.hash || '').replace('#', '').toLowerCase();
  if (hash === 'setting' || hash === 'section-setting') {
    showSection('setting');
  }
}

openSectionFromHash();
window.addEventListener('hashchange', openSectionFromHash);

// ── SUB TABS ──
function showSubTab(name, el) {
  document.querySelectorAll('.sub-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.sub-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('sub-' + name).classList.add('active');
  if (el) el.classList.add('active');
  else document.querySelectorAll('.sub-tab').forEach(t => { if (t.textContent.includes(name)) t.classList.add('active'); });
}

// ── NOTIFY ──
function showNotify(msg, type) {
  const el = document.getElementById('notify');
  el.textContent = msg; el.className = 'notify ' + type + ' show';
  setTimeout(() => el.classList.remove('show'), 3000);
}

// ── CONFIRM POPUP ──
let confirmPopupCallback = null;
function showConfirmPopup(msg, onConfirm) {
  confirmPopupCallback = onConfirm;
  document.getElementById('confirmPopupMsg').textContent = msg;
  document.getElementById('confirmPopupOverlay').classList.add('show');
}
function confirmPopupAction() {
  if (confirmPopupCallback) confirmPopupCallback();
  closeConfirmPopup();
}
function closeConfirmPopup() {
  document.getElementById('confirmPopupOverlay').classList.remove('show');
  confirmPopupCallback = null;
}
// Close popup on overlay click or Escape key
document.getElementById('confirmPopupOverlay').addEventListener('click', (e) => {
  if (e.target.id === 'confirmPopupOverlay') closeConfirmPopup();
});
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && document.getElementById('confirmPopupOverlay').classList.contains('show')) {
    closeConfirmPopup();
  }
});

// ── HAMBURGER MENU ──
const hambtn = document.getElementById('sidebarToggle');
const sidebar = document.querySelector('.sidebar');
if (hambtn) {
  hambtn.addEventListener('click', () => {
    hambtn.classList.toggle('open');
    sidebar.classList.toggle('open');
  });
  // Close sidebar when clicking nav items
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
      if (window.innerWidth <= 768) {
        hambtn.classList.remove('open');
        sidebar.classList.remove('open');
      }
    });
  });
}

document.addEventListener('click', (event) => {
	if (!sidebar || !hambtn) return;
	if (window.innerWidth > 768) return;
	if (!sidebar.classList.contains('open')) return;
	if (sidebar.contains(event.target) || hambtn.contains(event.target)) return;
	hambtn.classList.remove('open');
	sidebar.classList.remove('open');
});

// ── API CALL ──
function apiPost(url, data, onSuccess) {
  const fd = new FormData();
  Object.entries(data).forEach(([k,v]) => fd.append(k, v));
  fetch(url, { method:'POST', body:fd })
    .then(r => r.json())
    .then(d => { if (d.success) onSuccess(d); else showNotify(d.error || 'Gagal!', 'err'); })
    .catch(() => showNotify('Koneksi gagal.', 'err'));
}

// ── SELLER ACTIONS ──
function updateSeller(id, status) {
  const label = status === 'active' ? 'approve' : (status === 'rejected' ? 'reject/suspend' : '');
  showConfirmPopup(`${label} seller ini?`, () => {
    apiPost('api/owner_action.php', { action:'update_seller', id, status }, d => {
      showNotify(d.message, 'ok');
      document.querySelectorAll(`#seller-row-${id}`).forEach(r => r.remove());
    });
  });
}
function deleteSeller(id) {
  showConfirmPopup('Hapus akun seller ini? Semua stand-nya juga akan terhapus!', () => {
    apiPost('api/owner_action.php', { action:'delete_seller', id }, d => {
      showNotify(d.message, 'ok');
      document.querySelectorAll(`#seller-row-${id}`).forEach(r => r.remove());
    });
  });
}

// ── USER ACTIONS ──
function deleteUser(id) {
  showConfirmPopup('Hapus akun user ini?', () => {
    apiPost('api/owner_action.php', { action:'delete_user', id }, d => {
      showNotify(d.message, 'ok');
      document.getElementById(`user-row-${id}`).remove();
    });
  });
}

// ── STAND ACTIONS ──
function deleteStand(id) {
  showConfirmPopup('Hapus stand ini?', () => {
    apiPost('api/owner_action.php', { action:'delete_stand', id }, d => {
      showNotify(d.message, 'ok');
      document.getElementById(`stand-row-${id}`).remove();
    });
  });
}

// ── OWNER SETTINGS ──
const ownerSettingForm = document.getElementById('ownerSettingForm');
const ownerProfileInput = document.getElementById('ownerFotoProfile');
const ownerProfilePreview = document.getElementById('ownerProfilePreview');

if (ownerProfileInput && ownerProfilePreview) {
  ownerProfileInput.addEventListener('change', () => {
    const file = ownerProfileInput.files && ownerProfileInput.files[0];
    if (!file) return;
    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
    if (!allowed.includes(file.type)) {
      showNotify('Format foto harus JPG, PNG, atau WEBP.', 'err');
      ownerProfileInput.value = '';
      return;
    }
    if (file.size > 2 * 1024 * 1024) {
      showNotify('Ukuran foto maksimal 2MB.', 'err');
      ownerProfileInput.value = '';
      return;
    }
    ownerProfilePreview.src = URL.createObjectURL(file);
  });
}

if (ownerSettingForm) {
  ownerSettingForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const nama = document.getElementById('ownerNama').value.trim();
    const tanggal_lahir = document.getElementById('ownerTanggalLahir').value;
    const deskripsi = document.getElementById('ownerDeskripsi').value.trim();
    const fotoFile = ownerProfileInput && ownerProfileInput.files ? ownerProfileInput.files[0] : null;

    if (!nama) {
      showNotify('Username wajib diisi.', 'err');
      return;
    }

    const fd = new FormData();
    fd.append('action', 'update_owner_profile');
    fd.append('nama', nama);
    fd.append('tanggal_lahir', tanggal_lahir);
    fd.append('deskripsi', deskripsi);
    if (fotoFile) fd.append('foto_profile', fotoFile);

    fetch('api/owner_action.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (!d.success) {
          showNotify(d.error || 'Gagal menyimpan biodata.', 'err');
          return;
        }
        showNotify(d.message || 'Biodata berhasil disimpan.', 'ok');
        const ownerName = document.querySelector('.owner-name');
        if (ownerName) ownerName.textContent = nama;
        if (d.photo_url) {
          ownerProfilePreview.src = d.photo_url;
          const sidebarAvatar = document.getElementById('ownerAvatarSidebar');
          if (sidebarAvatar) sidebarAvatar.innerHTML = `<img src="${d.photo_url}" alt="Foto Profil Owner">`;
        }
      })
      .catch(() => showNotify('Koneksi gagal.', 'err'));
  });
}

// ── THEME SETTINGS ──
const themeOptionsWrap = document.getElementById('themeOptions');
if (themeOptionsWrap) {
  themeOptionsWrap.addEventListener('change', (e) => {
    const selected = e.target && e.target.value === 'light' ? 'light' : 'dark';
    localStorage.setItem(THEME_KEY, selected);
    applyTheme(selected);
    showNotify(selected === 'light' ? 'Mode terang aktif.' : 'Mode gelap aktif.', 'ok');
  });
}
</script>
</body>
</html>