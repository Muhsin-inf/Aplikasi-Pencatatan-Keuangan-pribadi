<?php
header("Content-Type: application/json; charset=UTF-8");

require 'config.php';

//default users 1
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 1;


$sql = "SELECT t.id, t.amount, t.type, t.note, t.date, 
               c.name as category_name, c.icon_name, c.color 
        FROM transactions t 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id = ? 
        ORDER BY t.date DESC";

// Prepared Statement
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$transactions = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['amount'] = (float)$row['amount'];
        $transactions[] = $row;
    }
    
    // Kirim respons JSON sukses
    echo json_encode([
        "status" => "success", 
        "data" => $transactions
    ]);
} else {
    // Kirim respons JSON jika data kosong
    echo json_encode([
        "status" => "success", 
        "data" => [], 
        "message" => "Belum ada transaksi."
    ]);
}


$stmt->close();
$conn->close();
?>