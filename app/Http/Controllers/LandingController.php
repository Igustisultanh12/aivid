<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use App\Models\Video;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(): Response
    {
        $popularTopics = Topic::where('is_popular', true)->take(6)->get();
        $demoVideo = Video::with(['topic', 'scenes'])->where('is_public', true)->first();

        $faqs = [
            [
                'question' => 'Mengapa durasi video dirancang tepat 30 detik?',
                'answer' => 'Durasi 30 detik adalah waktu emas untuk menyampaikan satu gagasan filosofis Hindu secara padat, memikat, dan mudah diserap oleh generasi muda di era media sosial tanpa mengurangi kedalaman makna.',
            ],
            [
                'question' => 'Bagaimana Ruang Dharma memastikan keakuratan ajaran Hindu?',
                'answer' => 'Setiap naskah yang dihasilkan AI dikaitkan dengan basis data sumber rujukan terverifikasi oleh admin, seperti Lontar Sundarigama, Bhagavad Gita, Manawa Dharmasastra, dan pedoman resmi Parisada Hindu Dharma Indonesia (PHDI).',
            ],
            [
                'question' => 'Apa perbedaan mode Ilustrasi Bergerak dan Video Generatif?',
                'answer' => 'Mode Ilustrasi Bergerak menyusun visual artistik beresolusi tinggi dengan pergerakan kamera dinamis (pan/zoom) dan transisi halus melalui FFmpeg. Mode Video Generatif memanfaatkan model video AI jika akun Google AI Studio Anda telah mengaktifkannya.',
            ],
            [
                'question' => 'Bagaimana cara menghubungkan Google Gemini API?',
                'answer' => 'Administrator website cukup memasukkan API Key Google Gemini melalui menu Pengaturan AI di dashboard admin. Seluruh kunci dienkripsi dengan standar keamanan AES-256 Laravel dan tidak perlu mengedit berkas .env server.',
            ],
            [
                'question' => 'Apakah video yang dibuat dapat langsung diunduh?',
                'answer' => 'Ya, Anda dapat mengunduh video berkualitas MP4 H.264, berkas subtitle SRT/VTT, thumbnail gambar beresolusi tinggi, hingga transkrip teks lengkap untuk keperluan edukasi dan media sosial.',
            ],
        ];

        return Inertia::render('Landing/Index', [
            'popularTopics' => $popularTopics,
            'demoVideo' => $demoVideo,
            'faqs' => $faqs,
        ]);
    }
}
