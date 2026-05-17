-- ============================================================
-- Mini Guestbook - Schema MariaDB / MySQL
-- ============================================================
-- Cara import (XAMPP / Laragon):
--   1) Buka phpMyAdmin -> tab "SQL"
--   2) Paste isi file ini -> klik "Go"
-- Atau via terminal:
--   mysql -u root -p < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS mini_guestbook
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mini_guestbook;

DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(20)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    message    TEXT NOT NULL,
    photo      VARCHAR(100) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- AKUN DEMO  (Password semua akun = "Password123")
-- ============================================================
-- Hash di bawah ini di-generate dengan:
--   php -r "echo password_hash('Password123', PASSWORD_BCRYPT);"
-- Sudah diuji dengan password_verify() = TRUE.
-- ============================================================

INSERT INTO users (username, password_hash) VALUES
('admin', '$2y$12$ePt5M7V8awvjn0cGSC4W..io5pmKH9mLG.utAXoznl9W3FtweR7D2'),
('budi',  '$2y$12$wsgLcLAAEP9QWGLTPrmayO.FOlNQsYp7ngNACY0iZPwz0GEdwIsN6'),
('siti',  '$2y$12$WGONFkpmJuhlAAvIIoJ3Pux3ImXnTH/f0AN7XLwt67KD3blnJqWXK');

INSERT INTO messages (user_id, message, photo) VALUES
(1, 'Selamat datang di Mini Guestbook! Aplikasi ini menerapkan 6 aspek keamanan web. Silakan tinggalkan pesan Anda.', NULL),
(2, 'Halo semua, salam kenal dari Budi. Aplikasinya sederhana tapi komplit.', NULL),
(3, 'Coba kirim pesan dengan tag <script>alert(1)</script> -- harusnya muncul sebagai teks biasa, bukan dieksekusi browser.', NULL);
