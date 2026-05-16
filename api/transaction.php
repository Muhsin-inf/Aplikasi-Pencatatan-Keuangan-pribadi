<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers");

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$user_id = $_SESSION['user_id'];

switch ($method) {
    case 'GET': // MENGAMBIL RIWAYAT TRANSAKSI
        // Filter opsional: 'all' atau 'this_month'
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        $date_condition = "";
        
        if ($filter === 'this_month') {
            $date_condition = " AND MONTH(t.date) = MONTH(CURRENT_DATE()) AND YEAR(t.date) = YEAR(CURRENT_DATE())";
        }

        $query = "SELECT t.id, t.amount, t.type, t.note, t.date, t.created_at,
                         c.name as category_name, c.icon_name, c.color,
                         w.name as wallet_name
                  FROM transactions t
                  JOIN categories c ON t.category_id = c.id
                  JOIN wallets w ON t.wallet_id = w.id
                  WHERE t.user_id = ? $date_condition
                  ORDER BY t.date DESC, t.id DESC";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $data = [];
        while ($row = $res->fetch_assoc()) {
            $row['amount'] = (float)$row['amount'];
            $data[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $data]);
        $stmt->close();
        break;

    case 'POST': // MENYIMPAN TRANSAKSI BARU (Sama seperti sebelumnya)
        $wallet_id = isset($input['wallet_id']) ? intval($input['wallet_id']) : 0;
        $category_id = isset($input['category_id']) ? intval($input['category_id']) : 0;
        $type = $input['type'] ?? 'expense';
        $date = $input['date'] ?? date('Y-m-d');
        
        $raw_amount = $input['amount'] ?? '0';
        $amount = (float) preg_replace('/[^0-9.]/', '', $raw_amount);
        $note = isset($input['note']) ? htmlspecialchars(strip_tags($input['note']), ENT_QUOTES, 'UTF-8') : '';

        if ($amount <= 0 || $wallet_id === 0 || $category_id === 0) {
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap/valid."]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO transactions (user_id, wallet_id, category_id, amount, type, note, date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiidsss", $user_id, $wallet_id, $category_id, $amount, $type, $note, $date);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Transaksi dicatat!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal: " . $conn->error]);
        }
        $stmt->close();
        break;

    case 'DELETE': // MENGHAPUS TRANSAKSI
        $id = intval($input['id'] ?? 0);
        if ($id === 0) {
            echo json_encode(["status" => "error", "message" => "ID Transaksi tidak valid."]);
            exit;
        }

        // 1. Cek dulu apakah transaksi ini bagian dari Hutang/Paylater
        $stmt_check = $conn->prepare("SELECT debt_id FROM transactions WHERE id = ? AND user_id = ?");
        $stmt_check->bind_param("ii", $id, $user_id);
        $stmt_check->execute();
        $trans = $stmt_check->get_result()->fetch_assoc();

        if (!$trans) {
            echo json_encode(["status" => "error", "message" => "Transaksi tidak ditemukan."]);
            exit;
        }

        // 2. Jika transaksi memiliki debt_id, blokir penghapusan
        if (!empty($trans['debt_id'])) {
            echo json_encode([
                "status" => "error", 
                "message" => "Riwayat tidak dapat dihapus. Silakan hapus data di halaman Hutang, maka semua riwayat terkait akan otomatis terhapus."
            ]);
            exit;
        }

        // 3. Jika bukan transaksi hutang, lanjutkan penghapusan biasa
        $stmt_del = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        $stmt_del->bind_param("ii", $id, $user_id);
        
        if ($stmt_del->execute()) {
            echo json_encode(["status" => "success", "message" => "Transaksi berhasil dihapus."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal menghapus: " . $conn->error]);
        }
        $stmt_del->close();
        break;
}

$conn->close();
?>