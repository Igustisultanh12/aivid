# Panduan Koneksi & Konfigurasi Google Gemini API di Ruang Dharma

Ruang Dharma menggunakan **Google Gemini API** secara langsung melalui backend Laravel HTTP Client sebagai satu-satunya penyedia kecerdasan buatan (AI) untuk menghasilkan naskah 30 detik, prompt visual, ilustrasi beresolusi tinggi, suara narasi, dan video klip edukasi Hindu.

Sesuai standar keamanan tinggi, **Anda tidak perlu mengedit berkas `.env` untuk memasukkan API Key Gemini**. Seluruh konfigurasi dilakukan melalui antarmuka web Administrator secara terenkripsi (AES-256).

---

## 1. Cara Mendapatkan Google Gemini API Key

1. Kunjungi situs resmi Google AI Studio:  
   👉 **[https://aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey)**
2. Masuk menggunakan akun Google Anda.
3. Klik tombol **"Create API Key"** (atau **"Get API Key"**).
4. Pilih project Google Cloud yang sudah ada, atau buat project baru dengan mengeklik **"Create API key in new project"**.
5. Salin string API key yang muncul (biasanya diawali dengan karakter `AIzaSy...`).

> **TIPS**: Simpan API Key Anda di tempat yang aman. Jangan membagikan key ke publik atau mengunggahnya ke repositori git terbuka.

---

## 2. Memasukkan API Key ke Ruang Dharma

1. Masuk ke aplikasi Ruang Dharma menggunakan akun Administrator Anda.
2. Buka menu samping kiri: **Admin** -> **Integrasi Google Gemini**  
   (atau akses langsung URL: `https://domain-anda.com/admin/settings/gemini`).
3. Pada kartu **"Kredensial Google Gemini API"**:
   - Tempelkan API Key yang telah Anda salin pada kolom **"Google Gemini API Key"**.
   - Klik tombol **"Simpan & Uji Kredensial"**.
4. Sistem backend akan:
   - Mengenkripsi API Key menggunakan algoritma **AES-256-CBC** dengan kunci `APP_KEY` server Anda.
   - Menyimpan ciphertext ke dalam tabel database `app_settings`.
   - Menguji otentikasi langsung ke server Google (`https://generativelanguage.googleapis.com/v1beta/models`).
   - Menampilkan status koneksi **"Terhubung & Aktif"** beserta latensi respons server dalam milidetik.
5. Pada antarmuka pengguna, API Key Anda akan disamarkan secara permanen (contoh: `AIzaSyB3...4xZ9`) sehingga tidak dapat dicuri melalui inspect element ataupun request network browser.

---

## 3. Memilih Model AI Sesuai Kebutuhan

Pada halaman yang sama, terdapat kartu **"Pemilihan Model AI & Preferensi Suara"**:

### A. Model Teks (Naskah 30 Detik, Storyboard & Kuis)
- **Gemini 2.5 Flash** *(Sangat Direkomendasikan)*: Model generasi terbaru yang super cepat, berwawasan budaya luas, dan sangat hemat biaya.
- **Gemini 2.0 Flash**: Model multimodal cepat dan andal.
- **Gemini 1.5 Pro**: Pilihan alternatif untuk penalaran filosofis yang mendalam.

### B. Model Gambar (Ilustrasi Scene & Thumbnail)
- **Imagen 3 (`imagen-3.0-generate-002`)** *(Rekomendasi Utama)*: Menghasilkan gambar visual sinematik dengan detail pencahayaan, tekstur janur, dan pura Bali yang sangat memukau.
- **Gemini 2.0 Flash Multimodal**: Model alternatif visual berbasis generatif.

### C. Model Suara & Karakter Narator (TTS)
- **Pari**: Suara wanita dengan pembawaan naratif yang hangat, santun, dan cocok untuk konten edukasi santai.
- **Kore**: Suara pria yang tenang, bijak, dan membimbing.
- **Zephyr**: Suara wanita yang dinamis dan berenergi muda.
- **Fenrir**: Suara pria yang berwibawa untuk tema sejarah atau sloka suci.

### D. Model Video Generatif
- **Veo 2.0 (`veo-2.0-generate-001`)**: Model pembuatan klip video dinamis dari Google.

Setelah memilih, klik **"Simpan Konfigurasi Model"**.

---

## 4. Pengujian Diagnostik Mandiri

Untuk memastikan setiap saluran integrasi berfungsi dengan baik, Anda dapat menggunakan tombol uji terpisah pada halaman admin:

1. **Uji Otentikasi & Model**: Memeriksa validitas key dan daftar model yang diaktifkan oleh Google untuk project Anda.
2. **Uji Pembuatan Naskah (Teks)**: Meminta Gemini menyusun 1 bait filosofi Tri Hita Karana dalam format JSON terstruktur.
3. **Uji Pembuatan Gambar (Imagen 3)**: Menghasilkan 1 gambar contoh dengan prompt budaya Bali.
4. **Uji Pembuatan Video**: Menguji respon pembuatan video generatif.

Setiap hasil pengujian dilengkapi informasi latensi (ms), token input/output, dan status keberhasilan.

---

## 5. Pemecahan Masalah (Troubleshooting)

| Gejala Masalah | Penyebab Umum | Solusi yang Disarankan |
|---|---|---|
| **Error 400: API_KEY_INVALID** | Karakter API key salah disalin atau terpotong. | Hapus koneksi di halaman admin lalu salin ulang key dari Google AI Studio. |
| **Error 403: PERMISSION_DENIED** | Project Google Cloud Anda belum mengaktifkan Generative Language API. | Buka Google Cloud Console, pilih project Anda, dan pastikan API "Generative Language API" berstatus Enabled. |
| **Error 429: RESOURCE_EXHAUSTED** | Kuota rate limit gratis (RPM/TPM) dari akun Google Anda terlampaui. | Tunggu 1 menit atau aktifkan opsi Pay-As-You-Go di Google AI Studio untuk kapasitas lebih tinggi. |
| **Gambar menggunakan fallback prosedural** | Model Imagen 3 belum diizinkan pada region project Google Cloud Anda. | Pilih model alternatif di halaman pengaturan, atau ubah region project ke `us-central1` di Google Cloud Console. |
| **Tampilan Key Kosong Setelah Restart** | Berkas `.env` baris `APP_KEY` diganti tanpa migrasi enkripsi. | Pastikan `APP_KEY` tidak diubah setelah memasukkan API Key, karena enkripsi database bergantung pada kunci ini. |

---

## 6. Arsitektur Keamanan Data
- **Zero Plaintext Storage**: API key tidak pernah disimpan dalam format teks polos.
- **No Client Exposure**: Kunci API tidak pernah dikirim ke frontend Vue, tidak disimpan di cookies, localStorage, atau build Vite.
- **Backend-Only Execution**: Semua panggilan ke Gemini dilakukan langsung oleh PHP worker dari server backend menggunakan `Http::withHeaders(['x-goog-api-key' => ...])`.
