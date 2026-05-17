<?php
require __DIR__ . '/config.php';
require_login();

$error = '';
$success = '';

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
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tulis Pesan — Mini Guestbook</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-50 min-h-screen">

<!-- Navbar -->
<nav class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex justify-between items-center">
        <a href="index.php" class="flex items-center gap-2">
            <span class="text-2xl">📖</span>
            <span class="text-xl font-bold text-slate-800">Mini Guestbook</span>
        </a>
        <div class="flex items-center gap-3">
            <span class="hidden sm:inline text-sm text-slate-600">
                <strong class="text-indigo-600"><?= e($_SESSION['username']) ?></strong>
            </span>
            <a href="index.php"
               class="inline-flex items-center gap-1 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>
</nav>

<!-- Form Section -->
<main class="max-w-2xl mx-auto px-4 sm:px-6 py-10">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8">
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center text-white text-xl">
                ✍️
            </div>
            <div>
                <h1 class="text-xl font-bold text-slate-800">Tulis Pesan Baru</h1>
                <p class="text-sm text-slate-500">Bagikan pesan atau sapa pengunjung lain</p>
            </div>
        </div>

        <!-- Error -->
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="post" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

            <!-- Pesan -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pesan Anda</label>
                <textarea name="message" rows="5" maxlength="1000" required
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-none"
                    placeholder="Tulis sesuatu yang ingin Anda sampaikan..."><?= e($_POST['message'] ?? '') ?></textarea>
                <p class="text-xs text-slate-400 mt-1">Maks. 1000 karakter</p>
            </div>

            <!-- Upload Foto -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Lampiran Foto (opsional)</label>
                <div class="border-2 border-dashed border-slate-200 rounded-xl p-6 text-center hover:border-indigo-300 transition-colors">
                    <svg class="mx-auto w-10 h-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"
                        class="text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="text-xs text-slate-400 mt-2">JPG, PNG, atau WEBP &mdash; maks. 2 MB</p>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Kirim Pesan
            </button>
        </form>
    </div>
</main>

</body>
</html>
