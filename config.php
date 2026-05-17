<?php
/**
 * config.php — Konfigurasi DB + Session + Helper Keamanan
 *
 * Default: MariaDB / MySQL (XAMPP / Laragon)
 * Untuk testing tanpa MariaDB, set env DB_DRIVER=sqlite
 */

// ============== KONFIGURASI DATABASE ==============
$DB_DRIVER = getenv('DB_DRIVER') ?: 'mysql';   // 'mysql' atau 'sqlite'
$DB_HOST   = 'localhost';
$DB_NAME   = 'mini_guestbook';
$DB_USER   = 'root';
$DB_PASS   = '';

try {
    if ($DB_DRIVER === 'sqlite') {
        $sqlitePath = __DIR__ . '/guestbook.sqlite';
        $pdo = new PDO('sqlite:' . $sqlitePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Inisialisasi schema kalau belum ada (khusus sqlite testing)
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP)");
        $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            photo TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id))");
    } else {
        $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
        $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,   // PENTING: cegah SQL Injection
        ]);
    }
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// ============== SESSION AMAN ==============
// 1) Cookie HttpOnly + SameSite -> cegah pencurian session via JS / CSRF
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Strict',
    'secure'   => false, // ubah ke true kalau pakai HTTPS
]);
session_start();

// 2) Auto-logout setelah 15 menit idle
$IDLE_TIMEOUT = 15 * 60;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $IDLE_TIMEOUT) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['last_activity'] = time();

// 3) CSRF Token
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// ============== HELPER KEAMANAN ==============

/** Cegah XSS: escape output ke HTML */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Validasi CSRF token dari form */
function check_csrf(): void {
    if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(419);
        die('CSRF token tidak valid. Refresh halaman dan coba lagi.');
    }
}

/** Cek apakah user sudah login */
function is_login(): bool {
    return !empty($_SESSION['user_id']);
}

/** Wajib login, kalau tidak redirect ke login */
function require_login(): void {
    if (!is_login()) {
        header('Location: login.php');
        exit;
    }
}
