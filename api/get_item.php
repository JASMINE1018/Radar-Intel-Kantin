<?php
header('Content-Type: application/json');
require_once '../config.php';

// Handle GET requests for fetching item details (public)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $menu_id = (int)($_GET['menu_id'] ?? 0);
    $user_id = (int)($_GET['user_id'] ?? 0);
    
    if ($menu_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'menu_id tidak valid']);
        exit;
    }
    
    // Fetch item details
    $stmt = $conn->prepare(
        "SELECT m.id, m.nama, m.harga, m.foto, m.rating, m.total_votes, s.kategori 
         FROM menu_items m 
         LEFT JOIN stands s ON s.id = m.stand_id 
         WHERE m.id = ?"
    );
    $stmt->bind_param('i', $menu_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'Item tidak ditemukan']);
        exit;
    }

    $conn->query("CREATE TABLE IF NOT EXISTS review_replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL UNIQUE,
        seller_id INT NOT NULL,
        reply TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_review_id (review_id),
        INDEX idx_seller_id (seller_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Fetch reviews
    $stmt = $conn->prepare(
        "SELECT rv.id, rv.rating, rv.komentar, rv.created_at, 
                rr.reply, rr.created_at AS reply_created_at,
                COALESCE(u.nama, o.nama) as nama,
                CASE WHEN u.id IS NOT NULL THEN 'user' ELSE 'owner' END as tipe
         FROM reviews rv
         LEFT JOIN users u ON u.id = rv.user_id
         LEFT JOIN owners o ON o.id = rv.user_id
         LEFT JOIN review_replies rr ON rr.review_id = rv.id
         WHERE rv.menu_id = ?
         ORDER BY rv.created_at DESC"
    );
    $stmt->bind_param('i', $menu_id);
    $stmt->execute();
    $reviewResult = $stmt->get_result();
    
    $reviews = [];
    while ($row = $reviewResult->fetch_assoc()) {
        $reviews[] = [
            'id' => (int)$row['id'],
            'nama' => $row['nama'],
            'rating' => (int)$row['rating'],
            'komentar' => $row['komentar'],
            'waktu' => $row['created_at'],
            'tipe' => $row['tipe'],
            'reply' => $row['reply'] ?? null,
            'reply_waktu' => $row['reply_created_at'] ?? null
        ];
    }
    $stmt->close();
    
    // Fetch user's rating if logged in
    $myRating = 0;
    $myKomentar = '';
    if ($user_id > 0) {
        $stmt = $conn->prepare(
            "SELECT rating, komentar FROM reviews WHERE menu_id = ? AND user_id = ?"
        );
        $stmt->bind_param('ii', $menu_id, $user_id);
        $stmt->execute();
        $myReview = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($myReview) {
            $myRating = (int)$myReview['rating'];
            $myKomentar = $myReview['komentar'];
        }
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'item' => $item,
        'reviews' => $reviews,
        'my_rating' => $myRating,
        'my_komentar' => $myKomentar
    ]);
    exit;
}

// Handle POST requests for seller CRUD (requires seller login)
require_once '../seller_auth.php';
requireSeller();

$seller_id  = $_SESSION['seller_id'];
$action     = $_POST['action'] ?? '';
$upload_dir = __DIR__ . '/../uploads/';

if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

function uploadFoto($file, $upload_dir) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed)) return ['error' => 'Format gambar tidak valid (JPG/PNG/WEBP)'];
    if ($file['size'] > 2 * 1024 * 1024) return ['error' => 'Ukuran gambar maks 2MB'];
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('item_', true) . '.' . strtolower($ext);
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) return ['error' => 'Gagal upload gambar'];
    return $filename;
}

function isMyStand($conn, $stand_id, $seller_id) {
    $stmt = $conn->prepare("SELECT id FROM stands WHERE id=? AND seller_id=?");
    $stmt->bind_param('ii', $stand_id, $seller_id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (bool)$r;
}

if ($action === 'add') {
    $stand_id = (int)($_POST['stand_id'] ?? 0);
    $nama     = trim($_POST['nama'] ?? '');
    $harga    = (int)($_POST['harga'] ?? 0);
    if (!$nama || !$harga || !$stand_id) { echo json_encode(['error'=>'Data tidak lengkap']); exit; }
    if (!isMyStand($conn, $stand_id, $seller_id)) { echo json_encode(['error'=>'Stand tidak valid']); exit; }

    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $result = uploadFoto($_FILES['foto'], $upload_dir);
        if (is_array($result)) { echo json_encode(['error' => $result['error']]); exit; }
        $foto = $result;
    }

    $stmt = $conn->prepare("INSERT INTO menu_items (stand_id, nama, harga, foto) VALUES (?,?,?,?)");
    $stmt->bind_param('isis', $stand_id, $nama, $harga, $foto);
    $stmt->execute(); $stmt->close();
    echo json_encode(['success'=>true, 'message'=>'Menu item berhasil ditambahkan!']);

} elseif ($action === 'edit') {
    $id       = (int)($_POST['id'] ?? 0);
    $stand_id = (int)($_POST['stand_id'] ?? 0);
    $nama     = trim($_POST['nama'] ?? '');
    $harga    = (int)($_POST['harga'] ?? 0);
    if (!$id || !$nama || !$harga) { echo json_encode(['error'=>'Data tidak lengkap']); exit; }
    if (!isMyStand($conn, $stand_id, $seller_id)) { echo json_encode(['error'=>'Stand tidak valid']); exit; }

    $r = $conn->query("SELECT foto FROM menu_items WHERE id=$id");
    $old = $r->fetch_assoc();
    $foto = $old['foto'] ?? null;

    if (!empty($_FILES['foto']['name'])) {
        $result = uploadFoto($_FILES['foto'], $upload_dir);
        if (is_array($result)) { echo json_encode(['error' => $result['error']]); exit; }
        if ($old['foto'] && file_exists($upload_dir . $old['foto'])) unlink($upload_dir . $old['foto']);
        $foto = $result;
    }

    $stmt = $conn->prepare("UPDATE menu_items SET nama=?, harga=?, foto=?, stand_id=? WHERE id=?");
    $stmt->bind_param('siisi', $nama, $harga, $foto, $stand_id, $id);
    $stmt->execute(); $stmt->close();
    echo json_encode(['success'=>true, 'message'=>'Menu item berhasil diupdate!']);

} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $conn->prepare("SELECT m.id, m.foto FROM menu_items m JOIN stands s ON s.id=m.stand_id WHERE m.id=? AND s.seller_id=?");
    $stmt->bind_param('ii', $id, $seller_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$row) { echo json_encode(['error'=>'Item tidak valid']); exit; }
    if ($row['foto'] && file_exists($upload_dir . $row['foto'])) unlink($upload_dir . $row['foto']);
    $stmt = $conn->prepare("DELETE FROM menu_items WHERE id=?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    echo json_encode(['success'=>true]);

} else {
    echo json_encode(['error'=>'Action tidak valid']);
}
$conn->close();