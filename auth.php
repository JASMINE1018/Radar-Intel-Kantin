<?php
// auth.php — include di semua halaman
session_start();
// Cek status login user 

function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['owner_id']);
}
//  Ambil data user aktif

function currentUser() {
    return [
        'id'   => $_SESSION['user_id'] ?? $_SESSION['owner_id'] ?? null,
        'nama' => $_SESSION['user_nama'] ?? $_SESSION['owner_nama'] ?? null,
    ];
}
// Wajibkan user login 
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}