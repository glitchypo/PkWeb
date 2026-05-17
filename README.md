# 📖 Mini Guestbook — Demo Keamanan Web (Native PHP)

Aplikasi buku tamu mini yang dibangun **murni dengan PHP native** untuk
mendemonstrasikan **6 aspek keamanan web** dalam satu studi kasus.

---

## 🛡️ 6 Aspek Keamanan yang Diterapkan

| # | Aspek | Lokasi di Kode | Teknik |
|---|---|---|---|
| 1 | **Input Validation & XSS** | `index.php` (output pesan), `post.php` (validasi panjang & whitelist regex username) | `htmlspecialchars()` + `preg_match()` whitelist + security headers (CSP, X-Frame-Options, X-Content-Type-Options) |
| 2 | **SQL Injection** | semua query di `index.php` & `post.php` | PDO prepared statement (`?` placeholder) + `ATTR_EMULATE_PREPARES=false` |
| 3 | **Password Security** | register & login di `index.php` | `password_hash(PASSWORD_BCRYPT)` & `password_verify()` + **password complexity** (huruf besar+kecil+angka) + **rate-limit login** (5 percobaan → lock 60 detik) |
| 4 | **Session Management** | `config.php` + `logout.php` | `session_regenerate_id(true)` saat login, cookie `HttpOnly` + `SameSite=Strict`, idle timeout 15 menit, CSRF token, **logout via POST + CSRF** |
| 5 | **File Upload** | `post.php` | Validasi MIME via `finfo` + whitelist ekstensi + ukuran max 2MB + rename dengan `bin2hex(random_bytes())` |
| 6 | **Permission Server** | `uploads/.htaccess` + `chmod 0644` | Block eksekusi PHP di folder upload + Options `-Indexes -ExecCGI` |

---

## 📁 Struktur Project

```
PkWeb/
├── config.php          # Koneksi DB + session + helper keamanan
├── index.php           # Halaman utama: login + register + daftar pesan
├── post.php            # Form tulis pesan + upload foto
├── logout.php          # Hapus session + cookie
├── style.css           # Styling tambahan
├── database.sql        # Schema MariaDB + akun demo
├── uploads/
│   └── .htaccess       # Cegah eksekusi PHP di folder upload
└── README.md
```

Hanya **4 file PHP utama**, total ~300 baris kode — cocok untuk dijelaskan dalam video.

---

## ⚙️ Cara Menjalankan (XAMPP / Laragon)

### 1. Letakkan Folder
- **XAMPP**: Salin folder `PkWeb` ke `C:\xampp\htdocs\PkWeb`
- **Laragon**: Salin ke `C:\laragon\www\PkWeb`

### 2. Import Database
- Jalankan Apache + MySQL di XAMPP/Laragon
- Buka `http://localhost/phpmyadmin`
- Klik tab **SQL** → paste isi `database.sql` → **Go**

### 3. Akses Aplikasi
Buka di browser:
```
http://localhost/PkWeb/
```

### 4. Akun Demo
| Username | Password |
|---|---|
| `admin` | `Password123` |
| `budi`  | `Password123` |
| `siti`  | `Password123` |

> Atau klik **Daftar** untuk membuat akun baru.

---

## 🎬 Skenario Demo untuk Video

### 🔴 1. Demo SQL Injection (gagal — aman)
- Buka halaman login
- Username: `' OR '1'='1`
- Password: `' OR '1'='1`
- Submit → akan muncul **"Username atau password salah"**

> **Penjelasan**: PDO prepared statement memperlakukan input sebagai string biasa, bukan kode SQL.

---

### 🔴 2. Demo XSS (gagal — aman)
- Login → klik **Tulis Pesan**
- Pesan: `<script>alert('hacked')</script><img src=x onerror=alert(1)>`
- Kirim → kembali ke halaman utama
- Pesan tampil **sebagai teks biasa**, tag tidak dieksekusi browser

> **Penjelasan**: `htmlspecialchars()` mengubah `<` jadi `&lt;`, jadi browser tidak menafsirkannya sebagai HTML.

---

### 🔴 3. Demo File Upload Berbahaya (ditolak)
Buat file `shell.php` di komputer Anda:
```php
<?php system($_GET['c']); ?>
```
- Login → **Tulis Pesan** → coba upload `shell.php`
- Akan muncul: **"Hanya JPG / PNG / WEBP yang diizinkan. Terdeteksi: text/x-php"**

> **Penjelasan**: `finfo` membaca **isi asli file**, bukan ekstensi atau header dari user.

---

### 🔴 4. Demo Permission Server (.htaccess)
Anggap penyerang berhasil menaruh `shell.php` di folder uploads (lewat cara lain).
- Akses: `http://localhost/PkWeb/uploads/shell.php`
- Apache merespons **403 Forbidden**

> **Penjelasan**: `<FilesMatch "\.php$">Require all denied</FilesMatch>` memblokir eksekusi.

---

### 🔴 5. Demo Password Hash
- Buka phpMyAdmin → tabel `users`
- Lihat kolom `password_hash` → bentuknya `$2y$12$ePt5M7V8...` (bcrypt)
- **Tidak ada satupun password tersimpan plaintext**

> **Penjelasan**: `password_hash()` otomatis pakai bcrypt + salt unik tiap user.

---

### 🔴 6. Demo Session Aman
Buka **DevTools (F12) → Application → Cookies**
- Cookie `PHPSESSID` punya flag `HttpOnly` ✅ (JS tidak bisa membaca)
- Flag `SameSite=Strict` ✅ (cegah CSRF)
- Saat login, ID session berubah (anti session-fixation)
- Diam 15 menit → otomatis logout

---

## 🔑 Ringkasan Alur Kode untuk Penjelasan Video

### `config.php` (file paling penting — wajib dijelaskan dulu)
1. Koneksi PDO MySQL
2. `ATTR_EMULATE_PREPARES = false` → real prepared statement
3. `session_set_cookie_params` dengan HttpOnly + SameSite
4. CSRF token auto-generate
5. Helper: `e()`, `check_csrf()`, `is_login()`, `require_login()`

### `index.php`
1. Form register → `password_hash` + insert via PDO
2. Form login → `password_verify` + `session_regenerate_id`
3. Output pesan → `e()` (htmlspecialchars) cegah XSS

### `post.php`
1. `require_login()` → cek session
2. `check_csrf()` → validasi token
3. Validasi panjang pesan
4. Upload file: cek `error`, `size`, MIME via `finfo`, ekstensi whitelist
5. Generate nama random + `move_uploaded_file` + `chmod 0644`

### `uploads/.htaccess`
1. Block eksekusi PHP/script
2. Block directory listing

---

## 📝 Catatan untuk Video

Urutan rekomendasi penjelasan (durasi ±15 menit):
1. **(1 menit)** Tour aplikasi: register → login → kirim pesan → tampil
2. **(2 menit)** Buka `config.php` — jelaskan PDO + session
3. **(2 menit)** Demo SQL Injection di login
4. **(2 menit)** Demo XSS di pesan
5. **(2 menit)** Buka phpMyAdmin → tunjukkan password ter-hash
6. **(3 menit)** Demo upload `shell.php` → ditolak. Jelaskan `finfo`
7. **(2 menit)** Akses langsung `uploads/shell.php` → 403. Jelaskan `.htaccess`
8. **(1 menit)** DevTools → tunjukkan cookie HttpOnly. Jelaskan session

---

### 🔴 7. Demo Rate-Limit Login (brute-force diblok)
- Logout dulu kalau masih login
- Coba login dengan password salah 5x berturut-turut
- Pada percobaan ke-5: muncul **"Terlalu banyak percobaan login gagal. Coba lagi dalam 60 detik."**
- Tunggu 60 detik (atau buka tab incognito) → bisa login lagi

> **Penjelasan**: counter `$_SESSION['login_attempts']` + `$_SESSION['login_lock_until']` di `index.php` melindungi dari brute-force.

---

### 🔴 8. Demo Password Complexity (register ditolak)
- Klik **Daftar**
- Username: `testweak`, Password: `passwordlemah` (tanpa huruf besar/angka)
- Submit → **"Password minimal 8 karakter dan harus mengandung huruf besar, huruf kecil, dan angka."**
- Ulang dengan password `Password123` → berhasil

> **Penjelasan**: regex `(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}` di `index.php` memaksa kombinasi.

---

### 🔴 9. Demo Logout POST + CSRF (forced-logout diblok)
- Login dulu
- Akses langsung `http://localhost/PkWeb/logout.php` di address bar → muncul **"405 Method Not Allowed"**
- Logout hanya bisa via tombol di nav (POST + CSRF token)

> **Penjelasan**: kalau `<img src="logout.php">` ditaruh di situs lain (CSRF), tidak akan jalan karena method bukan POST.

---

## ⚠️ Catatan Keamanan Tambahan (untuk produksi)

Aplikasi ini sudah aman untuk **demo edukasi** dan sudah punya:
- ✅ HTTPS-ready (tinggal set `secure: true` di session cookie)
- ✅ Rate limiting login
- ✅ Content Security Policy + security headers
- ✅ Password complexity

Untuk produksi nyata, tambahkan:
- Logging percobaan login gagal ke file/DB (bukan cuma session)
- Two-factor authentication (2FA)
- Backup berkala
- Re-encode gambar saat upload (anti image-polyglot)
- Subresource Integrity (SRI) untuk Tailwind CDN
