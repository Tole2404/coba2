<?php
// Contoh implementasi upload file yang lebih aman
$maxFileSize   = 2 * 1024 * 1024; // 2 MB
$allowedMimes  = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
];

// Folder upload berada di luar web root (c:\xampp\secure_uploads)
$storageRoot = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'secure_uploads';

$message      = null;
$messageClass = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['foto']) || empty($_FILES['foto']['name'])) {
        $message      = 'Tidak ada file yang dipilih.';
        $messageClass = 'error';
    } else {
        $file = $_FILES['foto'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $message      = 'Upload gagal. Kode error: ' . $file['error'];
            $messageClass = 'error';
        } elseif ($file['size'] > $maxFileSize) {
            $message      = 'Ukuran file melebihi ' . ($maxFileSize / (1024 * 1024)) . ' MB.';
            $messageClass = 'error';
        } else {
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!isset($allowedMimes[$mimeType])) {
                $message      = 'Tipe file tidak diizinkan. Hanya JPG dan PNG.';
                $messageClass = 'error';
            } elseif (getimagesize($file['tmp_name']) === false) {
                $message      = 'File bukan gambar yang valid.';
                $messageClass = 'error';
            } else {
                $extension   = $allowedMimes[$mimeType];
                $subDir      = date('Y') . DIRECTORY_SEPARATOR . date('m');
                $targetDir   = $storageRoot . DIRECTORY_SEPARATOR . $subDir;

                if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
                    $message      = 'Gagal membuat folder tujuan upload.';
                    $messageClass = 'error';
                } else {
                    $newFileName = bin2hex(random_bytes(16)) . '.' . $extension;
                    $targetPath  = $targetDir . DIRECTORY_SEPARATOR . $newFileName;

                    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $message      = 'Gagal memindahkan file upload.';
                        $messageClass = 'error';
                    } else {
                        $message      = 'Upload sukses. File disimpan sebagai: ' . htmlspecialchars($newFileName);
                        $messageClass = 'success';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Demo Upload Aman</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        form { border: 1px solid #ccc; padding: 1.5rem; border-radius: 8px; max-width: 420px; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        input[type="file"] { margin-bottom: 1rem; }
        button { padding: 0.6rem 1.2rem; cursor: pointer; }
        .alert { margin-top: 1rem; padding: 0.8rem 1rem; border-radius: 4px; }
        .alert.info { background: #eef4ff; border: 1px solid #abc4ff; }
        .alert.error { background: #ffe8e8; border: 1px solid #ff9b9b; }
        .alert.success { background: #e6ffed; border: 1px solid #8ddba4; }
    </style>
</head>
<body>
    <h1>Demo Upload Aman</h1>
    <p>Contoh ini memvalidasi tipe file berdasarkan MIME dan signature, bukan hanya ekstensi.</p>
    <form method="POST" enctype="multipart/form-data">
        <label for="foto">Pilih foto (JPG/PNG, maks 2MB)</label>
        <input type="file" name="foto" id="foto" accept="image/jpeg,image/png" required>
        <button type="submit">Upload</button>
    </form>

    <?php if ($message): ?>
        <div class="alert <?php echo $messageClass; ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section style="margin-top:2rem; max-width:600px;">
        <h2>Kenapa langkah-langkah ini penting?</h2>
        <ol>
            <li><strong>Cek error & ukuran:</strong> Hindari file rusak atau serangan DoS via file besar.</li>
            <li><strong>Validasi MIME dengan finfo:</strong> Menjamin tipe file asli, bukan dari user.</li>
            <li><strong>getimagesize:</strong> Memastikan benar-benar gambar, mendeteksi file skrip terselubung.</li>
            <li><strong>Folder di luar web root:</strong> File tidak bisa diakses langsung via URL.</li>
            <li><strong>Nama acak:</strong> Mencegah penebakan nama file + bentrok nama.</li>
        </ol>
    </section>
</body>
</html>
