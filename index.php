<?php
require __DIR__ . '/config.php';

$error = '';
$success = '';

// ============== HANDLE REGISTER ==============
if (($_POST['action'] ?? '') === 'register') {
    check_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi input (whitelist: huruf/angka/underscore, 3-20 char)
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $error = 'Username 3-20 karakter, huruf/angka/underscore saja.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } else {
        // Cek duplikat (PDO prepared statement -> aman SQL Injection)
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Username sudah dipakai.';
        } else {
            // password_hash -> bcrypt, otomatis pakai salt unik
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)');
            $stmt->execute([$username, $hash]);
            $success = 'Registrasi berhasil! Silakan login.';
        }
    }
}

// ============== HANDLE LOGIN ==============
if (($_POST['action'] ?? '') === 'login') {
    check_csrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // PDO prepared statement -> aman dari SQL Injection
    // Coba: ' OR '1'='1  --> tetap gagal karena diperlakukan sebagai string biasa
    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Cegah Session Fixation
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Username atau password salah.';
    }
}

// ============== AMBIL DAFTAR PESAN ==============
$messages = $pdo->query(
    'SELECT m.id, m.message, m.photo, m.created_at, u.username
     FROM messages m JOIN users u ON u.id = m.user_id
     ORDER BY m.id DESC LIMIT 50'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Mini Guestbook</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="style.css">
</head>
<body class="bg-slate-100 min-h-screen">

<nav class="bg-indigo-600 text-white px-6 py-4 shadow flex justify-between items-center">
    <h1 class="text-xl font-bold">📖 Mini Guestbook</h1>
    <div class="text-sm">
    <?php if (is_login()): ?>
        Halo, <strong><?= e($_SESSION['username']) ?></strong> ·
        <a href="post.php" class="underline">Tulis Pesan</a> ·
        <a href="logout.php" class="underline">Logout</a>
    <?php else: ?>
        <span class="opacity-80">Belum login</span>
    <?php endif; ?>
    </div>
</nav>

<div class="max-w-5xl mx-auto p-6 grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- Kolom kiri: form login/register -->
    <aside class="md:col-span-1">
        <?php if (!is_login()): ?>
            <div class="bg-white rounded-xl shadow p-5 mb-4">
                <h2 class="font-semibold text-lg mb-3">🔐 Login</h2>
                <?php if ($error): ?>
                    <div class="bg-red-100 text-red-700 px-3 py-2 rounded text-sm mb-3"><?= e($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="bg-green-100 text-green-700 px-3 py-2 rounded text-sm mb-3"><?= e($success) ?></div>
                <?php endif; ?>
                <form method="post" class="space-y-2">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <input name="username" placeholder="Username" required
                        class="w-full border rounded px-3 py-2">
                    <input type="password" name="password" placeholder="Password" required
                        class="w-full border rounded px-3 py-2">
                    <button class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700">
                        Masuk
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="font-semibold text-lg mb-3">📝 Daftar Akun</h2>
                <form method="post" class="space-y-2">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                    <input name="username" placeholder="Username (3-20)" required
                        class="w-full border rounded px-3 py-2">
                    <input type="password" name="password" placeholder="Password (min 8)" required
                        class="w-full border rounded px-3 py-2">
                    <button class="w-full bg-emerald-600 text-white py-2 rounded hover:bg-emerald-700">
                        Daftar
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="font-semibold text-lg mb-2">👋 Selamat datang!</h2>
                <p class="text-sm text-slate-600 mb-3">
                    Anda login sebagai <strong><?= e($_SESSION['username']) ?></strong>.
                </p>
                <a href="post.php"
                   class="block text-center bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700">
                    + Tulis Pesan Baru
                </a>
            </div>
        <?php endif; ?>
    </aside>

    <!-- Kolom kanan: daftar pesan -->
    <main class="md:col-span-2 space-y-4">
        <h2 class="text-2xl font-bold text-slate-800">💬 Pesan Tamu</h2>

        <?php if (empty($messages)): ?>
            <div class="bg-white p-6 rounded-xl shadow text-center text-slate-500">
                Belum ada pesan. Jadilah yang pertama!
            </div>
        <?php endif; ?>

        <?php foreach ($messages as $m): ?>
            <article class="bg-white rounded-xl shadow p-5">
                <header class="flex justify-between items-center mb-2">
                    <strong class="text-indigo-700"><?= e($m['username']) ?></strong>
                    <span class="text-xs text-slate-500"><?= e($m['created_at']) ?></span>
                </header>
                <!-- e() = htmlspecialchars -> cegah XSS -->
                <p class="text-slate-700 whitespace-pre-wrap"><?= e($m['message']) ?></p>
                <?php if ($m['photo']): ?>
                    <img src="uploads/<?= e($m['photo']) ?>"
                         alt="foto" class="mt-3 rounded-lg max-h-64 border">
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </main>
</div>

</body>
</html>
