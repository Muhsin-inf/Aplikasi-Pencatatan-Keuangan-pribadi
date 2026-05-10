<?php
// folder penyimpanan
$uploadDir = "uploads/";

// buat folder jika belum ada
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$message = "";

// fungsi resize + kompres + convert ke webp
function compressImage($source, $destination, $size = 300, $quality = 70)
{
    $info = getimagesize($source);

    if (!$info) {
        return false;
    }

    $mime = $info['mime'];

    switch ($mime) {

        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;

        case 'image/png':
            $image = imagecreatefrompng($source);
            break;

        case 'image/webp':
            $image = imagecreatefromwebp($source);
            break;

        default:
            return false;
    }

    // ukuran asli
    $width = imagesx($image);
    $height = imagesy($image);

    // ambil sisi terkecil untuk crop tengah
    $minSize = min($width, $height);

    // posisi crop tengah
    $srcX = ($width - $minSize) / 2;
    $srcY = ($height - $minSize) / 2;

    // canvas baru 1:1
    $newImage = imagecreatetruecolor($size, $size);

    // support transparansi PNG
    imagealphablending($newImage, false);
    imagesavealpha($newImage, true);

    // crop + resize
    imagecopyresampled(
        $newImage,
        $image,
        0,
        0,
        $srcX,
        $srcY,
        $size,
        $size,
        $minSize,
        $minSize
    );

    // simpan webp
    imagewebp($newImage, $destination, $quality);

    // bersihkan memory
    imagedestroy($image);
    imagedestroy($newImage);

    return true;
}

// proses upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {

        $tmpName = $_FILES['foto']['tmp_name'];

        // validasi mime
        $allowed = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        $info = getimagesize($tmpName);
        $mime = $info['mime'];

        if (!in_array($mime, $allowed)) {
            $message = "Format gambar tidak didukung!";
        } else {

            // nama random
            $fileName = uniqid() . ".webp";

            $destination = $uploadDir . $fileName;

            // kompres
            if (compressImage($tmpName, $destination)) {

                $originalSize = round($_FILES['foto']['size'] / 1024, 2);
                $newSize = round(filesize($destination) / 1024, 2);

                $message = "
                    <div class='success'>
                        <p><b>Upload berhasil!</b></p>
                        <p>Ukuran asli: {$originalSize} KB</p>
                        <p>Ukuran setelah kompres: {$newSize} KB</p>
                        <img src='$destination'>
                    </div>
                ";
            } else {
                $message = "Gagal memproses gambar!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Upload & Kompres Gambar</title>

<style>
body{
    font-family: Arial;
    background:#f4f4f4;
    padding:40px;
}

.container{
    max-width:500px;
    margin:auto;
    background:white;
    padding:25px;
    border-radius:10px;
    box-shadow:0 0 10px rgba(0,0,0,0.1);
}

input[type=file]{
    width:100%;
    margin:15px 0;
}

button{
    background:#2563eb;
    color:white;
    border:none;
    padding:10px 20px;
    border-radius:5px;
    cursor:pointer;
}

button:hover{
    background:#1d4ed8;
}

.success{
    margin-top:20px;
    background:#ecfdf5;
    padding:15px;
    border-radius:8px;
}

img{
    margin-top:15px;
    width:150px;
    border-radius:10px;
}
</style>
</head>
<body>

<div class="container">

    <h2>Upload & Kompres Foto Profil</h2>

    <form method="POST" enctype="multipart/form-data">

        <input type="file" name="foto" accept="image/*" required>

        <button type="submit">Upload</button>

    </form>

    <?= $message ?>

</div>

</body>
</html>