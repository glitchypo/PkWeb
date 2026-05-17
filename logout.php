<?php
require __DIR__ . '/config.php';

// Logout harus via POST + CSRF token -> cegah forced logout via <img src=...>
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    die('Method Not Allowed. Logout harus via POST.');
}
check_csrf();

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session di browser (sertakan SameSite supaya konsisten)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Strict',
    ]);
}

session_destroy();
header('Location: index.php');
exit;
