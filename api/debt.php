<?php
header('Content-Type: application/json');
require_once 'config.php';

// ==========================================
// 1. KEAMANAN & SESI
// ==========================================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Anda belum login."]);
    exit;
}
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Metode HTTP tidak didukung."]);
    exit;
}

// ==========================================
// 2. FUNGSI HELPER: KATEGORI HUTANG OTOMATIS
// ==========================================
function getDebtCategoryId($conn, $user_id) {
    $category_name = 'Hutang & Pinjaman';
    
    // Cek apakah kategori sudah ada
    $stmt = $conn->prepare("SELECT id FROM categories WHERE user_id = ? AND name = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $category_name);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        return $res->fetch_assoc()['id']; // Kembalikan ID jika ketemu
    }

    // Jika tidak ada (dihapus user/belum dibuat), buat otomatis
    $type = 'expense'; 
    $icon = 'card-outline'; 
    $color = '#ef4444'; 
    
    $ins = $conn->prepare("INSERT INTO categories (user_id, name, type, icon_name, color) VALUES (?, ?, ?, ?, ?)");
    $ins->bind_param("issss", $user_id, $category_name, $type, $icon, $color);
    $ins->execute();
    
    return $conn->insert_id; // Kembalikan ID yang baru saja dibuat
}


// ==========================================
// 3. ROUTING AKSI
// ==========================================
$action = $_GET['action'] ?? '';

// ------------------------------------------
// A. BUAT HUTANG / PIUTANG BARU
// ------------------------------------------
if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? ''; 
    $principal = floatval($_POST['principal'] ?? 0); // Pokok pinjaman
    $interest = floatval($_POST['interest'] ?? 0);   // Bunga
    $wallet_id = intval($_POST['wallet_id'] ?? 0);
    $due_date = $_POST['due_date'] ?? '';

    $total = $principal + $interest; // Total tanggungan

    if (empty($name) || empty($type) || $principal <= 0 || $wallet_id <= 0 || empty($due_date)) {
        echo json_encode(["status" => "error", "message" => "Mohon isi nominal pokok dan data lainnya!"]);
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. Simpan ke tabel debts
        $stmt = $conn->prepare("INSERT INTO debts (user_id, wallet_id, name, type, principal_amount, interest_amount, total_amount, remaining_amount, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissdddds", $user_id, $wallet_id, $name, $type, $principal, $interest, $total, $total, $due_date);
        $stmt->execute();

        // AMBIL ID HUTANG YANG BARU SAJA DIBUAT
        $last_debt_id = $conn->insert_id; 

        // 2. Transaksi dompet (Masukkan ID hutang ke kolom debt_id)
        $trans_type = ($type === 'payable') ? 'income' : 'expense';
        $note = ($type === 'payable') ? "Pencairan pinjaman/Paylater dari: $name" : "Memberi pinjaman ke: $name";
        $category_id = getDebtCategoryId($conn, $user_id); 

        // PERHATIKAN: Sekarang ada kolom debt_id dan variabel $last_debt_id
        $stmt2 = $conn->prepare("INSERT INTO transactions (user_id, wallet_id, category_id, debt_id, amount, type, note, date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt2->bind_param("iiiidss", $user_id, $wallet_id, $category_id, $last_debt_id, $principal, $trans_type, $note);
        $stmt2->execute();

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Catatan berhasil dibuat!"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Gagal memproses data: " . $e->getMessage()]);
    }
}

// ------------------------------------------
// B. BAYAR CICILAN / LUNASI
// ------------------------------------------
elseif ($action === 'pay') {
    $debt_id = intval($_POST['debt_id'] ?? 0);
    $pay_amount = floatval($_POST['amount'] ?? 0);
    $wallet_id = intval($_POST['wallet_id'] ?? 0);

    // Validasi input
    if ($debt_id <= 0 || $pay_amount <= 0 || $wallet_id <= 0) {
        echo json_encode(["status" => "error", "message" => "Input tidak valid!"]);
        exit;
    }

    // Ambil data hutang saat ini
    $stmt = $conn->prepare("SELECT type, remaining_amount, name FROM debts WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $debt_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) { 
        echo json_encode(["status" => "error", "message" => "Hutang tidak ditemukan."]); 
        exit; 
    }
    
    $debt = $res->fetch_assoc();

    // Cegah bayar lebih dari sisa
    if ($pay_amount > $debt['remaining_amount']) {
        echo json_encode(["status" => "error", "message" => "Nominal bayar melebihi sisa tagihan!"]); 
        exit;
    }

    $new_remaining = $debt['remaining_amount'] - $pay_amount;
    $status = ($new_remaining <= 0) ? 'paid' : 'active';

    $conn->begin_transaction();
    try {
        // 1. Kurangi sisa hutang dan perbarui status
        $stmt_upd = $conn->prepare("UPDATE debts SET remaining_amount = ?, status = ? WHERE id = ?");
        $stmt_upd->bind_param("dsi", $new_remaining, $status, $debt_id);
        $stmt_upd->execute();

        // 2. Siapkan data untuk transaksi pemotongan/penambahan dompet
        $trans_type = ($debt['type'] === 'payable') ? 'expense' : 'income';
        $note = ($debt['type'] === 'payable') ? "Bayar cicilan ke: " . $debt['name'] : "Terima cicilan dari: " . $debt['name'];
        $category_id = getDebtCategoryId($conn, $user_id); // Panggil Helper

        // 3. Eksekusi transaksi dompet
            // Gunakan $debt_id yang sedang diproses
        $stmt_trans = $conn->prepare("INSERT INTO transactions (user_id, wallet_id, category_id, debt_id, amount, type, note, date) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt_trans->bind_param("iiiidss", $user_id, $wallet_id, $category_id, $debt_id, $pay_amount, $trans_type, $note);
        $stmt_trans->execute();

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Cicilan berhasil dibayar! Saldo dompet otomatis terpotong."]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Gagal memproses pembayaran: " . $e->getMessage()]);
    }
} 

// ------------------------------------------
// C. HAPUS DATA HUTANG & SEMUA RIWAYATNYA
// ------------------------------------------
elseif ($action === 'delete') {
    $debt_id = intval($_POST['debt_id'] ?? 0);

    if ($debt_id <= 0) {
        echo json_encode(["status" => "error", "message" => "ID tidak valid!"]);
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. Hapus SEMUA riwayat transaksi (Pokok & Cicilan) yang berhubungan dengan debt_id ini
        // Saldo dompet akan otomatis menyesuaikan karena record transaksinya hilang
        $stmt_trans = $conn->prepare("DELETE FROM transactions WHERE debt_id = ? AND user_id = ?");
        $stmt_trans->bind_param("ii", $debt_id, $user_id);
        $stmt_trans->execute();

        // 2. Hapus data utama di tabel debts
        $stmt_debt = $conn->prepare("DELETE FROM debts WHERE id = ? AND user_id = ?");
        $stmt_debt->bind_param("ii", $debt_id, $user_id);
        $stmt_debt->execute();

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Data pinjaman dan semua riwayatnya telah dihapus!"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Gagal menghapus: " . $e->getMessage()]);
    }
}
else {
    echo json_encode(["status" => "error", "message" => "Aksi tidak dikenali."]);
}
?>