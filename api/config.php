<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$secrets = require __DIR__ . '/../secrets/secrets.php';

$host = $secrets['db_host']; 
$user = $secrets['db_user'];
$pass = $secrets['db_pass']; 
$dbname = $secrets['db_name'];

$conn = new mysqli($host, $user, $pass, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die(json_encode([
        "status" => "error", 
        "message" => "Koneksi database gagal: " . $conn->connect_error
    ]));
}

// =========================================================
// SISTEM AUTO-LOGIN (REMEMBER ME)
// Mengeksekusi login otomatis jika Session hilang tapi Cookie ada
// =========================================================
if (!isset($_SESSION['user_id']) && isset($_COOKIE['smartfinance_remember'])) {
    $cookie_data = base64_decode($_COOKIE['smartfinance_remember']);
    $parts = explode('::', $cookie_data);
    
    // Pastikan format cookie tidak dimanipulasi
    if (count($parts) === 2) {
        $cookie_id = intval($parts[0]);
        $cookie_hash = $parts[1];

        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE id = ?");
        $stmt->bind_param("i", $cookie_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $u = $res->fetch_assoc();
            // Verifikasi bahwa hash password di database cocok dengan di cookie
            if ($u['password'] === $cookie_hash) {
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['user_name'] = $u['name'];
            }
        }
        $stmt->close();
    }
}
?>