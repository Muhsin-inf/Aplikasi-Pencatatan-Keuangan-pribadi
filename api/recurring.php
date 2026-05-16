<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, PUT");

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit;
}
$user_id = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

switch ($method) {
    case 'GET': // Ambil semua jadwal
        $query = "SELECT r.*, w.name as wallet_name, c.name as category_name, c.icon_name, c.color 
                  FROM recurring_transactions r
                  JOIN wallets w ON r.wallet_id = w.id
                  JOIN categories c ON r.category_id = c.id
                  WHERE r.user_id = ? ORDER BY r.next_run_date ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = [];
        while ($row = $res->fetch_assoc()) $data[] = $row;
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    case 'POST': // Buat jadwal baru
        $wallet_id = intval($input['wallet_id'] ?? 0);
        $category_id = intval($input['category_id'] ?? 0);
        $type = $input['type'] ?? '';
        $amount = floatval($input['amount'] ?? 0);
        $note = $input['note'] ?? '';
        $repeat_interval = intval($input['repeat_interval'] ?? 1);
        $repeat_unit = $input['repeat_unit'] ?? 'month';
        $next_run_date = $input['next_run_date'] ?? '';

        if ($wallet_id <= 0 || $category_id <= 0 || $amount <= 0 || empty($next_run_date)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO recurring_transactions (user_id, wallet_id, category_id, type, amount, note, repeat_interval, repeat_unit, next_run_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisdsiss", $user_id, $wallet_id, $category_id, $type, $amount, $note, $repeat_interval, $repeat_unit, $next_run_date);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Jadwal transaksi berulang berhasil dibuat!"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Gagal menyimpan jadwal."]);
        }
        break;

    case 'PUT': // Pause / Resume Jadwal
        $id = intval($input['id'] ?? 0);
        $status = $input['status'] ?? 'active'; // 'active' atau 'paused'
        
        $stmt = $conn->prepare("UPDATE recurring_transactions SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $status, $id, $user_id);
        $stmt->execute();
        echo json_encode(["status" => "success", "message" => "Status jadwal diperbarui"]);
        break;

    case 'DELETE': // Hapus jadwal
        $id = intval($input['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM recurring_transactions WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        echo json_encode(["status" => "success", "message" => "Jadwal dihapus. Riwayat yang sudah terjadi tetap aman."]);
        break;
}
?>