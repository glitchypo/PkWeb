<?php
require __DIR__ . '/config.php';

// Kalau sudah login, langsung ke dashboard
if (is_login()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    // ============ RATE-LIMIT LOGIN (per-session) ============
    // 5 percobaan gagal -> kunci 60 detik (cegah brute-force)
    if (!isset($_SESSION['login_attempts']))   $_SESSION['login_attempts']   = 0;
    if (!isset($_SESSION['login_lock_until'])) $_SESSION['login_lock_until'] = 0;

    if (time() < $_SESSION['login_lock_until']) {
        $wait = $_SESSION['login_lock_until'] - time();
        $error = "Terlalu banyak percobaan gagal. Coba lagi dalam $wait detik.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // PDO prepared statement -> aman dari SQL Injection
        // Demo: coba input ' OR '1'='1  --> tetap gagal
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Cegah Session Fixation
            session_regenerate_id(true);
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['username']       = $user['username'];
            $_SESSION['login_attempts'] = 0; // reset counter
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= 5) {
                $_SESSION['login_lock_until'] = time() + 60;
                $_SESSION['login_attempts']   = 0;
                $error = 'Terlalu banyak percobaan gagal. Coba lagi dalam 60 detik.';
            } else {
                $sisa  = 5 - $_SESSION['login_attempts'];
                $error = "Username atau password salah. (sisa percobaan: $sisa)";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login — Mini Guestbook</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="style.css">
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500">

<div class="w-full max-w-md mx-4">
    <!-- Card -->
    <div class="bg-white/95 backdrop-blur rounded-2xl shadow-2xl p-8">
        <!-- Logo / Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">
                <span class="text-3xl">📖</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Mini Guestbook</h1>
            <p class="text-gray-500 text-sm mt-1">Masuk ke akun Anda</p>
        </div>

        <!-- Error Message -->
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Form Login -->
        <form method="post" class="space-y-5">
            <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </span>
                    <input type="text" name="username" required autofocus
                        class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                        placeholder="Masukkan username">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </span>
                    <input type="password" name="password" required
                        class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                        placeholder="Masukkan password">
                </div>
            </div>

            <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5">
                Masuk
            </button>
        </form>

        <!-- Link ke Register -->
        <div class="mt-6 text-center">
            <p class="text-gray-500 text-sm">
                Belum punya akun?
                <a href="register.php" class="text-indigo-600 hover:text-indigo-800 font-medium transition">Daftar di sini</a>
            </p>
        </div>
    </div>

    <!-- Footer -->
    <p class="text-center text-white/70 text-xs mt-6">
        Demo Keamanan Web &mdash; Native PHP
    </p>
</div>

</body>
</html>
