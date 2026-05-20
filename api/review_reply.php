<?php
header('Content-Type: application/json');
require_once '../seller_auth.php';
require_once '../config.php';

if (!isSellerLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Login seller dulu.']);
    exit;
}

$seller_id = (int)$_SESSION['seller_id'];
$review_id  = (int)($_POST['review_id'] ?? 0);
$reply      = trim($_POST['reply'] ?? '');

if ($review_id <= 0 || $reply === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap.']);
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

$stmt = $conn->prepare("SELECT id FROM reviews WHERE id = ?");
$stmt->bind_param('i', $review_id);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$review) {
    http_response_code(403);
    echo json_encode(['error' => 'Review tidak ditemukan.']);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO review_replies (review_id, seller_id, reply)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE seller_id = VALUES(seller_id), reply = VALUES(reply), updated_at = NOW()"
);
$stmt->bind_param('iis', $review_id, $seller_id, $reply);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("SELECT reply, updated_at FROM review_replies WHERE review_id = ?");
$stmt->bind_param('i', $review_id);
$stmt->execute();
$saved = $stmt->get_result()->fetch_assoc();
$stmt->close();

$conn->close();

echo json_encode([
    'success' => true,
    'reply' => [
        'review_id' => $review_id,
        'reply' => $saved['reply'] ?? $reply,
        'waktu' => $saved['updated_at'] ?? 'Baru saja',
    ],
]);
