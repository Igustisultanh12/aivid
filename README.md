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

## 5. Dokumentasi Lengkap

- [Panduan Deployment aaPanel (Linux & Nginx)](docs/DEPLOY-AAPANEL.md)
- [Panduan Konfigurasi Google Gemini API](docs/CONNECT-GEMINI.md)
- [Laporan Pengujian & Verifikasi Kualitas](docs/TEST-REPORT.md)
- [Contoh Konfigurasi Nginx](deploy/nginx.conf.example)
- [Contoh Konfigurasi Supervisor Horizon](deploy/supervisor-horizon.conf.example)
- [Contoh Konfigurasi Supervisor Reverb](deploy/supervisor-reverb.conf.example)

---

## 6. Verifikasi Pengujian

Untuk menjalankan pengujian fitur otomatis (21 skenario pengujian dengan 51 asersi):

```bash
php artisan test
```

Untuk memverifikasi build frontend:
```bash
npm run build
```

---

## 7. Lisensi

Hak Cipta (c) 2026 Ruang Dharma. Dikembangkan untuk kemajuan edukasi budaya dan filosofi luhur Hindu Nusantara.
