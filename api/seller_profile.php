<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../seller_auth.php';
require_once '../config.php';

if (!isset($_SESSION['seller_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$seller_id = (int)$_SESSION['seller_id'];
$nama = trim($_POST['nama'] ?? '');
$tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
$deskripsi = trim($_POST['deskripsi'] ?? '');

if ($nama === '') {
    echo json_encode(['error' => 'Username wajib diisi']);
    exit;
}

if ($tanggal_lahir !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_lahir)) {
    echo json_encode(['error' => 'Format tanggal lahir tidak valid']);
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS seller_profiles (
    seller_id INT PRIMARY KEY,
    tanggal_lahir DATE NULL,
    deskripsi TEXT NULL,
    foto_profile VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_seller_profiles_seller FOREIGN KEY (seller_id) REFERENCES sellers(id) ON DELETE CASCADE
)");

$currentPhoto = '';
$photoResult = $conn->query("SELECT foto_profile FROM seller_profiles WHERE seller_id = $seller_id LIMIT 1");
if ($photoResult && $photoResult->num_rows > 0) {
    $photoRow = $photoResult->fetch_assoc();
    $currentPhoto = $photoRow['foto_profile'] ?? '';
}

$uploadedPhotoPath = $currentPhoto;
if (isset($_FILES['foto_profile']) && isset($_FILES['foto_profile']['error']) && $_FILES['foto_profile']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['foto_profile']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Gagal upload foto profil']);
        exit;
    }

    if ($_FILES['foto_profile']['size'] > 2 * 1024 * 1024) {
        echo json_encode(['error' => 'Ukuran foto maksimal 2MB']);
        exit;
    }

    $tmp = $_FILES['foto_profile']['tmp_name'];
    $mime = mime_content_type($tmp);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        echo json_encode(['error' => 'Format foto tidak valid']);
        exit;
    }

    $uploadDirFs = dirname(__DIR__) . '/uploads/seller_profile';
    if (!is_dir($uploadDirFs) && !mkdir($uploadDirFs, 0755, true)) {
        echo json_encode(['error' => 'Folder upload tidak tersedia']);
        exit;
    }

    $fileName = 'seller_' . $seller_id . '_' . time() . '.' . $allowed[$mime];
    $targetFs = $uploadDirFs . '/' . $fileName;

    if (!move_uploaded_file($tmp, $targetFs)) {
        echo json_encode(['error' => 'Tidak bisa menyimpan foto profil']);
        exit;
    }

    if ($currentPhoto) {
        $oldFs = dirname(__DIR__) . '/' . ltrim($currentPhoto, '/');
        if (is_file($oldFs)) {
            @unlink($oldFs);
        }
    }

    $uploadedPhotoPath = 'uploads/seller_profile/' . $fileName;
}

$stmt = $conn->prepare("UPDATE sellers SET nama = ? WHERE id = ?");
$stmt->bind_param('si', $nama, $seller_id);
$stmt->execute();
$stmt->close();

$tanggal_lahir_param = $tanggal_lahir !== '' ? $tanggal_lahir : null;
$deskripsi_param = $deskripsi !== '' ? $deskripsi : null;

$stmt = $conn->prepare("INSERT INTO seller_profiles (seller_id, tanggal_lahir, deskripsi, foto_profile) VALUES (?, ?, ?, ?)
                       ON DUPLICATE KEY UPDATE tanggal_lahir = VALUES(tanggal_lahir), deskripsi = VALUES(deskripsi), foto_profile = VALUES(foto_profile)");
$stmt->bind_param('isss', $seller_id, $tanggal_lahir_param, $deskripsi_param, $uploadedPhotoPath);
$stmt->execute();
$stmt->close();

$_SESSION['seller_nama'] = $nama;

echo json_encode([
    'success' => true,
    'message' => 'Biodata seller berhasil disimpan.',
    'photo_url' => $uploadedPhotoPath,
]);

$conn->close();
