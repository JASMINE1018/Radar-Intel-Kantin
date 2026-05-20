<?php
header('Content-Type: application/json');
require_once '../auth.php';
require_once '../config.php';

// Check if user or owner is logged in
$isUserLoggedIn = isset($_SESSION['user_id']);
$isOwnerLoggedIn = isset($_SESSION['owner_id']);

if (!$isUserLoggedIn && !$isOwnerLoggedIn) {
    http_response_code(401);
    echo json_encode(['error' => 'Login dulu ya!']);
    exit;
}

$user_id = $isUserLoggedIn ? (int)$_SESSION['user_id'] : (int)$_SESSION['owner_id'];
$action = $_POST['action'] ?? ($_GET['action'] ?? 'list');

function ensureTrayTable(mysqli $conn): void {
    $sql = "
        CREATE TABLE IF NOT EXISTS tray_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            menu_id INT NOT NULL,
            qty INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_user_menu (user_id, menu_id),
            KEY idx_user_id (user_id),
            KEY idx_menu_id (menu_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    $conn->query($sql);
}

function fetchTrayItems(mysqli $conn, int $user_id): array {
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
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    $subtotal = 0;
    while ($row = $result->fetch_assoc()) {
        $harga = (int)$row['harga'];
        $qty = (int)$row['qty'];
        $line_total = $harga * $qty;
        $subtotal += $line_total;
        $items[] = [
            'menu_id' => (int)$row['menu_id'],
            'qty' => $qty,
            'nama' => $row['nama'],
            'harga' => $harga,
            'foto' => $row['foto'],
            'stand_nama' => $row['stand_nama'] ?? '',
            'kategori' => $row['kategori'] ?? '',
            'line_total' => $line_total,
        ];
    }
    $stmt->close();

    $tax = (int)round($subtotal * 0.1);
    $total = $subtotal + $tax;

    return [
        'items' => $items,
        'summary' => [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
        ],
    ];
}

ensureTrayTable($conn);

if ($action === 'list') {
    echo json_encode(['success' => true] + fetchTrayItems($conn, $user_id));
    $conn->close();
    exit;
}

if ($action === 'add') {
    $menu_id = (int)($_POST['menu_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    if ($menu_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'menu_id tidak valid']);
        $conn->close();
        exit;
    }

    $check = $conn->prepare('SELECT id FROM menu_items WHERE id = ?');
    $check->bind_param('i', $menu_id);
    $check->execute();
    $exists = $check->get_result()->fetch_assoc();
    $check->close();

    if (!$exists) {
        http_response_code(404);
        echo json_encode(['error' => 'Menu item tidak ditemukan']);
        $conn->close();
        exit;
    }

    $stmt = $conn->prepare(
        'INSERT INTO tray_items (user_id, menu_id, qty) VALUES (?, ?, ?) '
        . 'ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)'
    );
    $stmt->bind_param('iii', $user_id, $menu_id, $qty);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Item ditambahkan ke tray'] + fetchTrayItems($conn, $user_id));
    $conn->close();
    exit;
}

if ($action === 'set_qty') {
    $menu_id = (int)($_POST['menu_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    $stmt = $conn->prepare('UPDATE tray_items SET qty = ? WHERE user_id = ? AND menu_id = ?');
    $stmt->bind_param('iii', $qty, $user_id, $menu_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true] + fetchTrayItems($conn, $user_id));
    $conn->close();
    exit;
}

if ($action === 'remove') {
    $menu_id = (int)($_POST['menu_id'] ?? 0);

    $stmt = $conn->prepare('DELETE FROM tray_items WHERE user_id = ? AND menu_id = ?');
    $stmt->bind_param('ii', $user_id, $menu_id);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true] + fetchTrayItems($conn, $user_id));
    $conn->close();
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Action tidak valid']);
$conn->close();
