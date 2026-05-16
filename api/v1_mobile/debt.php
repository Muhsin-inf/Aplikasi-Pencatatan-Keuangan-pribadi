<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config.php'; // Sesuaikan path config Anda

// --- AUTHENTICATION CHECK ---
// Untuk Web: Masih menggunakan Session. 
// Untuk Android: Kedepannya bagian ini diganti pengecekan Bearer Token/JWT.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized. Silakan login terlebih dahulu."]);
    exit;
}
$user_id = $_SESSION['user_id'];

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

// Helper: Fungsi Cari/Buat Kategori (Sama seperti sebelumnya)
function getDebtCategoryId($conn, $user_id) {
    $category_name = 'Hutang & Pinjaman';
    $stmt = $conn->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $category_name);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) return $res->fetch_assoc()['id'];
    
    $ins = $conn->prepare("INSERT INTO categories (user_id, name, type, icon_name, color) VALUES (?, ?, 'expense', 'card-outline', '#ef4444')");
    $ins->bind_param("is", $user_id, $category_name);
    $ins->execute();
    return $conn->insert_id;
}

switch ($method) {
    
    // ==========================================
    // GET: MENGAMBIL DAFTAR HUTANG
    // ==========================================
    case 'GET':
        $query = "SELECT * FROM debts WHERE user_id = ? ORDER BY status ASC, due_date ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        http_response_code(200);
        echo json_encode(["status" => "success", "data" => $data]);
        break;

    // ==========================================
    // POST: MEMBUAT HUTANG BARU
    // ==========================================
    case 'POST':
        $name = $input['name'] ?? '';
        $type = $input['type'] ?? '';
        $principal = floatval($input['principal'] ?? 0);
        $interest = floatval($input['interest'] ?? 0);
        $wallet_id = intval($input['wallet_id'] ?? 0);
        $due_date = $input['due_date'] ?? '';
        $total = $principal + $interest;

        if (empty($name) || $principal <= 0 || $wallet_id <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data input tidak valid."]);
            exit;
        }

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO debts (user_id, wallet_id, name, type, principal_amount, interest_amount, total_amount, remaining_amount, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iissdddds", $user_id, $wallet_id, $name, $type, $principal, $interest, $total, $total, $due_date);
            $stmt->execute();
            $debt_id = $conn->insert_id;

            // Catat transaksi awal di dompet
            $trans_type = ($type === 'payable') ? 'income' : 'expense';
            $cat_id = getDebtCategoryId($conn, $user_id);
            $note = "Pencairan Hutang/Piutang: $name";

            $stmt2 = $conn->prepare("INSERT INTO transactions (user_id, wallet_id, category_id, debt_id, amount, type, note, date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt2->bind_param("iiiidss", $user_id, $wallet_id, $cat_id, $debt_id, $principal, $trans_type, $note);
            $stmt2->execute();

            $conn->commit();
            http_response_code(201);
            echo json_encode(["status" => "success", "message" => "Hutang berhasil dibuat."]);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // ==========================================
    // PUT: BAYAR CICILAN
    // ==========================================
    case 'PUT':
        $debt_id = intval($input['debt_id'] ?? 0);
        $pay_amount = floatval($input['amount'] ?? 0);
        $wallet_id = intval($input['wallet_id'] ?? 0);

        if ($debt_id <= 0 || $pay_amount <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Data pembayaran tidak valid."]);
            exit;
        }

        $stmt = $conn->prepare("SELECT type, remaining_amount, name FROM debts WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $debt_id, $user_id);
        $stmt->execute();
        $debt = $stmt->get_result()->fetch_assoc();

        if (!$debt || $pay_amount > $debt['remaining_amount']) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Nominal melebihi sisa hutang."]);
            exit;
        }

        $conn->begin_transaction();
        try {
            $new_remain = $debt['remaining_amount'] - $pay_amount;
            $status = ($new_remain <= 0) ? 'paid' : 'active';
            
            $upd = $conn->prepare("UPDATE debts SET remaining_amount = ?, status = ? WHERE id = ?");
            $upd->bind_param("dsi", $new_remain, $status, $debt_id);
            $upd->execute();

            $trans_type = ($debt['type'] === 'payable') ? 'expense' : 'income';
            $cat_id = getDebtCategoryId($conn, $user_id);
            $note = "Cicilan Hutang/Piutang: " . $debt['name'];

            $stmt_t = $conn->prepare("INSERT INTO transactions (user_id, wallet_id, category_id, debt_id, amount, type, note, date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt_t->bind_param("iiiidss", $user_id, $wallet_id, $cat_id, $debt_id, $pay_amount, $trans_type, $note);
            $stmt_t->execute();

            $conn->commit();
            echo json_encode(["status" => "success", "message" => "Cicilan berhasil dicatat."]);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    // ==========================================
    // DELETE: HAPUS TOTAL
    // ==========================================
    case 'DELETE':
        $debt_id = intval($input['debt_id'] ?? 0);
        
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM transactions WHERE debt_id = $debt_id AND user_id = $user_id");
            $conn->query("DELETE FROM debts WHERE id = $debt_id AND user_id = $user_id");
            
            $conn->commit();
            echo json_encode(["status" => "success", "message" => "Hutang dan riwayat berhasil dihapus."]);
        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
        break;
}