<?php
header('Content-Type: application/json');
require_once 'config.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Sesi telah habis. Silakan login ulang."]);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// --- FUNGSI KOMPRES GAMBAR (GD LIBRARY) ---
function compressImageProfile($source, $destination, $size = 300, $quality = 70) {
    $info = getimagesize($source);
    if (!$info) return false;

    $mime = $info['mime'];
    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($source); break;
        case 'image/png': $image = imagecreatefrompng($source); break;
        case 'image/webp': $image = imagecreatefromwebp($source); break;
        default: return false;
    }

    $width = imagesx($image);
    $height = imagesy($image);
    $minSize = min($width, $height);
    $srcX = ($width - $minSize) / 2;
    $srcY = ($height - $minSize) / 2;

    $newImage = imagecreatetruecolor($size, $size);
    imagealphablending($newImage, false);
    imagesavealpha($newImage, true);
    
    // Potong tengah (Crop 1:1) dan Resize
    imagecopyresampled($newImage, $image, 0, 0, $srcX, $srcY, $size, $size, $minSize, $minSize);
    
    // Simpan sebagai WebP
    imagewebp($newImage, $destination, $quality);

    imagedestroy($image);
    imagedestroy($newImage);
    return true;
}

// ==========================================
// 1. UPDATE NAMA
// ==========================================
if ($action === 'update_name') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        echo json_encode(["status" => "error", "message" => "Nama tidak boleh kosong."]); exit;
    }

    $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $name, $user_id);
    if ($stmt->execute()) {
        $_SESSION['user_name'] = $name;
        echo json_encode(["status" => "success", "message" => "Nama berhasil diperbarui!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal memperbarui nama."]);
    }
}

// ==========================================
// 2. UPDATE FOTO PROFIL (Dengan Kompresi WebP)
// ==========================================
elseif ($action === 'update_photo') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["status" => "error", "message" => "Pilih file gambar terlebih dahulu."]); exit;
    }

    $file = $_FILES['photo'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];

    // Validasi Tipe File
    if (!in_array($file['type'], $allowed_types)) {
        echo json_encode(["status" => "error", "message" => "Format ditolak! Hanya JPG, PNG, atau WEBP."]); exit;
    }

    // Nama file baru (selalu .webp)
    $new_filename = "user_" . $user_id . "_" . time() . ".webp";
    
    $upload_dir = "../uploads/profiles/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $target_path = $upload_dir . $new_filename;

    // Lakukan Kompresi (Resolusi 300x300, Kualitas 80%)
    if (compressImageProfile($file['tmp_name'], $target_path, 300, 80)) {
        
        // Hapus foto lama agar server tidak penuh
        $cek_lama = $conn->query("SELECT photo_url FROM users WHERE id = $user_id")->fetch_assoc();
        if ($cek_lama['photo_url'] && file_exists("../" . $cek_lama['photo_url'])) {
            unlink("../" . $cek_lama['photo_url']); 
        }

        // Simpan nama file ke database
        $db_path = "uploads/profiles/" . $new_filename;
        $conn->query("UPDATE users SET photo_url = '$db_path' WHERE id = $user_id");

        echo json_encode([
            "status" => "success", 
            "message" => "Foto berhasil dikompres dan disimpan!", 
            "photo_url" => $db_path
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal memproses/mengkompres gambar."]);
    }
}
?>