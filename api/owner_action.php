<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config.php';

// Cek login owner
if (!isset($_SESSION['owner_id'])) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

$action = $_POST['action'] ?? '';

// ── UPDATE SELLER STATUS ──
if ($action === 'update_seller') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!in_array($status, ['active','rejected','pending'])) {
        echo json_encode(['error' => 'Status tidak valid']); exit;
    }
    $stmt = $conn->prepare("UPDATE sellers SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $status, $id);
    $stmt->execute(); $stmt->close();
    $msg = $status === 'active' ? 'Seller berhasil di-approve!' : 'Seller berhasil di-reject.';
    echo json_encode(['success' => true, 'message' => $msg]);

// ── DELETE SELLER ──
} elseif ($action === 'delete_seller') {
    $id = (int)($_POST['id'] ?? 0);
    // Hapus stands milik seller (cascade ke menu_items)
    $conn->query("DELETE FROM menu_items WHERE stand_id IN (SELECT id FROM stands WHERE seller_id = $id)");
    $conn->query("DELETE FROM stands WHERE seller_id = $id");
    $stmt = $conn->prepare("DELETE FROM sellers WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Akun seller dihapus.']);

// ── DELETE USER ──
} elseif ($action === 'delete_user') {
    $id = (int)($_POST['id'] ?? 0);
    // Hapus reviews & ratings dulu
    $conn->query("DELETE FROM reviews WHERE user_id = $id");
    $conn->query("DELETE FROM ratings_stand WHERE user_id = $id");
    $conn->query("DELETE FROM ratings_menu WHERE user_id = $id");
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Akun user dihapus.']);

// ── DELETE STAND ──
} elseif ($action === 'delete_stand') {
    $id = (int)($_POST['id'] ?? 0);
    $conn->query("DELETE FROM menu_items WHERE stand_id = $id");
    $stmt = $conn->prepare("DELETE FROM stands WHERE id = ?");
    $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
    echo json_encode(['success' => true, 'message' => 'Stand dihapus.']);

// ── UPDATE OWNER BIODATA ──
} elseif ($action === 'update_owner_profile') {
    $owner_id = (int)$_SESSION['owner_id'];
    $nama = trim($_POST['nama'] ?? '');
    $tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama === '') {
        echo json_encode(['error' => 'Username wajib diisi']); exit;
    }

    if ($tanggal_lahir !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_lahir)) {
        echo json_encode(['error' => 'Format tanggal lahir tidak valid']); exit;
    }

    // Pastikan tabel biodata owner tersedia.
    $conn->query("CREATE TABLE IF NOT EXISTS owner_profiles (
        owner_id INT PRIMARY KEY,
        tanggal_lahir DATE NULL,
        deskripsi TEXT NULL,
        foto_profile VARCHAR(255) NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_owner_profiles_owner FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE
    )");

    $fotoCol = $conn->query("SHOW COLUMNS FROM owner_profiles LIKE 'foto_profile'");
    if ($fotoCol && $fotoCol->num_rows === 0) {
        $conn->query("ALTER TABLE owner_profiles ADD COLUMN foto_profile VARCHAR(255) NULL AFTER deskripsi");
    }

    $currentPhoto = '';
    $photoResult = $conn->query("SELECT foto_profile FROM owner_profiles WHERE owner_id = $owner_id LIMIT 1");
    if ($photoResult && $photoResult->num_rows > 0) {
        $photoRow = $photoResult->fetch_assoc();
        $currentPhoto = $photoRow['foto_profile'] ?? '';
    }

    $uploadedPhotoPath = $currentPhoto;
    if (isset($_FILES['foto_profile']) && isset($_FILES['foto_profile']['error']) && $_FILES['foto_profile']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['foto_profile']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'Gagal upload foto profil']); exit;
        }

        if ($_FILES['foto_profile']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['error' => 'Ukuran foto maksimal 2MB']); exit;
        }

        $tmp = $_FILES['foto_profile']['tmp_name'];
        $mime = mime_content_type($tmp);
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        if (!isset($allowed[$mime])) {
            echo json_encode(['error' => 'Format foto tidak valid']); exit;
        }

        $uploadDirFs = dirname(__DIR__) . '/uploads/owner_profile';
        if (!is_dir($uploadDirFs) && !mkdir($uploadDirFs, 0755, true)) {
            echo json_encode(['error' => 'Folder upload tidak tersedia']); exit;
        }

        $fileName = 'owner_' . $owner_id . '_' . time() . '.' . $allowed[$mime];
        $targetFs = $uploadDirFs . '/' . $fileName;
        if (!move_uploaded_file($tmp, $targetFs)) {
            echo json_encode(['error' => 'Tidak bisa menyimpan foto profil']); exit;
        }

        // Hapus file lama jika ada.
        if ($currentPhoto) {
            $oldFs = dirname(__DIR__) . '/' . ltrim($currentPhoto, '/');
            if (is_file($oldFs)) {
                @unlink($oldFs);
            }
        }

        $uploadedPhotoPath = 'uploads/owner_profile/' . $fileName;
    }

    $stmt = $conn->prepare("UPDATE owners SET nama = ? WHERE id = ?");
    $stmt->bind_param('si', $nama, $owner_id);
    $stmt->execute();
    $stmt->close();

    $tanggal_lahir_param = $tanggal_lahir !== '' ? $tanggal_lahir : null;
    $deskripsi_param = $deskripsi !== '' ? $deskripsi : null;

    $stmt = $conn->prepare("INSERT INTO owner_profiles (owner_id, tanggal_lahir, deskripsi, foto_profile) VALUES (?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE tanggal_lahir = VALUES(tanggal_lahir), deskripsi = VALUES(deskripsi), foto_profile = VALUES(foto_profile)");
    $stmt->bind_param('isss', $owner_id, $tanggal_lahir_param, $deskripsi_param, $uploadedPhotoPath);
    $stmt->execute();
    $stmt->close();

    $_SESSION['owner_nama'] = $nama;
    echo json_encode(['success' => true, 'message' => 'Biodata berhasil disimpan.', 'photo_url' => $uploadedPhotoPath]);

} else {
    echo json_encode(['error' => 'Action tidak valid']);
}
$conn->close();