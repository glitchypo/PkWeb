<?php
require __DIR__ . '/config.php';

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
<body class="bg-slate-50 min-h-screen">

<!-- Navbar -->
<nav class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex justify-between items-center">
        <a href="index.php" class="flex items-center gap-2">
            <span class="text-2xl">📖</span>
            <span class="text-xl font-bold text-slate-800">Mini Guestbook</span>
        </a>
        <div class="flex items-center gap-3">
        <?php if (is_login()): ?>
            <span class="hidden sm:inline text-sm text-slate-600">
                Halo, <strong class="text-indigo-600"><?= e($_SESSION['username']) ?></strong>
            </span>
            <a href="post.php"
               class="inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow transition-all hover:shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tulis Pesan
            </a>
            <form method="post" action="logout.php" class="inline">
                <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                <button type="submit"
                   class="inline-flex items-center gap-1 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Logout
                </button>
            </form>
        <?php else: ?>
            <a href="login.php"
               class="inline-flex items-center gap-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow transition-all hover:shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Login
            </a>
            <a href="register.php"
               class="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow transition-all hover:shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Daftar
            </a>
        <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 text-white py-12 sm:py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 text-center">
        <h1 class="text-3xl sm:text-4xl font-extrabold mb-3">Buku Tamu Digital</h1>
        <p class="text-lg text-white/80 max-w-2xl mx-auto">
            Tinggalkan pesan, berbagi cerita, dan sapa sesama pengunjung.
            Aplikasi ini menerapkan <strong class="text-white">6 aspek keamanan web</strong>.
        </p>
        <?php if (!is_login()): ?>
            <div class="mt-6 flex justify-center gap-3">
                <a href="register.php" class="bg-white text-indigo-700 font-semibold px-6 py-2.5 rounded-lg shadow-lg hover:shadow-xl transition-all hover:-translate-y-0.5">
                    Mulai Menulis
                </a>
            </div>
        <?php endif; ?>
    </div>
</header>

<!-- Daftar Pesan -->
<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <span class="text-2xl">💬</span> Pesan Tamu
            <span class="text-sm font-normal text-slate-500">(<?= count($messages) ?> pesan)</span>
        </h2>
    </div>

    <?php if (empty($messages)): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-12 text-center">
            <div class="text-5xl mb-4">📝</div>
            <h3 class="text-lg font-semibold text-slate-700 mb-2">Belum Ada Pesan</h3>
            <p class="text-slate-500">Jadilah yang pertama menulis di buku tamu ini!</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
        <?php foreach ($messages as $m): ?>
            <article class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-start gap-4">
                    <!-- Avatar -->
                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-indigo-400 to-purple-500 rounded-full flex items-center justify-center text-white font-bold text-sm uppercase">
                        <?= e(substr($m['username'], 0, 2)) ?>
                    </div>
                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-semibold text-slate-800"><?= e($m['username']) ?></span>
                            <span class="text-xs text-slate-400">&bull;</span>
                            <time class="text-xs text-slate-400"><?= e($m['created_at']) ?></time>
                        </div>
                        <!-- e() = htmlspecialchars -> cegah XSS -->
                        <p class="text-slate-700 whitespace-pre-wrap leading-relaxed"><?= e($m['message']) ?></p>
                        <?php if ($m['photo']): ?>
                            <div class="mt-3">
                                <img src="uploads/<?= e($m['photo']) ?>"
                                     alt="foto lampiran"
                                     class="rounded-xl max-h-72 border border-slate-200 shadow-sm">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<!-- Footer -->
<footer class="border-t border-slate-200 bg-white mt-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-6 text-center text-sm text-slate-500">
        <p>Mini Guestbook &mdash; Demo <strong>6 Aspek Keamanan Web</strong> dengan Native PHP</p>
        <p class="mt-1 text-xs text-slate-400">XSS &bull; SQL Injection &bull; Password Hash &bull; Session &bull; File Upload &bull; Permission</p>
    </div>
</footer>

</body>
</html>
