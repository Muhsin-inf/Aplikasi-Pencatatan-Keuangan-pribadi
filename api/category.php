<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];


$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = []; 
}

$user_id = $_SESSION['user_id'];

switch ($method) {
    case 'GET':
        $sql = "SELECT * FROM categories WHERE user_id = ? ORDER BY id ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        echo json_encode(["status" => "success", "data" => $categories]);
        $stmt->close();
        break;

    case 'POST':
        $name = $input['name'] ?? '';
        $type = $input['type'] ?? 'expense';
        $icon_name = $input['icon_name'] ?? 'box';
        $color = $input['color'] ?? '#3B82F6';

        if (empty($name)) {
            echo json_encode(["status" => "error", "message" => "Nama kategori wajib diisi."]);
            break;
        }

        $stmt = $conn->prepare("INSERT INTO categories (user_id, name, type, icon_name, color) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user_id, $name, $type, $icon_name, $color);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Kategori berhasil dibuat!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal: " . $conn->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        $id = isset($input['id']) ? intval($input['id']) : 0;
        $name = $input['name'] ?? '';
        $icon_name = $input['icon_name'] ?? '';
        $color = $input['color'] ?? '';

        if ($id === 0 || empty($name)) {
            echo json_encode(["status" => "error", "message" => "Data tidak lengkap untuk update."]);
            break;
        }

        $stmt = $conn->prepare("UPDATE categories SET name = ?, icon_name = ?, color = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sssii", $name, $icon_name, $color, $id, $user_id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Kategori berhasil diperbarui!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal update: " . $conn->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $id = isset($input['id']) ? intval($input['id']) : 0;

        if ($id === 0) {
            echo json_encode(["status" => "error", "message" => "ID tidak valid."]);
            break;
        }

        $check = $conn->prepare("SELECT id FROM transactions WHERE category_id = ? LIMIT 1");
        $check->bind_param("i", $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "Kategori tidak bisa dihapus karena terikat dengan riwayat transaksi."]);
            $check->close();
            break;
        }
        $check->close();

        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Kategori berhasil dihapus."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Gagal hapus: " . $conn->error]);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Metode HTTP tidak didukung."]);
        break;
}
$conn->close();
?>