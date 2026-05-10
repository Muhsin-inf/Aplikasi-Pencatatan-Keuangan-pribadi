<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers");

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$user_id = $_SESSION['user_id'];

switch ($method) {
    case 'GET': // Ambil daftar dompet beserta total saldonya
        $sql = "SELECT w.id, w.name, w.icon_name, w.color,
                COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END) - 
                         SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS balance
                FROM wallets w
                LEFT JOIN transactions t ON w.id = t.wallet_id
                WHERE w.user_id = ?
                GROUP BY w.id ORDER BY w.id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $wallets = [];
        while ($row = $res->fetch_assoc()) {
            $row['balance'] = (float)$row['balance'];
            $wallets[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $wallets]);
        break;

    case 'POST': // Tambah dompet baru
        $name = $input['name'] ?? '';
        $icon = $input['icon_name'] ?? 'wallet';
        $color = $input['color'] ?? '#3B82F6';
        if (empty($name)) { echo json_encode(["status" => "error", "message" => "Nama dompet wajib diisi."]); break; }
        
        $stmt = $conn->prepare("INSERT INTO wallets (user_id, name, icon_name, color) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $name, $icon, $color);
        if ($stmt->execute()) echo json_encode(["status" => "success", "message" => "Dompet berhasil dibuat!"]);
        else echo json_encode(["status" => "error", "message" => "Gagal: " . $conn->error]);
        break;

    case 'PUT': // Edit dompet
        $id = intval($input['id'] ?? 0);
        $name = $input['name'] ?? '';
        if ($id === 0 || empty($name)) { echo json_encode(["status" => "error", "message" => "Data tidak lengkap."]); break; }
        
        $stmt = $conn->prepare("UPDATE wallets SET name = ?, icon_name = ?, color = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssii", $name, $input['icon_name'], $input['color'], $id, $user_id);
        if ($stmt->execute()) echo json_encode(["status" => "success", "message" => "Dompet diperbarui!"]);
        break;

    case 'DELETE': // Hapus dompet
        $id = intval($input['id'] ?? 0);
        $check = $conn->prepare("SELECT id FROM transactions WHERE wallet_id = ? LIMIT 1");
        $check->bind_param("i", $id); $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Dompet tidak bisa dihapus karena berisi riwayat transaksi. Hapus/pindahkan transaksi terlebih dahulu."]);
            break;
        }
        $stmt = $conn->prepare("DELETE FROM wallets WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        if ($stmt->execute()) echo json_encode(["status" => "success", "message" => "Dompet dihapus."]);
        break;
}
$conn->close();
?>