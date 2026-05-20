<?php
require_once 'auth.php';
require_once 'config.php';

$user = currentUser();
$isLoggedIn = isLoggedIn();

// -- Pastikan struktur tabel tersedia --
$conn->query("CREATE TABLE IF NOT EXISTS tray_items (
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT NOT NULL,
	menu_id INT NOT NULL,
	qty INT NOT NULL DEFAULT 1,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	UNIQUE KEY uniq_user_menu (user_id, menu_id),
	KEY idx_user_id (user_id),
	KEY idx_menu_id (menu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// -- Siapkan query database --

$stmt = $conn->prepare(
		"SELECT
				t.menu_id,
				t.qty,
				m.nama,
				m.harga,
				m.foto,
				s.nama AS stand_nama,
				s.kategori
		 FROM tray_items t
		 JOIN menu_items m ON m.id = t.menu_id
		 LEFT JOIN stands s ON s.id = m.stand_id
		 WHERE t.user_id = ?
		 ORDER BY t.updated_at DESC"
);

$trayItems = [];
$subtotal = 0;

if ($stmt && $isLoggedIn) {
		$uid = (int)$user['id'];
		$stmt->bind_param('i', $uid);
		$stmt->execute();
		$result = $stmt->get_result();

		while ($row = $result->fetch_assoc()) {
				$lineTotal = ((int)$row['harga']) * ((int)$row['qty']);
				$subtotal += $lineTotal;
				$row['line_total'] = $lineTotal;
				$trayItems[] = $row;
		}
} else {
		//kosong 
		foreach ($trayItems as $item) {
				$subtotal += $item['line_total'];
		}
}

if ($stmt) {
		$stmt->close();
}

$tax = (int)round($subtotal * 0.1);
$total = $subtotal + $tax;
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Tray - School Cafeteria</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body, body * { font-family: 'Inter', sans-serif !important; }
body {
	background: #ffffff;
	overflow-x: hidden;
	color: #1f2329;
}

/* ===== NAVBAR (same transition style as page2) ===== */
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
	color: #1f2329;
	text-transform: uppercase;
	letter-spacing: 0.08em;
	line-height: 1.2;
	text-decoration: none;
}
.logo-icon {
	width: 36px;
	height: 36px;
	border-radius: 10px;
	box-shadow: 0 10px 24px rgba(55, 32, 20, 0.25);
	display: flex;
	align-items: center;
	justify-content: center;
	font-size: 16px;
	background: transparent;
}
.logo-icon img {
	width: 100%;
	height: 100%;
	object-fit: contain;
	display: block;
	background: transparent;
}
.nav-logo .sub { color: #6e7884; }
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
.nav-links a:hover, .nav-links a.active { color: #1f2329; }
.nav-links a.active { border-bottom: 2px solid #4b601d; padding-bottom: 2px; }
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
.hamburger { display: none; flex-direction: column; gap: 5px; cursor: pointer; background: none; border: none; padding: 4px; }
.hamburger span { display: block; width: 24px; height: 2px; background: #1f2329; transition: all 0.3s; }
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
.mobile-menu a:hover { color: #1f2329; }
.mobile-menu .btn-join { margin-top: 16px; border-radius: 8px; color: #ffffff; }

.tray-shell {
	max-width: 1060px;
	margin: 98px auto 24px;
	border: 1px solid #ececec;
	border-radius: 20px;
	overflow: hidden;
	background: #ffffff;
}
.tray-head {
	padding: 28px;
	border-bottom: 1px solid #ededed;
}
.tray-head h1 {
	font-family: 'Space Grotesk', sans-serif;
	font-size: 40px;
	color: #2b2b2b;
}
.tray-content {
	background: #f8f8f8;
	padding: 26px;
}
.tray-card {
	background: #ffffff;
	border: 1px solid #ebebeb;
	border-radius: 6px;
	padding: 10px 12px;
}
.tray-item {
	display: grid;
	grid-template-columns: 1fr auto auto auto;
	gap: 12px;
	align-items: center;
	border-bottom: 1px solid #f0f0f0;
	padding: 12px 0;
}
.item-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
.thumb {
	width: 78px;
	height: 52px;
	border-radius: 2px;
	object-fit: cover;
	border: 1px solid #ececec;
	background: #f2f2f2;
}
.item-name {
	font-size: 28px;
	font-weight: 700;
	color: #2f2f2f;
	line-height: 1.1;
}
.item-meta { font-size: 11px; color: #909090; margin-top: 2px; }
.qty {
	display: inline-flex;
	border: 1px solid #ececec;
	border-radius: 4px;
	overflow: hidden;
}
.qty button {
	width: 22px;
	height: 30px;
	border: none;
	background: #fafafa;
	color: #8a8a8a;
	cursor: pointer;
}
.qty span {
	width: 34px;
	height: 30px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-size: 13px;
	color: #545454;
	border-left: 1px solid #ececec;
	border-right: 1px solid #ececec;
}
.price {
	min-width: 110px;
	font-family: 'Space Grotesk', sans-serif;
	font-size: 30px;
	font-weight: 700;
	color: #4a4a4a;
}
.btn-remove {
	width: 24px;
	height: 24px;
	border: 1px solid #e7e7e7;
	border-radius: 4px;
	background: #ffffff;
	color: #8e8e8e;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
}
.summary {
	margin-top: 12px;
	margin-left: auto;
	width: 250px;
}
.summary-row {
	display: flex;
	justify-content: space-between;
	font-size: 13px;
	color: #767676;
	padding: 3px 0;
}
.summary-row.total {
	color: #2e2e2e;
	font-weight: 800;
}
.checkout-wrap {
	margin-top: 10px;
	border-top: 1px solid #f0f0f0;
	padding-top: 10px;
	display: flex;
	justify-content: flex-end;
}
.btn-checkout {
	border: none;
	border-radius: 8px;
	background: linear-gradient(135deg, #4b601d, #6f8a34);
	color: #ffffff;
	font-size: 12px;
	font-weight: 700;
	letter-spacing: 0.12em;
	text-transform: uppercase;
	padding: 12px 48px;
	cursor: pointer;
	transition: all 0.2s ease;
	box-shadow: 0 4px 12px rgba(75, 96, 29, 0.2);
}
.btn-checkout:hover {
	transform: translateY(-2px);
	box-shadow: 0 6px 16px rgba(75, 96, 29, 0.3);
	filter: brightness(1.05);
}
.checkout-modal-overlay {
	position: fixed;
	inset: 0;
	z-index: 300;
	display: none;
	align-items: center;
	justify-content: center;
	padding: 20px;
	background: rgba(0, 0, 0, 0.72);
	backdrop-filter: blur(8px);
	animation: overlayFadeIn 0.2s ease both;
}
.checkout-modal-overlay.open { display: flex; }
.checkout-modal {
	width: min(100%, 490px);
	background: #ffffff;
	border-radius: 18px;
	box-shadow: 0 24px 60px rgba(0, 0, 0, 0.28);
	overflow: hidden;
	transform-origin: center;
	animation: checkoutPop 0.28s cubic-bezier(0.2, 0.9, 0.2, 1) both;
}
.checkout-modal-inner {
	padding: 30px 26px 26px;
	text-align: center;
}
.checkout-modal-title {
	font-family: 'Space Grotesk', sans-serif;
	font-size: 24px;
	line-height: 1.25;
	font-weight: 800;
	color: #2f3a2c;
	margin-bottom: 24px;
}
.checkout-modal-actions {
	display: flex;
	justify-content: center;
}
.checkout-cancel {
	min-width: 176px;
	border: none;
	border-radius: 14px;
	background: #e53935;
	color: #ffffff;
	font-family: 'Space Grotesk', sans-serif;
	font-size: 16px;
	font-weight: 700;
	padding: 16px 24px;
	cursor: pointer;
	/* box-shadow: 0 10px 22px rgba(229, 57, 53, 0.28); */
	transition: transform 0.18s ease, filter 0.18s ease, box-shadow 0.18s ease;
}
.checkout-cancel:hover {
	transform: translateY(-1px);
	filter: brightness(1.02);
	color: #e53935;
	background: #ffffff;
	box-shadow: 0 10px px #e53935;
}
.checkout-cancel:active { transform: translateY(0); }
@keyframes checkoutPop {
	0% { opacity: 0; transform: scale(0.84) translateY(14px); }
	100% { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes overlayFadeIn {
	from { opacity: 0; }
	to { opacity: 1; }
}
.tray-empty {
	padding: 24px 12px;
	text-align: center;
	color: #8c8c8c;
	font-family: 'Space Grotesk', sans-serif;
	letter-spacing: 0.08em;
	text-transform: uppercase;
	font-size: 12px;
}

@media (max-width: 980px) {
	.item-name { font-size: 22px; }
	.price { font-size: 23px; }
}
@media (max-width: 860px) {
	.nav-links, .btn-join.desktop { display: none; }
	.hamburger { display: flex; }
	.tray-shell { margin-top: 90px; }
}
@media (max-width: 760px) {
	nav { padding: 12px 14px; }
	.tray-shell {
		margin: 74px 10px 16px;
		border-radius: 12px;
	}
	.tray-head, .tray-content { padding: 14px; }
	.tray-head h1 { font-size: 30px; }
	.tray-item {
		grid-template-columns: 1fr;
		gap: 10px;
	}
	.price { min-width: 0; }
	.summary { width: 100%; }
	.btn-checkout { width: 100%; }
	.checkout-modal {
		max-width: 92vw;
	}
	.checkout-modal-inner {
		padding: 24px 18px 20px;
	}
	.checkout-modal-title {
		font-size: 18px;
		margin-bottom: 18px;
	}
	.checkout-cancel {
		min-width: 150px;
		font-size: 14px;
		padding: 14px 18px;
	}
}
</style>
</head>
<body>
<nav>
	<a href="index.php" class="nav-logo">
		<div class="logo-icon"><img src="assets/img/logosmkn-transparent.png" alt="SMKN 1 Surabaya"></div>
		<div><div>SMKN 1 SURABAYA</div><div class="sub">Kantin</div></div>
	</a>
	<ul class="nav-links">
		<li><a href="index.php">Home</a></li>
		<li><a href="page2.php">Menu</a></li>
		<li><a href="myTray.php" class="active">My Tray</a></li>
	</ul>
	<?php if ($isLoggedIn): ?>
		<div style="display:flex;align-items:center;gap:12px;">
			<span style="font-family:'Space Grotesk',sans-serif;font-size:11px;color:#888;letter-spacing:0.08em;">
				Hi, <strong style="color:#111"><?= htmlspecialchars($user['nama']) ?></strong>
			</span>
			<a href="api/logout.php" class="btn-join desktop" style="background:#555;">Logout</a>
		</div>
	<?php else: ?>
		<a href="login.php" class="btn-join desktop">Login</a>
	<?php endif; ?>
	<button class="hamburger" id="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
</nav>

<div class="mobile-menu" id="mobileMenu">
	<a href="index.php">Home</a>
	<a href="page2.php">Menu</a>
	<a href="myTray.php">My Tray</a>
	<?php if ($isLoggedIn): ?>
		<a href="api/logout.php" class="btn-join">Logout</a>
	<?php else: ?>
		<a href="login.php" class="btn-join">Login</a>
	<?php endif; ?>
</div>

<main class="tray-shell">
	<section class="tray-head">
		<h1>My Tray</h1>
	</section>

	<section class="tray-content">
		<div class="tray-card" id="trayCard">
			<?php if (empty($trayItems)): ?>
				<div class="tray-empty" id="trayEmpty">Tray kamu masih kosong</div>
			<?php else: ?>
				<?php foreach ($trayItems as $item): ?>
					<div class="tray-item" data-menu-id="<?= (int)$item['menu_id'] ?>" data-unit-price="<?= (int)$item['harga'] ?>">
						<div class="item-left">
							<?php if (!empty($item['foto'])): ?>
								<img class="thumb" src="uploads/<?= htmlspecialchars($item['foto']) ?>" alt="<?= htmlspecialchars($item['nama']) ?>">
							<?php else: ?>
								<img class="thumb" src="image1.jpeg" alt="<?= htmlspecialchars($item['nama']) ?>">
							<?php endif; ?>
							<div>
								<div class="item-name"><?= htmlspecialchars($item['nama']) ?></div>
								<div class="item-meta">Stand <?= htmlspecialchars($item['kategori']) ?> - <?= htmlspecialchars($item['stand_nama']) ?></div>
							</div>
						</div>

						<div class="qty">
							<button type="button" class="btn-minus">-</button>
							<span class="qty-val"><?= (int)$item['qty'] ?></span>
							<button type="button" class="btn-plus">+</button>
						</div>

						<div class="price">Rp<?= number_format((int)$item['line_total'], 0, ',', '.') ?></div>

						<button type="button" class="btn-remove" aria-label="Hapus item">
							<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M6 6l1 14h10l1-14"/></svg>
						</button>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>

			<div class="summary" id="summaryBox" style="display:<?= empty($trayItems) ? 'none' : 'block' ?>;">
				<div class="summary-row"><span>Subtotal</span><strong id="subtotal">Rp<?= number_format($subtotal, 0, ',', '.') ?></strong></div>
				<div class="summary-row"><span>Tax (10%)</span><strong id="tax">Rp<?= number_format($tax, 0, ',', '.') ?></strong></div>
				<div class="summary-row total"><span>Total</span><strong id="grandTotal">Rp<?= number_format($total, 0, ',', '.') ?></strong></div>
			</div>

			<div class="checkout-wrap" id="checkoutWrap" style="display:<?= empty($trayItems) ? 'none' : 'flex' ?>;">
				<button type="button" class="btn-checkout">Beli</button>
			</div>
		</div>
	</section>
</main>

<div class="checkout-modal-overlay" id="checkoutModalOverlay" aria-hidden="true">
	<div class="checkout-modal" role="dialog" aria-modal="true" aria-labelledby="checkoutModalTitle">
		<div class="checkout-modal-inner">
			<div class="checkout-modal-title" id="checkoutModalTitle">Beli langsung ke orangnya yah :) #denganNadaLembut</div>
			<div class="checkout-modal-actions">
				<button type="button" class="checkout-cancel" id="checkoutCancelBtn">Batalkan</button>
			</div>
		</div>
	</div>
</div>

<script>
const hamburger = document.getElementById('hamburger');
const mobileMenu = document.getElementById('mobileMenu');
hamburger.addEventListener('click', () => {
	hamburger.classList.toggle('open');
	mobileMenu.classList.toggle('open');
});
mobileMenu.querySelectorAll('a').forEach((a) => {
	a.addEventListener('click', () => {
		hamburger.classList.remove('open');
		mobileMenu.classList.remove('open');
	});
});

const rupiah = (v) => 'Rp' + Number(v).toLocaleString('id-ID');
const summaryBox = document.getElementById('summaryBox');
const checkoutWrap = document.getElementById('checkoutWrap');
const IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
const checkoutModalOverlay = document.getElementById('checkoutModalOverlay');
const checkoutCancelBtn = document.getElementById('checkoutCancelBtn');

function openCheckoutModal() {
	checkoutModalOverlay.classList.add('open');
	checkoutModalOverlay.setAttribute('aria-hidden', 'false');
	document.body.style.overflow = 'hidden';
}

function closeCheckoutModal() {
	checkoutModalOverlay.classList.remove('open');
	checkoutModalOverlay.setAttribute('aria-hidden', 'true');
	document.body.style.overflow = '';
}

checkoutCancelBtn.addEventListener('click', closeCheckoutModal);
checkoutModalOverlay.addEventListener('click', (e) => {
	if (e.target === checkoutModalOverlay) closeCheckoutModal();
});
document.addEventListener('keydown', (e) => {
	if (e.key === 'Escape') closeCheckoutModal();
});

function setSummary(summary) {
	document.getElementById('subtotal').textContent = rupiah(summary.subtotal);
	document.getElementById('tax').textContent = rupiah(summary.tax);
	document.getElementById('grandTotal').textContent = rupiah(summary.total);
}

function syncRowLineTotal(row, data) {
	row.querySelector('.qty-val').textContent = data.qty;
	row.querySelector('.price').textContent = rupiah(data.line_total);
}

function updateEmptyState() {
	const hasItems = document.querySelectorAll('.tray-item').length > 0;
	const emptyEl = document.getElementById('trayEmpty');

	if (!hasItems) {
		if (!emptyEl) {
			const emptyNode = document.createElement('div');
			emptyNode.id = 'trayEmpty';
			emptyNode.className = 'tray-empty';
			emptyNode.textContent = 'Tray kamu masih kosong';
			document.getElementById('trayCard').insertBefore(emptyNode, summaryBox);
		}
		summaryBox.style.display = 'none';
		checkoutWrap.style.display = 'none';
	} else {
		if (emptyEl) emptyEl.remove();
		summaryBox.style.display = 'block';
		checkoutWrap.style.display = 'flex';
	}
}

async function trayAction(action, payload) {
	const fd = new FormData();
	fd.append('action', action);
	Object.keys(payload).forEach((key) => fd.append(key, payload[key]));

	const res = await fetch('api/tray.php', { method: 'POST', body: fd });
	const data = await res.json();

	if (!res.ok || data.error) {
		throw new Error(data.error || 'Gagal update tray');
	}

	return data;
}

document.querySelectorAll('.tray-item').forEach((row) => {
	const menuId = row.dataset.menuId;

	row.querySelector('.btn-plus').addEventListener('click', async () => {
		const qtyEl = row.querySelector('.qty-val');
		const nextQty = Number(qtyEl.textContent) + 1;
		
		if (!IS_LOGGED_IN) {
			// Demo mode: update UI only
			const unitPrice = Number(row.dataset.unitPrice);
			qtyEl.textContent = nextQty;
			row.querySelector('.price').textContent = rupiah(unitPrice * nextQty);
			recalculateLocalSummary();
			return;
		}
		
		try {
			const data = await trayAction('set_qty', { menu_id: menuId, qty: nextQty });
			const item = data.items.find((it) => String(it.menu_id) === String(menuId));
			if (item) syncRowLineTotal(row, item);
			setSummary(data.summary);
		} catch (err) {
			alert(err.message);
		}
	});

	row.querySelector('.btn-minus').addEventListener('click', async () => {
		const qtyEl = row.querySelector('.qty-val');
		const nextQty = Math.max(1, Number(qtyEl.textContent) - 1);
		
		if (!IS_LOGGED_IN) {
			// Demo mode: update UI only
			const unitPrice = Number(row.dataset.unitPrice);
			qtyEl.textContent = nextQty;
			row.querySelector('.price').textContent = rupiah(unitPrice * nextQty);
			recalculateLocalSummary();
			return;
		}
		
		try {
			const data = await trayAction('set_qty', { menu_id: menuId, qty: nextQty });
			const item = data.items.find((it) => String(it.menu_id) === String(menuId));
			if (item) syncRowLineTotal(row, item);
			setSummary(data.summary);
		} catch (err) {
			alert(err.message);
		}
	});

	row.querySelector('.btn-remove').addEventListener('click', async () => {
		if (!IS_LOGGED_IN) {
			// Demo mode: remove from UI only
			row.remove();
			recalculateLocalSummary();
			return;
		}
		
		try {
			const data = await trayAction('remove', { menu_id: menuId });
			row.remove();
			setSummary(data.summary);
			updateEmptyState();
		} catch (err) {
			alert(err.message);
		}
	});
});

function recalculateLocalSummary() {
	let subtotal = 0;
	document.querySelectorAll('.tray-item').forEach((row) => {
		const unitPrice = Number(row.dataset.unitPrice);
		const qty = Number(row.querySelector('.qty-val').textContent);
		subtotal += unitPrice * qty;
	});

	const tax = Math.round(subtotal * 0.1);
	const total = subtotal + tax;

	document.getElementById('subtotal').textContent = rupiah(subtotal);
	document.getElementById('tax').textContent = rupiah(tax);
	document.getElementById('grandTotal').textContent = rupiah(total);

	const hasItems = document.querySelectorAll('.tray-item').length > 0;
	summaryBox.style.display = hasItems ? 'block' : 'none';
	checkoutWrap.style.display = hasItems ? 'flex' : 'none';
}

// Checkout button
document.querySelector('.btn-checkout').addEventListener('click', () => {
	openCheckoutModal();
});
</script>
</body>
</html>
