# Panduan Lengkap Deployment Ruang Dharma di aaPanel (Linux)

Dokumen ini adalah panduan langkah-demi-langkah ramah pemula untuk menerapkan aplikasi **Ruang Dharma** di server VPS berbasis Linux menggunakan kontrol panel **aaPanel**.

---

## 1. Persyaratan Server Minimal
- **Sistem Operasi**: Ubuntu 22.04 LTS / Debian 12 / AlmaLinux 9 (64-bit).
- **CPU**: 2 Core (Disarankan 4 Core untuk performa render FFmpeg yang lebih cepat).
- **RAM**: 4 GB (Disarankan 8 GB jika menjalankan video rendering paralel).
- **Penyimpanan**: 40 GB SSD / NVMe.
- **Domain**: Domain aktif yang sudah diarahkan DNS A record ke IP VPS Anda.

---

## 2. Instalasi Paket Inti di aaPanel

Buka antarmuka web aaPanel Anda (`https://IP-VPS:8888`), lalu masuk ke menu **App Store**:

1. **Web Server**: Pasang **Nginx 1.24** atau versi stabil terbaru.
2. **PHP**: Pasang **PHP 8.3**.
3. **Database**: Pasang **PostgreSQL 15/16** atau **MySQL 8.0**.
4. **Cache & Queue**: Pasang **Redis 7.x**.
5. **Supervisor**: Pasang ekstensi **Supervisor Manager** dari aaPanel App Store.
6. **Node.js**: Pasang **Node.js version manager** (pilih Node v20 LTS atau v22).

---

## 3. Konfigurasi Ekstensi & Limit PHP 8.3

Buka **App Store** -> **PHP 8.3** -> **Settings**:

### A. Ekstensi yang Wajib Diaktifkan
Masuk ke tab **Install extensions**, pastikan ekstensi berikut terpasang:
- `curl`
- `mbstring`
- `openssl`
- `pdo_pgsql` (jika menggunakan PostgreSQL) atau `pdo_mysql`
- `gd`
- `fileinfo`
- `zip`
- `redis`
- `sockets`
- `intl`
- `pcntl` (wajib untuk Laravel Horizon & pemrosesan queue)
- `posix`

### B. Konfigurasi php.ini
Masuk ke tab **Configuration**, ubah batas memori dan unggahan:
```ini
upload_max_filesize = 100M
post_max_size = 100M
memory_limit = 512M
max_execution_time = 300
```

### C. Nonaktifkan Fungsi Berbahaya yang Diblokir aaPanel
Secara default aaPanel menonaktifkan beberapa fungsi di tab **Disabled functions**. Hapus fungsi berikut dari daftar blokir agar Laravel, Horizon, dan FFmpeg dapat bekerja:
- `proc_open`
- `proc_get_status`
- `pcntl_signal`
- `pcntl_alarm`
- `pcntl_fork`
- `pcntl_signal_dispatch`
- `putenv`
- `exec`

---

## 4. Instalasi FFmpeg & FFprobe di Server

Login ke server Anda via terminal SSH (sebagai `root`), lalu jalankan:

```bash
# Untuk Ubuntu / Debian
apt update && apt install -y ffmpeg

# Verifikasi versi yang terpasang
ffmpeg -version
ffprobe -version
```

Pastikan path binary berada di `/usr/bin/ffmpeg` dan `/usr/bin/ffprobe`.

---

## 5. Membuat Website & Mengunggah Source Code

1. Di aaPanel, masuk ke menu **Website** -> **Add site**:
   - **Domain**: Masukkan domain Anda (misal: `ruangdharma.com`).
   - **Database**: Buat database PostgreSQL/MySQL (catat nama DB, username, dan password).
   - **PHP Version**: Pilih `PHP-83`.
2. Hapus file default di folder `/www/wwwroot/ruangdharma.com`.
3. Clone repository project:
   ```bash
   cd /www/wwwroot/ruangdharma.com
   git clone https://github.com/Igustisultanh12/aivid.git .
   ```

---

## 6. Konfigurasi Lingkungan (.env)

Salin berkas contoh `.env.example` ke `.env`:
```bash
cp .env.example .env
```

Buka file `.env` dan sesuaikan nilainya:
```ini
APP_NAME="Ruang Dharma"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://ruangdharma.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ruangdharma_db
DB_USERNAME=ruangdharma_user
DB_PASSWORD=PasswordDatabaseAnda

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

REVERB_APP_ID=ruang-dharma-prod
REVERB_APP_KEY=rd-secret-key-prod
REVERB_APP_SECRET=rd-app-secret-production
REVERB_HOST="ruangdharma.com"
REVERB_PORT=443
REVERB_SCHEME="https"

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

FFMPEG_PATH=/usr/bin/ffmpeg
FFPROBE_PATH=/usr/bin/ffprobe
```

> **PERHATIAN PENTING**: Google Gemini API Key **TIDAK PERLU** dan **DILARANG** dimasukkan ke dalam file `.env`. Anda akan memasukkannya secara aman dan terenkripsi melalui panel Admin Web (`/admin/settings/gemini`).

---

## 7. Instalasi Dependensi & Build Aset

Jalankan serangkaian perintah berikut di terminal:

```bash
cd /www/wwwroot/ruangdharma.com

# 1. Pasang dependensi PHP
composer install --no-dev --optimize-autoloader

# 2. Generate Application Key
php artisan key:generate --force

# 3. Jalankan Migrasi dan Database Seeder
php artisan migrate --force --seed

# 4. Hubungkan Storage Publik
php artisan storage:link

# 5. Pasang dependensi Frontend dan Build Aset Vite
npm install
npm run build

# 6. Optimasi Cache Konfigurasi Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 8. Hak Akses dan Kepemilikan File

aaPanel menggunakan user web server `www:www`. Pastikan permission disetel dengan benar:

```bash
chown -R www:www /www/wwwroot/ruangdharma.com
chmod -R 755 /www/wwwroot/ruangdharma.com
chmod -R 775 /www/wwwroot/ruangdharma.com/storage
chmod -R 775 /www/wwwroot/ruangdharma.com/bootstrap/cache
```

---

## 9. Konfigurasi Nginx & Web Root

1. Di aaPanel, buka **Website** -> Klik domain Anda -> **Site directory**:
   - **Running directory**: Pilih `/public`.
   - Klik **Save**.
2. Masuk ke tab **URL rewrite**, pilih template **Laravel 5**, klik **Save**.
3. Masuk ke tab **SSL**, pilih **Let's Encrypt**, centang domain Anda, dan klik **Apply**. Aktifkan **Force HTTPS**.
4. Masuk ke tab **Configuration file** di pengaturan website, tambahkan reverse proxy untuk WebSocket Reverb di dalam blok `server { ... }`:

```nginx
location /app {
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_pass http://127.0.0.1:8080;
}
```

---

## 10. Konfigurasi Supervisor (Horizon & Reverb)

Buka **Supervisor Manager** di aaPanel, buat dua daemon proses:

### Daemon 1: Laravel Horizon (Queue & Video Producer)
- **Name**: `ruang-dharma-horizon`
- **Run user**: `www`
- **Run Dir**: `/www/wwwroot/ruangdharma.com`
- **Command**: `/www/server/php/83/bin/php artisan horizon`
- **Number of processes**: `1`

### Daemon 2: Laravel Reverb (Realtime WebSocket)
- **Name**: `ruang-dharma-reverb`
- **Run user**: `www`
- **Run Dir**: `/www/wwwroot/ruangdharma.com`
- **Command**: `/www/server/php/83/bin/php artisan reverb:start --port=8080`
- **Number of processes**: `1`

Klik **Start** pada kedua daemon tersebut.

---

## 11. Konfigurasi Cron Job Laravel (aaPanel Cron)

Masuk ke menu **Cron** di aaPanel:
- **Type of Task**: `Shell Script`
- **Name of Task**: `Ruang Dharma Schedule`
- **Period**: `N Minutes` -> Setiap `1` Menit
- **Script content**:
  ```bash
  /www/server/php/83/bin/php /www/wwwroot/ruangdharma.com/artisan schedule:run >> /dev/null 2>&1
  ```
- Klik **Add task**.

---

## 12. Membuat Akun Administrator Pertama

Jalankan perintah interaktif aman di terminal SSH:

```bash
cd /www/wwwroot/ruangdharma.com
php artisan ruang-dharma:admin
```

Masukkan nama, email, dan kata sandi yang kuat. Akun ini memiliki akses penuh ke Dashboard Pengaturan Google Gemini (`/admin/settings/gemini`).

Selamat! Aplikasi Ruang Dharma Anda kini telah sepenuhnya aktif, aman, dan siap memproduksi video AI!
