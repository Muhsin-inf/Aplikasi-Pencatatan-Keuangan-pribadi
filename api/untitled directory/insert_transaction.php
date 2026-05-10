<?php
// Set headers agar bisa diakses oleh aplikasi mobile & web (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require 'config.php'; // Mengambil koneksi database

// Cek apakah metode request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ambil data (Mendukung input form biasa dan JSON dari Axios)
    $data = json_decode(file_get_contents("php://input"), true);
    
    $user_id     = isset($_POST['user_id']) ? $_POST['user_id'] : ($data['user_id'] ?? 1); // Default ke user Muhsin (ID 1)
    $category_id = isset($_POST['category_id']) ? $_POST['category_id'] : ($data['category_id'] ?? null);
    $amount      = isset($_POST['amount']) ? $_POST['amount'] : ($data['amount'] ?? 0);
    $type        = isset($_POST['type']) ? $_POST['type'] : ($data['type'] ?? 'expense');
    $date        = isset($_POST['date']) ? $_POST['date'] : ($data['date'] ?? date('Y-m-d'));
    $note        = isset($_POST['note']) ? $_POST['note'] : ($data['note'] ?? '');

    // Validasi sederhana
    if (!$category_id || $amount <= 0) {
        echo json_encode(["status" => "error", "message" => "Data tidak lengkap atau nominal tidak valid."]);
        exit;
    }

    // Query prepared statement untuk keamanan (Anti SQL Injection) 
    $query = "INSERT INTO transactions (user_id, category_id, amount, type, date, note) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iissss", $user_id, $category_id, $amount, $type, $date, $note);

    if ($stmt->execute()) {
        // Jika sukses, kirim respon JSON
        echo json_encode([
            "status" => "success",
            "message" => "Transaksi berhasil disimpan",
            "id" => $conn->insert_id
        ]);
        
        // Opsional: Redirect jika dipanggil dari browser (web form)
        // header("Location: ../index?msg=success"); 
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal menyimpan data: " . $conn->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["status" => "error", "message" => "Metode request tidak diizinkan."]);
}

$conn->close();
?>