# SCRIPT VIDEO PENJELASAN
## Mini Guestbook — 6 Aspek Keamanan Web (Native PHP)
### Durasi Target: 12-15 menit

---

## [00:00 - 01:30] PEMBUKAAN & TOUR APLIKASI

**[Tampilkan wajah/layar recording]**

> "Assalamualaikum warahmatullahi wabarakatuh. Halo semuanya.
>
> Pada video kali ini saya akan menjelaskan tentang implementasi keamanan aplikasi web menggunakan PHP native. Studi kasus yang saya buat adalah aplikasi Mini Guestbook — buku tamu digital sederhana.
>
> Kenapa Mini Guestbook? Karena meskipun sederhana, aplikasi ini punya semua fitur yang membutuhkan keenam aspek keamanan web: ada login, ada input teks, ada upload file — jadi cocok banget untuk demo.
>
> Saya akan tunjukkan dulu tampilannya..."

**[Buka browser, tunjukkan]:**
- Halaman utama (index.php) — hero section + daftar pesan
- Halaman login (login.php) — full page dengan gradient
- Halaman register (register.php)
- Halaman tulis pesan (post.php) — form + upload area

> "Oke, jadi alurnya simpel: user daftar → login → tulis pesan → bisa lampirin foto → pesan muncul di halaman utama. Sekarang kita masuk ke kode dan keamanannya satu per satu."

---

## [01:30 - 03:30] ASPEK 1: VALIDASI INPUT & PENCEGAHAN XSS

**[Buka file config.php, tunjukkan fungsi e()]**

> "Aspek pertama: pencegahan XSS atau Cross-Site Scripting.
>
> XSS itu terjadi kalau kita menampilkan input dari user langsung ke HTML tanpa di-filter. Penyerang bisa sisipkan tag script yang nanti dieksekusi browser pengunjung lain.
>
> Di aplikasi ini, saya buat helper function namanya `e()` — singkatan dari escape:"

**[Highlight kode]:**
```php
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
```

> "Fungsi ini mengubah karakter khusus HTML jadi entitas. Misalnya tanda `<` jadi `&lt;`, tanda `>` jadi `&gt;`. Jadi browser ga menganggapnya sebagai tag HTML."

**[Buka index.php, tunjukkan penggunaan di output pesan]:**
```php
<p><?= e($m['message']) ?></p>
```

> "Setiap kali kita mau tampilkan data dari database ke HTML, selalu pakai `e()`. Tanpa kecuali."

**[DEMO: Buka halaman tulis pesan, ketik payload XSS]:**
```
<script>alert('hacked')</script><img src=x onerror=alert(1)>
```

> "Lihat, saya kirim pesan yang isinya tag script. Sekarang kita lihat di halaman utama..."
>
> "Muncul sebagai teks biasa! Tidak ada alert yang keluar. Kalau kita inspect element, terlihat sudah di-escape jadi `&lt;script&gt;`. Aman."

**[Tunjukkan juga validasi regex username di register.php]:**
```php
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) { ... }
```

> "Untuk username, saya pakai pendekatan whitelist — hanya izinkan karakter yang memang diharapkan. Ini lebih aman daripada blacklist."

---

## [03:30 - 05:30] ASPEK 2: PENCEGAHAN SQL INJECTION

**[Buka login.php, tunjukkan query]**

> "Aspek kedua: SQL Injection. Ini salah satu serangan paling klasik tapi masih banyak ditemui.
>
> SQL Injection terjadi kalau input user langsung disambung ke query SQL. Misalnya query login seperti ini:"

**[Tunjukkan contoh BURUK (tulis di notepad, jangan di kode)]:**
```php
// JANGAN BEGINI! (contoh kode yang rentan)
$sql = "SELECT * FROM users WHERE username = '$username'";
```

> "Kalau user input `' OR '1'='1`, querynya jadi selalu benar, login berhasil tanpa password."
>
> "Nah di aplikasi kita, saya pakai PDO prepared statement:"

**[Highlight kode di login.php]:**
```php
$stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = ?');
$stmt->execute([$username]);
```

> "Perhatikan: tanda tanya itu placeholder. Input user dikirim terpisah melalui `execute()`. Jadi database memperlakukan itu sebagai string murni, bukan bagian dari perintah SQL."

**[DEMO: Buka halaman login, masukkan]:**
- Username: `' OR '1'='1`
- Password: `' OR '1'='1`

> "Klik login... Muncul 'Username atau password salah.' — injection gagal. Karena prepared statement memperlakukan input itu sebagai literal string yang dicari."

**[Tunjukkan juga config koneksi PDO]:**
```php
PDO::ATTR_EMULATE_PREPARES => false
```

> "Ini juga penting: saya set emulate_prepares ke false. Artinya prepared statement benar-benar diproses oleh MySQL engine, bukan cuma di-escape oleh PHP."

---

## [05:30 - 07:30] ASPEK 3: KEAMANAN PASSWORD

**[Buka register.php, bagian hashing]**

> "Aspek ketiga: bagaimana menyimpan password dengan aman.
>
> Prinsip dasarnya: JANGAN pernah simpan password dalam bentuk asli. Kalau database bocor, semua akun langsung terekspos.
>
> Kita gunakan fungsi `password_hash()` dengan algoritma bcrypt:"

**[Highlight kode]:**
```php
$hash = password_hash($password, PASSWORD_BCRYPT);
```

> "Bcrypt itu punya beberapa keunggulan. Pertama, dia lambat secara sengaja — bikin brute-force jadi sulit. Kedua, setiap hash otomatis punya salt unik, jadi dua user dengan password sama menghasilkan hash berbeda."

**[Buka phpMyAdmin / tunjukkan isi tabel users]:**

> "Lihat kolom password_hash: isinya `$2y$12$...` — itu format bcrypt. Angka 12 itu cost factor. Tidak ada satupun password yang bisa dibaca langsung."

**[Tunjukkan verifikasi di login.php]:**
```php
if ($user && password_verify($password, $user['password_hash'])) {
    // berhasil
}
```

> "Saat login, kita pakai `password_verify()`. Fungsi ini yang tahu cara membandingkan input dengan hash."

**[Tunjukkan password complexity di register.php]:**
```php
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password)) {
    $error = '...';
}
```

> "Saya juga tambahkan validasi kompleksitas: minimal 8 karakter, harus ada huruf besar, huruf kecil, dan angka. Ini mencegah user pakai password yang terlalu lemah."

**[DEMO: Coba daftar dengan password "abcd"]:**

> "Ditolak. Sekarang coba 'Password123'... berhasil. Ini memastikan password minimum level keamanannya."

---

## [07:30 - 09:30] ASPEK 4: SESSION MANAGEMENT

**[Buka config.php, bagian session]**

> "Aspek keempat: manajemen session. Session itu cara server mengingat siapa yang sedang login.
>
> Ada beberapa hal yang saya amankan di sini:"

**[Highlight cookie params]:**
```php
session_set_cookie_params([
    'httponly'  => true,
    'samesite' => 'Strict',
]);
```

> "Pertama, cookie session di-set HttpOnly — artinya JavaScript tidak bisa baca cookie ini. Jadi meskipun ada XSS lolos, penyerang tetap tidak bisa curi session.
>
> Kedua, SameSite Strict — browser tidak kirim cookie ini pada request yang berasal dari website lain. Ini mencegah CSRF."

**[DEMO: Buka DevTools → Application → Cookies, tunjukkan flag-nya]**

**[Tunjukkan session_regenerate_id]:**
```php
session_regenerate_id(true);
```

> "Ketiga, saat login berhasil kita regenerasi session ID. Ini mencegah serangan session fixation — di mana penyerang membuat korban login menggunakan session ID yang sudah diketahui penyerang."

**[Tunjukkan CSRF token]:**
```php
$_SESSION['csrf'] = bin2hex(random_bytes(32));
```

> "Keempat, setiap form punya token CSRF unik. Token ini di-generate secara random dan divalidasi saat form di-submit."

**[Tunjukkan check_csrf() dan hidden input di form]:**

> "Kalau ada penyerang yang buat website palsu dengan form yang mengarah ke server kita, formnya pasti tidak punya token ini, jadi request ditolak."

**[Tunjukkan juga logout via POST di index.php]:**

> "Terakhir, logout sekarang pakai method POST + CSRF token — bukan link biasa. Jadi penyerang tidak bisa paksa logout user lewat tag img atau link tersembunyi."

---

## [09:30 - 11:30] ASPEK 5: FILE UPLOAD SECURITY

**[Buka post.php, bagian upload]**

> "Aspek kelima: keamanan upload file. Ini sering jadi celah fatal.
>
> Bayangkan penyerang upload file PHP berisi perintah sistem — kalau bisa dieksekusi, dia bisa kontrol seluruh server.
>
> Di aplikasi ini saya terapkan 5 lapis validasi:"

**[Jelaskan sambil scroll kode]:**

> "Lapis 1: cek ukuran — maksimal 2 MB.
>
> Lapis 2: validasi MIME type pakai `finfo`. Ini yang paling penting — dia baca header file yang sebenarnya, bukan dari apa yang dikirim user. Jadi meskipun penyerang rename `shell.php` jadi `shell.jpg`, finfo tetap mendeteksi isinya bukan gambar.
>
> Lapis 3: whitelist ekstensi — hanya jpg, png, dan webp yang diterima.
>
> Lapis 4: rename file pakai `bin2hex(random_bytes(16))` — nama asli diabaikan. Ini mencegah path traversal dan overwrite.
>
> Lapis 5: setelah tersimpan, permission file di-set 0644 — artinya file tidak bisa dieksekusi."

**[DEMO: Buat file shell.php di komputer]:**
```php
<?php system($_GET['c']); ?>
```

> "Saya coba upload shell.php ini... hasilnya: 'Hanya JPG / PNG / WEBP yang diizinkan. Terdeteksi: text/x-php.' — ditolak!"

**[Upload gambar PNG asli]:**

> "Sekarang upload foto PNG biasa... berhasil. Dan lihat namanya di folder uploads: string random panjang — nama asli hilang."

---

## [11:30 - 13:00] ASPEK 6: PERMISSION SERVER

**[Buka file uploads/.htaccess]**

> "Aspek keenam: konfigurasi permission di level server. Ini lapisan pertahanan terakhir.
>
> Prinsipnya: meskipun validasi aplikasi sudah bagus, kita tetap harus asumsikan kemungkinan ada celah yang belum diketahui. Jadi server harus dikonfigurasi agar folder upload TIDAK BISA menjalankan script apapun."

**[Highlight isi .htaccess]:**

> "Ada tiga directive utama:
> 1. `FilesMatch` yang memblokir akses ke semua file berekstensi `.php`, `.phtml`, `.py`, `.sh`, dll.
> 2. `php_flag engine off` yang mematikan parser PHP di folder ini.
> 3. `Options -Indexes -ExecCGI` yang melarang listing direktori dan eksekusi CGI."

**[DEMO: Misalkan ada file shell.php di folder uploads]:**

> "Seandainya penyerang berhasil menaruh file PHP di folder ini lewat celah lain... kita coba akses langsung di browser: `localhost/PkWeb/uploads/shell.php`"
>
> "Hasilnya? 403 Forbidden. Apache menolak mentah-mentah. File itu tidak akan pernah dieksekusi."
>
> "Inilah yang disebut defense in depth — pertahanan berlapis. Satu lapisan gagal, lapisan lain tetap jaga."

---

## [13:00 - 14:30] PENUTUP & KESIMPULAN

**[Kembali ke wajah/layar utama]**

> "Baik, jadi kita sudah bahas keenam aspek keamanan web:
>
> 1. XSS — dicegah dengan `htmlspecialchars()` di setiap output.
> 2. SQL Injection — dicegah dengan PDO prepared statement.
> 3. Password — disimpan dengan bcrypt, tidak pernah plaintext.
> 4. Session — cookie HttpOnly, SameSite, regenerate ID, CSRF token.
> 5. File Upload — validasi MIME asli, whitelist ekstensi, rename random.
> 6. Permission Server — .htaccess blokir eksekusi script di folder upload.
>
> Keenam aspek ini saling melengkapi. Tidak cukup hanya terapkan satu atau dua — semuanya harus ada untuk membentuk pertahanan yang solid.
>
> Pesan saya: keamanan bukan fitur tambahan, tapi bagian integral dari proses development. Lebih mudah menerapkan keamanan sejak awal daripada menambal setelah ada insiden.
>
> Kode sumbernya bisa dilihat di GitHub: github.com/glitchypo/PkWeb — silakan dipelajari.
>
> Terima kasih sudah menonton. Wassalamualaikum warahmatullahi wabarakatuh."

---

## CATATAN UNTUK PEREKAMAN

### Tips Recording:
1. **Screen recorder**: Gunakan OBS Studio (gratis) atau Camtasia
2. **Resolusi**: 1920x1080, font editor diperbesar agar terbaca
3. **Browser**: Buka di Chrome, zoom 125% agar UI jelas
4. **Editor**: Gunakan VS Code dengan tema terang (agar terbaca di video)
5. **Bicara natural**: Tidak perlu hafal script kata per kata — pahami poin utamanya, jelaskan dengan gaya sendiri
6. **Pace**: Jangan terlalu cepat. Tiap aspek idealnya 2 menit, sisanya pembukaan dan penutup

### Persiapan sebelum record:
- XAMPP/Laragon sudah running
- Database sudah di-import (jalankan `database.sql`)
- Sudah ada beberapa pesan di guestbook (pakai akun demo)
- Siapkan file `shell.php` untuk demo upload
- Buka phpMyAdmin (untuk tunjukkan hash password)
- Buka DevTools di Chrome (untuk tunjukkan cookie)
