<?php
// -- Mulai session jika belum aktif --
if (session_status() === PHP_SESSION_NONE) session_start();
// -- Load dependency utama --
require_once 'config.php';

if (isset($_SESSION['user_id']))   { header('Location: page2.php'); exit; }
if (isset($_SESSION['seller_id'])) { header('Location: dashboard.php'); exit; }
if (isset($_SESSION['owner_id']))  { header('Location: owner_panel.php'); exit; }

$error = '';
$role  = $_POST['role'] ?? 'user';
// -- Cek akses atau request masuk --

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    if (!$email || !$password) {
        $error = 'Isi semua field ya!';
    } else {
        if ($role === 'user') {
          // -- Siapkan query database --
            $stmt = $conn->prepare("SELECT id, nama, password FROM users WHERE email = ?");
            $stmt->bind_param('s', $email); $stmt->execute();
            $acc = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if ($acc && $password === $acc['password']) {
                $_SESSION['user_id']   = $acc['id'];
                $_SESSION['user_nama'] = $acc['nama'];
                header('Location: ' . ($_GET['redirect'] ?? 'page2.php')); exit;
            } else { $error = 'Email atau password salah.'; }

        } elseif ($role === 'seller') {
          // -- Siapkan query database --
            $stmt = $conn->prepare("SELECT id, nama, password, status FROM sellers WHERE email = ?");
            $stmt->bind_param('s', $email); $stmt->execute();
            $acc = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if (!$acc || !password_verify($password, $acc['password'])) {
                $error = 'Email atau password salah.';
            } elseif ($acc['status'] === 'pending') {
                $error = '⏳ Akun seller kamu masih menunggu persetujuan owner.';
            } elseif ($acc['status'] === 'rejected') {
                $error = '❌ Akun seller kamu ditolak. Hubungi owner untuk info lebih lanjut.';
            } else {
                $_SESSION['seller_id']   = $acc['id'];
                $_SESSION['seller_nama'] = $acc['nama'];
                header('Location: dashboard.php'); exit;
            }

        } elseif ($role === 'owner') {
            $stmt = $conn->prepare("SELECT id, nama, password FROM owners WHERE email = ?");
            $stmt->bind_param('s', $email); $stmt->execute();
            $acc = $stmt->get_result()->fetch_assoc(); $stmt->close();
            if ($acc && $password === $acc['password']) {
                $_SESSION['owner_id']   = $acc['id'];
                $_SESSION['owner_nama'] = $acc['nama'];
                header('Location: owner_panel.php'); exit;
            } else { $error = 'Email atau password salah.'; }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — School Cafeteria</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body, body *{font-family:'Inter',sans-serif !important;}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;background:#f5f5f5;transition:background 0.4s;color:#123524;}
body.mode-seller{background:#0d0d0d;}
body.mode-owner{background:#0a0a14;}
.back-menu{
  position:fixed;
  top:20px;
  left:20px;
  z-index:30;
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:10px 14px;
  border-radius:999px;
  border:1px solid rgba(35, 89, 52, 0.18);
  background:rgba(255,255,255,0.9);
  backdrop-filter:blur(10px);
  color:#244a31;
  font-size:11px;
  font-weight:700;
  letter-spacing:0.08em;
  text-transform:uppercase;
  text-decoration:none;
  transition:transform 0.2s, box-shadow 0.2s, background 0.2s;
}
.back-menu:hover{
  transform:translateY(-1px);
  box-shadow:0 8px 24px rgba(0,0,0,0.12);
  background:#fff;
}
.back-menu-icon{display:inline-flex;align-items:center;justify-content:center;line-height:1;}
.back-menu-text{display:inline;}
body.mode-seller .back-menu,
body.mode-owner .back-menu{
  background:rgba(17,17,17,0.82);
  border-color:rgba(255,255,255,0.08);
  color:#c8e6c9;
}
@media (max-width: 768px) {
  .back-menu{
    top:14px;
    left:14px;
    width:38px;
    height:38px;
    padding:0;
    justify-content:center;
    gap:0;
    font-size:0;
  }
  .back-menu-icon{
    font-size:18px;
    line-height:1;
  }
  .back-menu-text{display:none;}
}
.auth-box{width:100%;max-width:420px;padding:40px;border:1px solid #e0e0e0;background:#fff;transition:all 0.4s;}
.auth-box.mode-seller{background:#111;border-color:#222;}
.auth-box.mode-owner{background:#0f0f1a;border-color:#1e1e33;}
.auth-logo{display:flex;align-items:center;gap:10px;margin-bottom:28px;font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:0.06em;color:#244a31;text-decoration:none;}
.auth-box.mode-seller .auth-logo,.auth-box.mode-owner .auth-logo{color:#c8e6c9;}
.logo-icon{width:36px;height:36px;border-radius:10px;background:transparent;box-shadow:0 10px 24px rgba(55,32,20,0.25);display:flex;align-items:center;justify-content:center;font-size:16px;overflow:hidden;}
.logo-icon img{width:100%;height:100%;object-fit:contain;display:block;background:transparent;}
.auth-box.mode-seller .logo-icon,.auth-box.mode-owner .logo-icon{background:transparent;}
.auth-logo .sub{color:#6b8f74;font-size:11px;display:block;}
/* Role Tabs */
.role-tabs{display:flex;margin-bottom:28px;border:1.5px solid #c8dbca;overflow:hidden;}
.auth-box.mode-seller .role-tabs{border-color:#2b3a2d;}
.auth-box.mode-owner .role-tabs{border-color:#243b2b;}
.role-tab{flex:1;padding:11px 6px;font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;cursor:pointer;border:none;background:transparent;color:#7e9b83;transition:all 0.2s;text-align:center;}
.role-tab:not(:last-child){border-right:1.5px solid #c8dbca;}
.auth-box.mode-seller .role-tab:not(:last-child){border-right-color:#2b3a2d;}
.auth-box.mode-owner .role-tab:not(:last-child){border-right-color:#243b2b;}
.role-tab.active{background:#111;color:#d7f3d2;}
.auth-box.mode-seller .role-tab.active{background:#fff;color:#244a31;}
.auth-box.mode-owner .role-tab.active{background:#243b2b;color:#d7f3d2;}
.role-tab:hover:not(.active){color:#244a31;}
.auth-box.mode-seller .role-tab:hover:not(.active){color:#c8e6c9;}
.auth-box.mode-owner .role-tab:hover:not(.active){color:#d7f3d2;}
/* Title */
.auth-title{font-weight:800;font-size:1.5rem;color:#244a31;margin-bottom:4px;}
.auth-sub{font-size:13px;color:#6f8f77;margin-bottom:24px;}
.auth-box.mode-seller .auth-title,.auth-box.mode-owner .auth-title{color:#d7f3d2;}
.auth-box.mode-seller .auth-sub,.auth-box.mode-owner .auth-sub{color:#9fbeaa;}
/* Fields */
.field{margin-bottom:16px;}
.field label{font-size:10px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#4f7a5d;display:block;margin-bottom:7px;}
.field input{width:100%;padding:12px 14px;border:1.5px solid #c7dac8;font-size:14px;color:#123524;background:#fafafa;outline:none;transition:all 0.2s;}
.field input:focus{border-color:#6f8a34;background:#fff;box-shadow:0 0 0 3px rgba(111,138,52,0.12);}
.auth-box.mode-seller .field input,.auth-box.mode-owner .field input{background:#1a1a1a;border-color:#2a3b2d;color:#d7f3d2;}
.auth-box.mode-seller .field input:focus{border-color:#86a84a;background:#222;box-shadow:0 0 0 3px rgba(134,168,74,0.16);}
.auth-box.mode-owner .field input:focus{border-color:#86a84a;background:#1a1a2a;box-shadow:0 0 0 3px rgba(134,168,74,0.16);}
/* Error */
.error-msg{font-size:13px;padding:10px 14px;margin-bottom:16px;background:#fff6f0;border:1px solid #e7cfb4;color:#7b2e1a;}
.auth-box.mode-seller .error-msg{background:#2a0000;border-color:#550000;color:#ff8888;}
.auth-box.mode-owner .error-msg{background:#0a0a2a;border-color:#243b2b;color:#d7f3d2;}
/* Submit */
.btn-submit{width:100%;padding:13px;font-size:12px;font-weight:800;letter-spacing:0.12em;text-transform:uppercase;border:none;cursor:pointer;transition:opacity 0.2s;background:#111;color:#d7f3d2;}
.auth-box.mode-seller .btn-submit{background:#fff;color:#244a31;}
.auth-box.mode-owner .btn-submit{background:#243b2b;color:#d7f3d2;}
.btn-submit:hover{opacity:0.8;}
/* Footer */
.auth-footer{text-align:center;margin-top:18px;font-size:13px;color:#6f8f77;}
.auth-footer a{color:#244a31;font-weight:700;text-decoration:none;border-bottom:1.5px solid #244a31;}
.auth-box.mode-seller .auth-footer a{color:#d7f3d2;border-bottom-color:#d7f3d2;}
.auth-box.mode-owner .auth-footer a{color:#c8e6c9;border-bottom-color:#c8e6c9;}
/* Pending info */
.pending-info{background:#f4fbf4;border:1px solid #cfe6d1;color:#4f7a5d;font-size:12px;padding:10px 14px;margin-bottom:16px;letter-spacing:0.04em;display:none;}
.auth-box.mode-seller .pending-info{background:#1a1500;border-color:#443300;color:#d7f3d2;}
</style>
</head>
<body id="loginBody" class="mode-<?= $role ?>">
<a href="page2.php" class="back-menu" aria-label="Kembali ke menu"><span class="back-menu-icon" aria-hidden="true">←</span><span class="back-menu-text">Kembali ke menu</span></a>
<div class="auth-box mode-<?= $role ?>" id="authBox">

  <a href="index.php" class="auth-logo">
    <div class="logo-icon"><img src="assets/img/logosmkn-transparent.png" alt="SMKN 1 Surabaya"></div>
    <div><span>SMKN 1 SURABAYA</span><span class="sub">Kantin</span></div>
  </a>

  <div class="role-tabs">
    <button type="button" class="role-tab <?= $role==='user'?'active':'' ?>" onclick="setRole('user',this)">User</button>
    <button type="button" class="role-tab <?= $role==='seller'?'active':'' ?>" onclick="setRole('seller',this)">Seller</button>
    <button type="button" class="role-tab <?= $role==='owner'?'active':'' ?>" onclick="setRole('owner',this)">Owner</button>
  </div>

  <div class="pending-info" id="pendingInfo">
    Akun seller baru perlu di-approve owner dulu sebelum bisa login.
  </div>

  <h1 class="auth-title" id="authTitle">
    <?= $role==='seller'?'Seller Login':($role==='owner'?'Owner Login':'Login') ?>
  </h1>
  <p class="auth-sub" id="authSub">
    <?= $role==='seller'?'Masuk ke dashboard penjual':($role==='owner'?'Akses panel owner':'Masuk untuk kasih rating & review!') ?>
  </p>

  <?php if ($error): ?>
    <div class="error-msg"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($role) ?>"/>
    <div class="field">
      <label>Email</label>
      <input type="email" name="email" placeholder="email@kamu.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
    </div>
    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required/>
    </div>
    <button type="submit" class="btn-submit" id="btnSubmit">Masuk →</button>
  </form>

  <p class="auth-footer" id="authFooter">
    <?php if ($role==='seller'): ?>
      Belum punya akun seller? <a href="seller_register.php">Daftar</a>
    <?php elseif ($role==='user'): ?>
      Belum punya akun? <a href="register.php">Daftar sekarang</a>
    <?php else: ?>
      &nbsp;
    <?php endif; ?>
  </p>
</div>

<script>
  // — Fungsi manajemen form login dan validasi role — 
const titles  = {user:'Login', seller:'Seller Login', owner:'Owner Login'};
const subs    = {user:'Masuk untuk kasih rating & review!', seller:'Masuk ke dashboard penjual', owner:'Akses panel owner'};
const footers = {
  user:'Belum punya akun? <a href="register.php">Daftar sekarang</a>',
  seller:'Belum punya akun seller? <a href="seller_register.php">Daftar</a>',
  owner:'&nbsp;'
};

function setRole(role, el) {
  document.getElementById('roleInput').value = role;
  document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  const box = document.getElementById('authBox');
  // — Akhir script interaksi form login multi-role — 
  const body = document.getElementById('loginBody');
  ['mode-user','mode-seller','mode-owner'].forEach(c => { box.classList.remove(c); body.classList.remove(c); });
  box.classList.add('mode-'+role);
  body.classList.add('mode-'+role);
  document.getElementById('authTitle').textContent  = titles[role];
  document.getElementById('authSub').textContent    = subs[role];
  document.getElementById('authFooter').innerHTML   = footers[role];
  document.getElementById('pendingInfo').style.display = role==='seller' ? 'block' : 'none';
}

// Init
document.getElementById('pendingInfo').style.display =
  '<?= $role ?>' === 'seller' ? 'block' : 'none';
</script>
</body>
</html>