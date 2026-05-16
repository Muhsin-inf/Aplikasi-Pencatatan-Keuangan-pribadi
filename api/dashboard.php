<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(["status" => "error", "message" => "Metode HTTP tidak didukung. Gunakan GET."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'this_month';

// Logika Filter Waktu MariaDB
$date_condition = "";
if ($filter === 'this_month') {
    $date_condition = " AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())";
} elseif ($filter === 'last_month') {
    $date_condition = " AND MONTH(date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(date) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)";
} elseif ($filter === 'all') {
    $date_condition = ""; 
}

// 1. Hitung Total Saldo Keseluruhan[cite: 4]
$query_balance = "SELECT 
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) - 
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS absolute_balance 
    FROM transactions WHERE user_id = ?";

$stmt_balance = $conn->prepare($query_balance);
$stmt_balance->bind_param("i", $user_id);
$stmt_balance->execute();
$res_balance = $stmt_balance->get_result()->fetch_assoc();
$total_balance = $res_balance['absolute_balance'] ?? 0;
$stmt_balance->close();

// 2. Hitung Pemasukan & Pengeluaran[cite: 4]
$query_sum = "SELECT 
    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) AS period_income,
    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS period_expense
    FROM transactions WHERE user_id = ?" . $date_condition;

$stmt_sum = $conn->prepare($query_sum);
$stmt_sum->bind_param("i", $user_id);
$stmt_sum->execute();
$res_sum = $stmt_sum->get_result()->fetch_assoc();
$total_income = $res_sum['period_income'] ?? 0;
$total_expense = $res_sum['period_expense'] ?? 0;
$stmt_sum->close();

// 3. PERBAIKAN: Ambil Transaksi Terakhir (Gunakan LEFT JOIN dan COALESCE)
$query_recent = "SELECT t.*, 
    COALESCE(c.name, 'Lainnya') as cat_name, 
    COALESCE(c.icon_name, 'wallet') as icon_name, 
    COALESCE(c.color, '#9CA3AF') as color 
    FROM transactions t LEFT JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ?" . str_replace("MONTH(date)", "MONTH(t.date)", str_replace("YEAR(date)", "YEAR(t.date)", $date_condition)) . " 
    ORDER BY t.date DESC, t.created_at DESC LIMIT 5"; // <-- PENAMBAHAN t.created_at DESC DI SINI

$stmt_recent = $conn->prepare($query_recent);
$stmt_recent->bind_param("i", $user_id);
$stmt_recent->execute();
$res_recent = $stmt_recent->get_result();

$recent_transactions = [];
while($row = $res_recent->fetch_assoc()) {
    $row['amount'] = (float)$row['amount'];
    $recent_transactions[] = $row;
}
$stmt_recent->close();

// 4. PERBAIKAN: Ambil Data Chart (Gunakan LEFT JOIN)
// GROUP BY diubah menjadi t.category_id agar kategori 'Lainnya' terkumpul jadi satu
$query_chart = "SELECT COALESCE(c.name, 'Lainnya') as name, SUM(t.amount) as total 
    FROM transactions t LEFT JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = ? AND t.type = 'expense'" . str_replace("MONTH(date)", "MONTH(t.date)", str_replace("YEAR(date)", "YEAR(t.date)", $date_condition)) . " 
    GROUP BY t.category_id";

$stmt_chart = $conn->prepare($query_chart);
$stmt_chart->bind_param("i", $user_id);
$stmt_chart->execute();
$res_chart = $stmt_chart->get_result();

$chart_labels = [];
$chart_data = [];
while($row = $res_chart->fetch_assoc()) {
    $chart_labels[] = $row['name'];
    $chart_data[] = (float)$row['total'];
}
$stmt_chart->close();

// Kirim Response JSON RESTful[cite: 4]
echo json_encode([
    "status" => "success",
    "filter_active" => $filter,
    "data" => [
        "summary" => [
            "balance" => $total_balance,
            "income" => $total_income,
            "expense" => $total_expense
        ],
        "recent_transactions" => $recent_transactions,
        "chart" => [
            "labels" => $chart_labels,
            "data" => $chart_data
        ]
    ]
]);

$conn->close();
?>