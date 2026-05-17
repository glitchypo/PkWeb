<?php
require __DIR__ . '/config.php';
require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $message = trim($_POST['message'] ?? '');
    $photoName = null;

    // ============ VALIDASI INPUT TEKS ============
    if ($message === '') {
        $error = 'Pesan tidak boleh kosong.';
    } elseif (mb_strlen($message) > 1000) {
        $error = 'Pesan maksimal 1000 karakter.';
    }

    // ============ VALIDASI FILE UPLOAD ============
    if (!$error && !empty($_FILES['photo']['name'])) {
        $f = $_FILES['photo'];

        // 1) Cek error upload
        if ($f['error'] !== UPLOAD_ERR_OK) {
            $error = 'Upload gagal (kode: ' . $f['error'] . ').';
        }
        // 2) Cek ukuran maksimal 2 MB
        elseif ($f['size'] > 2 * 1024 * 1024) {
            $error = 'Ukuran file melebihi 2 MB.';
        } else {
            // 3) Cek MIME type ASLI dari isi file (bukan dari header user)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($f['tmp_name']);
            $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

            if (!isset($allowedMime[$mime])) {
                $error = 'Hanya JPG / PNG / WEBP yang diizinkan. Terdeteksi: ' . $mime;
            } else {
                // 4) Whitelist ekstensi (double-check)
                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    $error = 'Ekstensi tidak diizinkan.';
                } else {
                    // 5) Generate nama file random -> cegah overwrite & path traversal
                    $photoName = bin2hex(random_bytes(16)) . '.' . $allowedMime[$mime];
                    $dest = __DIR__ . '/uploads/' . $photoName;

                    if (!is_dir(__DIR__ . '/uploads')) {
                        mkdir(__DIR__ . '/uploads', 0755, true);
                    }
                    if (!move_uploaded_file($f['tmp_name'], $dest)) {
                        $error = 'Gagal menyimpan file.';
                        $photoName = null;
                    } else {
                        // 6) Set permission file -> tidak boleh executable
                        chmod($dest, 0644);
                    }
                }
            }
        }
    }

    // ============ SIMPAN KE DB (PDO prepared -> aman SQLi) ============
    if (!$error) {
        $stmt = $pdo->prepare(
            'INSERT INTO messages (user_id, message, photo) VALUES (?, ?, ?)'
        );
        $stmt->execute([$_SESSION['user_id'], $message, $photoName]);
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Tulis Pesan — Mini Guestbook</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-100 min-h-screen">

<nav class="bg-indigo-600 text-white px-6 py-4 shadow flex justify-between items-center">
    <h1 class="text-xl font-bold">📖 Mini Guestbook</h1>
    <a href="index.php" class="underline text-sm">← Kembali</a>
</nav>

<div class="max-w-2xl mx-auto p-6">
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-bold mb-4">✍️ Tulis Pesan</h2>

        <?php if ($error): ?>
            <div class="bg-red-100 text-red-700 px-3 py-2 rounded text-sm mb-3"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

            <div>
                <label class="block text-sm font-medium mb-1">Pesan</label>
                <textarea name="message" rows="4" maxlength="1000" required
                    class="w-full border rounded px-3 py-2"
                    placeholder="Tulis pesan Anda di sini..."></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Foto (opsional, max 2 MB, JPG/PNG/WEBP)</label>
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                    class="w-full border rounded px-3 py-2 bg-white">
            </div>

            <button class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700">
                Kirim Pesan
            </button>
        </form>
    </div>
</div>

</body>
</html>
