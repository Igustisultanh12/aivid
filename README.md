# Ruang Dharma

> **Cerita bermakna dalam 30 detik.**  
> *Jelajahi filosofi Hindu dan ubah rasa ingin tahumu menjadi cerita visual yang bermakna.*

---

## 1. Tentang Ruang Dharma

**Ruang Dharma** adalah platform web terpadu untuk memproduksi video edukasi kecerdasan buatan (AI) berdurasi tepat **30 DETIK** mengenai filosofi agama Hindu, khususnya tradisi Hindu di Indonesia dan Bali (seperti Canang Sari, Galungan, Kuningan, Nyepi, Saraswati, Tri Hita Karana, Karma Phala, dan simbolisme sakral).

Aplikasi dirancang dengan pendekatan visual **Creative Studio modern bernuansa budaya Bali yang subtil dan hormat**, memadukan gradasi ungu, coral/oranye hangat, kuning keemasan, dan teal dengan tipografi proporsional serta ikonografi SVG murni (tanpa emotikon atau emoji).

---

## 2. Fitur Utama

- **Studio Produksi 7 Langkah (Wizard)**:
  1. Pemilihan topik terkurasi atau ide bebas pengguna.
  2. Pemilihan audiens target (Anak Muda, Remaja, Umum) dan gaya penyampaian (Inspiratif, Reflektif, Santai).
  3. Pemilihan mode produksi: Gerak Ilustrasi Sinematik (*Illustration Motion*) atau Klip Video Generatif (*Generative Video*).
  4. Pemilihan gaya visual (Modern Editorial, Bali Minimalis, Budaya Semarak, Cat Air Suci) dan suara narator Gemini (Pari, Kore, Zephyr, Fenrir).
  5. Pengaturan subtitel otomatis (SRT & VTT), musik latar gamelan meditatif, dan rasio aspek (9:16 vertikal, 16:9 horizontal, 1:1 persegi).
  6. Peninjauan estimasi kuota dan biaya token AI sebelum eksekusi.
  7. Mode persetujuan naskah sebelum render atau otomatis langsung jadi.
- **Pipeline Rendering Video 30 Detik Berstandar Ketat**:
  - Detik 0 s.d. 3: Hook visual pemantik rasa ingin tahu.
  - Detik 3 s.d. 23: Inti filosofis dan nilai luhur tradisi Hindu.
  - Detik 23 s.d. 30: Refleksi penutup, aksi kebajikan nyata, dan rujukan kitab suci sah.
  - Subtitel SRT & VTT disinkronisasi kata per kata.
- **Integrasi Google Gemini API Tanpa `.env`**:
  - Konfigurasi API Key secara aman melalui antarmuka web Administrator (`/admin/settings/gemini`).
  - Kunci dienkripsi menggunakan AES-256-CBC di database.
  - Kunci disamarkan (`AIzaSy...****`) saat dikirim ke antarmuka pengguna.
  - Pengujian diagnostik mandiri (Auth, Teks, Gambar Imagen 3, Suara TTS, dan Video Veo).
- **Pustaka Video & Pemutar Interaktif**:
  - Pemutar video kustom dengan progress bar waktu nyata.
  - Tab transkrip sinkron, daftar sumber rujukan sah (Lontar Sundarigama, Bhagavad Gita, Ketetapan PHDI), dan unduhan berkas (MP4, SRT, VTT, Thumbnail, Transkrip).
  - Kuis interaktif pasca-menonton dengan penilaian skor instan.
- **Seri Video Edukasi & Penjadwalan Produksi**:
  - Kelompokkan video menjadi playlist tematik.
  - Jadwalkan produksi video otomatis berkala.
- **Galeri Komunitas & Pusat Moderasi**:
  - Pengguna dapat mempublikasikan karya ke Galeri Komunitas.
  - Sistem pelaporan komunitas untuk menjaga kesucian dan kebenaran ajaran.
  - Dashboard moderasi admin untuk menyetujui, menolak, atau mencabut video.
- **Statistik & Analisis Biaya Token AI**:
  - Pantau konsumsi prompt/candidate tokens, latensi respons Gemini, dan estimasi biaya USD secara real-time.

---

## 3. Tumpukan Teknologi (Tech Stack)

- **Backend**: Laravel 12 (versi stabil terbaru), PHP 8.3.
- **Frontend**: Vue 3 (Composition API, `<script setup lang="ts">`, TypeScript), Inertia.js, Vite.
- **Desain & Gaya**: Tailwind CSS v4, Palet Budaya Bali Modern, Lucide SVG Icons (`lucide-vue-next`).
- **AI Engine**: Google Gemini REST API langsung via Laravel HTTP Client (`Http::withHeaders(...)`) sebagai satu-satunya penyedia AI.
- **Rendering Mesin**: FFmpeg & FFprobe (motion pan/zoom, overlay teks, sinkronisasi audio dan penyesuaian durasi 30,0 detik).
- **Database & Queue**: PostgreSQL / SQLite, Redis, Laravel Horizon.
- **Real-time WebSocket**: Laravel Reverb.

---

## 4. Panduan Menjalankan Secara Lokal (Local Development)

### Persyaratan:
- PHP 8.3+ dengan ekstensi `curl`, `mbstring`, `openssl`, `pdo_sqlite`, `gd`, `fileinfo`, `zip`, `intl`.
- Composer 2.7+.
- Node.js 20+ & NPM.
- FFmpeg & FFprobe terpasang dan terdaftar di PATH sistem.

### Langkah Instalasi:
```bash
# 1. Clone repository
git clone https://github.com/Igustisultanh12/aivid.git ruang-dharma
cd ruang-dharma

# 2. Salin environment file
cp .env.example .env

# 3. Pasang dependensi PHP
composer install

# 4. Generate Application Key
php artisan key:generate

# 5. Jalankan migrasi dan database seeder
php artisan migrate --seed

# 6. Hubungkan direktori storage publik
php artisan storage:link

# 7. Pasang dependensi frontend dan build
npm install
npm run build # atau 'npm run dev' untuk live reloading

# 8. Buat akun Administrator pertama
php artisan ruang-dharma:admin

# 9. Jalankan web server Laravel
php artisan serve
```

Akses web browser Anda di `http://localhost:8000`.

---

## 5. Panduan Deployment ke aaPanel (Linux VPS)

Panduan praktis dan lengkap untuk menerapkan Ruang Dharma di server VPS Ubuntu / Debian yang menggunakan kontrol panel **aaPanel**:

### A. Paket yang Wajib Dipasang di aaPanel (App Store)
1. **Nginx 1.24** (atau versi stabil terbaru).
2. **PHP 8.3**.
3. **PostgreSQL 15/16** (atau MySQL 8.0).
4. **Redis 7.x** (untuk cache, sesi, dan antrean job render video).
5. **Supervisor Manager** (untuk mengelola worker Horizon dan WebSocket Reverb).
6. **Node.js Version Manager** (pilih versi Node LTS v20 atau v22).

### B. Konfigurasi PHP 8.3
1. Buka **App Store** -> **PHP 8.3** -> **Settings** -> **Install extensions**:
   - Pasang: `curl`, `mbstring`, `openssl`, `pdo_pgsql` (atau `pdo_mysql`), `gd`, `fileinfo`, `zip`, `redis`, `sockets`, `intl`, `pcntl`, `posix`.
2. Masuk ke tab **Configuration**, ubah batas di `php.ini`:
   ```ini
   upload_max_filesize = 100M
   post_max_size = 100M
   memory_limit = 512M
   max_execution_time = 300
   ```
3. Masuk ke tab **Disabled functions**, **hapus fungsi-fungsi berikut dari daftar blokir**:
   - `proc_open`, `proc_get_status`, `pcntl_signal`, `pcntl_alarm`, `pcntl_fork`, `pcntl_signal_dispatch`, `putenv`, `exec`.

### C. Instalasi FFmpeg & FFprobe di Server
Login via terminal SSH server sebagai `root`:
```bash
apt update && apt install -y ffmpeg
ffmpeg -version
ffprobe -version
```

### D. Tambah Situs & Unduh Kode Sumber
1. Di aaPanel, masuk ke menu **Website** -> **Add site**:
   - Masukkan nama domain (contoh: `ruangdharma.com`).
   - Buat database PostgreSQL/MySQL baru.
   - Pilih versi PHP: `PHP-83`.
2. Di terminal SSH, clone repositori ke direktori situs:
   ```bash
   cd /www/wwwroot/ruangdharma.com
   rm -rf * .[^.]*
   git clone https://github.com/Igustisultanh12/aivid.git .
   ```

### E. Konfigurasi Lingkungan (.env)
Salin dan sesuaikan berkas lingkungan:
```bash
cp .env.example .env
nano .env
```
Isi konfigurasi database (DB_DATABASE, DB_USERNAME, DB_PASSWORD), Redis host/port, Reverb host/key, dan jalur FFmpeg (`/usr/bin/ffmpeg`).
> **CATATAN**: Google Gemini API Key **TIDAK PERLU** dimasukkan di `.env`. Kunci dimasukkan secara aman melalui panel web admin.

### F. Eksekusi Instalasi & Kompilasi Aset
Jalankan di terminal server:
```bash
cd /www/wwwroot/ruangdharma.com

# Pasang dependensi PHP production
composer install --no-dev --optimize-autoloader

# Kunci aplikasi & migrasi tabel
php artisan key:generate --force
php artisan migrate --force --seed
php artisan storage:link

# Kompilasi frontend Vue 3
npm install
npm run build

# Optimasi cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### G. Atur Izin Berkas
```bash
chown -R www:www /www/wwwroot/ruangdharma.com
chmod -R 755 /www/wwwroot/ruangdharma.com
chmod -R 775 /www/wwwroot/ruangdharma.com/storage
chmod -R 775 /www/wwwroot/ruangdharma.com/bootstrap/cache
```

### H. Konfigurasi Nginx di aaPanel
Buka menu **Website** -> klik domain Anda:
1. Tab **Site directory**: Ubah **Running directory** ke `/public`, klik **Save**.
2. Tab **URL rewrite**: Pilih template **Laravel 5**, klik **Save**.
3. Tab **SSL**: Pasang SSL **Let's Encrypt**, centang domain, klik **Apply**, lalu aktifkan **Force HTTPS**.
4. Tab **Configuration file**: Tambahkan reverse proxy Reverb WebSocket di dalam blok `server { ... }`:
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

### I. Jalankan Background Daemon (Supervisor)
Buka aplikasi **Supervisor Manager** di aaPanel, tambahkan 2 proses daemon:

1. **Laravel Horizon (Antrean Video AI)**:
   - Name: `ruang-dharma-horizon`
   - Run user: `www`
   - Run Dir: `/www/wwwroot/ruangdharma.com`
   - Command: `/www/server/php/83/bin/php artisan horizon`
   - Num of processes: `1`

2. **Laravel Reverb (WebSocket Real-Time)**:
   - Name: `ruang-dharma-reverb`
   - Run user: `www`
   - Run Dir: `/www/wwwroot/ruangdharma.com`
   - Command: `/www/server/php/83/bin/php artisan reverb:start --port=8080`
   - Num of processes: `1`

Klik **Start** pada kedua daemon tersebut.

### J. Penjadwalan Cron Job
Buka menu **Cron** di aaPanel:
- Type of Task: `Shell Script`
- Name of Task: `Ruang Dharma Schedule`
- Period: `N Minutes` -> pilih `1` menit
- Script content:
  ```bash
  /www/server/php/83/bin/php /www/wwwroot/ruangdharma.com/artisan schedule:run >> /dev/null 2>&1
  ```
- Klik **Add task**.

### K. Buat Akun Administrator & Hubungkan Gemini
1. Di terminal SSH, jalankan:
   ```bash
   cd /www/wwwroot/ruangdharma.com
   php artisan ruang-dharma:admin
   ```
2. Buka browser, login ke `https://domain-anda.com/login`.
3. Buka menu **Admin** -> **Integrasi Google Gemini** (`/admin/settings/gemini`).
4. Masukkan Google Gemini API Key Anda, lalu klik **Simpan & Uji Kredensial**.

---

## 6. Berkas Dokumentasi Tambahan

- [Panduan Deployment aaPanel Lengkap](docs/DEPLOY-AAPANEL.md)
- [Panduan Konfigurasi Google Gemini API](docs/CONNECT-GEMINI.md)
- [Laporan Pengujian & Verifikasi Kualitas](docs/TEST-REPORT.md)
- [Contoh Berkas Konfigurasi Nginx](deploy/nginx.conf.example)
- [Contoh Berkas Konfigurasi Supervisor Horizon](deploy/supervisor-horizon.conf.example)
- [Contoh Berkas Konfigurasi Supervisor Reverb](deploy/supervisor-reverb.conf.example)

---

## 7. Verifikasi Pengujian

Untuk menjalankan pengujian fitur otomatis (21 skenario pengujian dengan 51 asersi):

```bash
php artisan test
```

Untuk memverifikasi build frontend:
```bash
npm run build
```

---

## 8. Lisensi

Hak Cipta (c) 2026 Ruang Dharma. Dikembangkan untuk kemajuan edukasi budaya dan filosofi luhur Hindu Nusantara.
