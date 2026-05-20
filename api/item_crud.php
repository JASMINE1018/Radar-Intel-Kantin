<?php
header('Content-Type: application/json');
require_once '../seller_auth.php';
require_once '../config.php';
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