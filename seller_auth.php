<?php
// seller_auth.php
if (session_status() === PHP_SESSION_NONE) session_start();
// Cek status login seller 
function isSellerLoggedIn() {
    return isset($_SESSION['seller_id']);
}
// Ambil data seller aktif 
function currentSeller() {
    return [
        'id'   => $_SESSION['seller_id']   ?? null,
        'nama' => $_SESSION['seller_nama'] ?? null,
    ];
}
//Wajibkan seller login 
function requireSeller() {
    if (!isSellerLoggedIn()) {
        header('Location: seller_login.php');
        exit;
    }
}
// Akhir autentikasi seller dan helper functions 