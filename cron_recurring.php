<?php
// Script ini dijalankan otomatis oleh Server (aaPanel)
require_once 'api/config.php';

$today = date('Y-m-d');

// Cari semua jadwal yang aktif DAN waktunya dieksekusi hari ini (atau terlewat)
$sql = "SELECT * FROM recurring_transactions WHERE status = 'active' AND next_run_date <= ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $today);
$stmt->execute();
$result = $stmt->get_result();

$count = 0;
while ($row = $result->fetch_assoc()) {
    $conn->begin_transaction();
    try {
        // 1. Eksekusi: Masukkan ke tabel transaksi agar saldo dompet terpotong/bertambah
        $insert_sql = "INSERT INTO transactions (user_id, wallet_id, category_id, amount, type, note, date) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($insert_sql);
        $note_otomatis = $row['note'] . " (Otomatis)";
        $stmt_insert->bind_param("iiidsss", $row['user_id'], $row['wallet_id'], $row['category_id'], $row['amount'], $row['type'], $note_otomatis, $today);
        $stmt_insert->execute();

        // 2. Hitung tanggal eksekusi selanjutnya berdasarkan interval
        $interval = $row['repeat_interval']; // Contoh: 1
        $unit = $row['repeat_unit'];         // Contoh: month
        
        // Logika PHP strtotime yang sangat canggih: "+1 month", "+2 week", dll.
        $next_date = date('Y-m-d', strtotime($row['next_run_date'] . " + $interval $unit"));

        // 3. Perbarui tanggal next_run_date di tabel jadwal
        $update_sql = "UPDATE recurring_transactions SET next_run_date = ? WHERE id = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("si", $next_date, $row['id']);
        $stmt_update->execute();

        $conn->commit();
        $count++;
    } catch (Exception $e) {
        $conn->rollback();
        // Log error jika diperlukan
    }
}

echo "Cron Selesai. $count transaksi otomatis berhasil diproses pada $today.";
?>