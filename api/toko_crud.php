<?php
header('Content-Type: application/json');
require_once '../seller_auth.php';
require_once '../config.php';
requireSeller();

$seller_id  = $_SESSION['seller_id'];
$action     = $_POST['action'] ?? '';
$upload_dir = __DIR__ . '/../uploads/';

// Buat folder uploads kalau belum ada
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// ── Helper: upload foto ──
function uploadFoto($file, $upload_dir) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed)) return ['error' => 'Format gambar tidak valid (JPG/PNG/WEBP)'];
    if ($file['size'] > 2 * 1024 * 1024) return ['error' => 'Ukuran gambar maks 2MB'];
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('stand_', true) . '.' . strtolower($ext);
    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $filename)) return ['error' => 'Gagal upload gambar'];
    return $filename;
}

if ($action === 'add') {
    $nama     = trim($_POST['nama'] ?? '');
    $kategori = $_POST['kategori'] ?? 'makanan';
    if (!$nama) { echo json_encode(['error'=>'Nama wajib diisi']); exit; }

    $foto = null;
    if (!empty($_FILES['foto']['name'])) {
        $result = uploadFoto($_FILES['foto'], $upload_dir);
        if (is_array($result)) { echo json_encode(['error' => $result['error']]); exit; }
        $foto = $result;
    }

    $stmt = $conn->prepare("INSERT INTO stands (nama, kategori, foto, seller_id) VALUES (?,?,?,?)");
    $stmt->bind_param('sssi', $nama, $kategori, $foto, $seller_id);
    $stmt->execute(); $stmt->close();
    echo json_encode(['success'=>true, 'message'=>'Stand berhasil ditambahkan!']);

} elseif ($action === 'edit') {
    $id       = (int)($_POST['id'] ?? 0);
    $nama     = trim($_POST['nama'] ?? '');
    $kategori = $_POST['kategori'] ?? 'makanan';
    if (!$id || !$nama) { echo json_encode(['error'=>'Data tidak valid']); exit; }

    // Ambil foto lama
    $r = $conn->query("SELECT foto FROM stands WHERE id=$id AND seller_id=$seller_id");
    $old = $r->fetch_assoc();
    if (!$old) { echo json_encode(['error'=>'Stand tidak ditemukan']); exit; }

    $foto = $old['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $result = uploadFoto($_FILES['foto'], $upload_dir);
        if (is_array($result)) { echo json_encode(['error' => $result['error']]); exit; }
        // Hapus foto lama
        if ($old['foto'] && file_exists($upload_dir . $old['foto'])) unlink($upload_dir . $old['foto']);
        $foto = $result;
    }

    $stmt = $conn->prepare("UPDATE stands SET nama=?, kategori=?, foto=? WHERE id=? AND seller_id=?");
    $stmt->bind_param('sssii', $nama, $kategori, $foto, $id, $seller_id);
    $stmt->execute(); $stmt->close();
    echo json_encode(['success'=>true, 'message'=>'Stand berhasil diupdate!']);

} elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    // Hapus foto
    $r = $conn->query("SELECT foto FROM stands WHERE id=$id AND seller_id=$seller_id");
    $row = $r->fetch_assoc();
    if ($row && $row['foto'] && file_exists($upload_dir . $row['foto'])) unlink($upload_dir . $row['foto']);
    // Hapus foto menu items juga
    $r2 = $conn->query("SELECT foto FROM menu_items WHERE stand_id=$id");
    while ($mi = $r2->fetch_assoc()) {
        if ($mi['foto'] && file_exists($upload_dir . $mi['foto'])) unlink($upload_dir . $mi['foto']);
    }
    $stmt = $conn->prepare("DELETE FROM stands WHERE id=? AND seller_id=?");
    $stmt->bind_param('ii', $id, $seller_id);
    $stmt->execute(); $stmt->close();
    echo json_encode(['success'=>true]);

} else {
    echo json_encode(['error'=>'Action tidak valid']);
}
$conn->close();