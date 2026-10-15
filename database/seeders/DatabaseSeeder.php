<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\GlossaryTerm;
use App\Models\Source;
use App\Models\Topic;
use App\Models\User;
use App\Models\Video;
use App\Models\StoryboardScene;
use App\Models\VideoQuiz;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. App Settings
        $defaultSettings = [
            'gemini_text_model' => ['value' => 'gemini-2.5-flash', 'encrypted' => false, 'group' => 'gemini'],
            'gemini_image_model' => ['value' => 'imagen-3.0-generate-002', 'encrypted' => false, 'group' => 'gemini'],
            'gemini_tts_model' => ['value' => 'gemini-2.0-flash', 'encrypted' => false, 'group' => 'gemini'],
            'gemini_video_model' => ['value' => 'veo-2.0-generate-001', 'encrypted' => false, 'group' => 'gemini'],
            'gemini_voice_name' => ['value' => 'Pari', 'encrypted' => false, 'group' => 'gemini'],
            'app_max_video_per_user' => ['value' => '10', 'encrypted' => false, 'group' => 'limits'],
            'app_daily_render_limit' => ['value' => '50', 'encrypted' => false, 'group' => 'limits'],
        ];

        foreach ($defaultSettings as $key => $data) {
            AppSetting::set($key, $data['value'], $data['encrypted'], $data['group']);
        }

        // 2. Verified Hindu Topics
        $topicsData = [
            [
                'title' => 'Makna Filosofis Canang Sari',
                'slug' => 'makna-filosofis-canang-sari',
                'description' => 'Memahami canang sari sebagai simbol persembahan tulus, harmoni lima indera, dan wujud cinta kasih tanpa pamrih kepada Sang Hyang Widhi Wasa.',
                'category' => 'Tradisi Bali & Persembahan',
                'cultural_context' => 'Tradisi Hindu Bali',
                'is_popular' => true,
                'icon_name' => 'Flower2',
                'suggested_prompts' => [
                    'Mengapa setiap bunga di canang sari memiliki arah mata angin dan warna tertentu?',
                    'Apa filosofi porosan (sirih, kapur, pinang) dalam canang sari?',
                    'Bagaimana canang sari mengajarkan generasi muda tentang ketulusan harian?'
                ],
            ],
            [
                'title' => 'Filosofi Hari Raya Galungan',
                'slug' => 'filosofi-hari-raya-galungan',
                'description' => 'Kemenangan Dharma (kebaikan) melawan Adharma (keburukan) dalam batin manusia, ditandai penjor nan megah sebagai lambang rasa syukur.',
                'category' => 'Hari Suci & Perayaan',
                'cultural_context' => 'Tradisi Hindu Bali',
                'is_popular' => true,
                'icon_name' => 'Sun',
                'suggested_prompts' => [
                    'Apa makna mendalam dari penjor yang melengkung indah saat Galungan?',
                    'Bagaimana perang batin antara Dharma dan Adharma terjadi dalam keseharian anak muda?',
                    'Arti sesungguhnya dari hari Sugihan Jawa dan Sugihan Bali menjelang Galungan.'
                ],
            ],
            [
                'title' => 'Makna Hari Raya Kuningan',
                'slug' => 'makna-hari-raya-kuningan',
                'description' => 'Momen introspeksi dan memohon pencerahan budi pekerti luhur kepada Ida Sang Hyang Widhi Wasa serta para leluhur.',
                'category' => 'Hari Suci & Perayaan',
                'cultural_context' => 'Tradisi Hindu Bali',
                'is_popular' => true,
                'icon_name' => 'Flame',
                'suggested_prompts' => [
                    'Mengapa persembahyangan Kuningan selesai sebelum jam 12 siang?',
                    'Makna tamiang dan endongan sebagai perisai spiritual dan bekal kebijaksanaan.',
                    'Kuningan sebagai peneguh tekad hidup berlandaskan kebenaran.'
                ],
            ],
            [
                'title' => 'Nyepi dan Seni Refleksi Diri',
                'slug' => 'nyepi-dan-seni-refleksi-diri',
                'description' => 'Catur Brata Penyepian sebagai jeda hening bagi bumi dan jiwa manusia di tengah hiruk-pikuk modernitas.',
                'category' => 'Refleksi & Meditasi',
                'cultural_context' => 'Tradisi Hindu Bali & Filosofi Universal',
                'is_popular' => true,
                'icon_name' => 'Moon',
                'suggested_prompts' => [
                    'Bagaimana Catur Brata Penyepian menjadi detoks digital dan spiritual paling ampuh?',
                    'Makna pawai ogoh-ogoh malam pengerupukan sebelum keheningan total.',
                    'Manfaat Nyepi bagi pemulihan ekosistem bumi dan kesehatan mental.'
                ],
            ],
            [
                'title' => 'Saraswati dan Kemuliaan Ilmu Pengetahuan',
                'slug' => 'saraswati-dan-kemuliaan-ilmu-pengetahuan',
                'description' => 'Ilmu pengetahuan sebagai cahaya yang membebaskan manusia dari kegelapan (Awidya) menuju kebijaksanaan dan kemandirian.',
                'category' => 'Pengetahuan & Kebijaksanaan',
                'cultural_context' => 'Filosofi Hindu Nusantara',
                'is_popular' => true,
                'icon_name' => 'BookOpen',
                'suggested_prompts' => [
                    'Simbol Dewi Saraswati: angsa, teratai, veena, dan lontar dalam pendidikan modern.',
                    'Mengapa pada hari Saraswati kita dilarang membaca atau menulis buku yang diupacarai?',
                    'Banyu Pinaruh: menyucikan pikiran dengan air ilmu di fajar hari.'
                ],
            ],
            [
                'title' => 'Tri Hita Karana dalam Keseharian',
                'slug' => 'tri-hita-karana-dalam-keseharian',
                'description' => 'Tiga harmoni penyebab kebahagiaan hidup: relasi dengan Sang Pencipta (Parhyangan), sesama manusia (Pawongan), dan alam sekitar (Palemahan).',
                'category' => 'Etika & Ekologi',
                'cultural_context' => 'Tradisi Hindu Bali & Tata Nilai Kehidupan',
                'is_popular' => true,
                'icon_name' => 'HeartHandshake',
                'suggested_prompts' => [
                    'Bagaimana anak muda menerapkan Palemahan dalam mengatasi polusi sampah plastik?',
                    'Pawongan di era media sosial: menjaga keharmonisan komunikasi digital.',
                    'Parhyangan sebagai ruang syukur batin di tengah kesibukan harian.'
                ],
            ],
            [
                'title' => 'Karma Phala: Sebab, Akibat, dan Tanggung Jawab',
                'slug' => 'karma-phala-sebab-akibat-tanggung-jawab',
                'description' => 'Hukum universal yang memastikan setiap pikiran, perkataan, dan perbuatan menghasilkan buahnya masing-masing tanpa ada yang terlewat.',
                'category' => 'Hukum Kosmik & Moral',
                'cultural_context' => 'Filosofi Hindu Umum',
                'is_popular' => true,
                'icon_name' => 'Scale',
                'suggested_prompts' => [
                    'Membedakan Sanchita, Prarabdha, dan Kriyamana Karma Phala dengan analogi mudah.',
                    'Mengapa orang baik terkadang mengalami penderitaan menurut Karma Phala?',
                    'Karma Phala bukan takdir pasif, melainkan kekuatan untuk mengubah masa depan.'
                ],
            ],
            [
                'title' => 'Makna Simbol dalam Persembahyangan',
                'slug' => 'makna-simbol-dalam-persembahyangan',
                'description' => 'Kupas tuntas makna dupa, tirtha, bija/bhasma, dan bunga dalam ritual sembahyang Panca Sembah.',
                'category' => 'Ritual & Simbolisme',
                'cultural_context' => 'Tradisi Hindu Bali & Liturgi',
                'is_popular' => false,
                'icon_name' => 'Sparkles',
                'suggested_prompts' => [
                    'Mengapa tirtha dipercikkan di kepala, diminum, dan diusapkan di wajah?',
                    'Arti dupa sebagai saksi dewa agni dan penuntun konsentrasi batin.',
                    'Bija di kening: menanam benih kesucian pada pusat kesadaran (Ajna Chakra).'
                ],
            ],
        ];

        $topicModels = [];
        foreach ($topicsData as $topic) {
            $topicModels[$topic['slug']] = Topic::create($topic);
        }

        // 3. Verified Cultural and Scriptural Sources
        $sourcesData = [
            [
                'title' => 'Lontar Sundarigama',
                'author_or_institution' => 'Pusat Dokumentasi Kebudayaan Bali & Badan Pembina Lembaga Adat',
                'source_type' => 'Lontar Klasik',
                'reference_section' => 'Lembar 12-18 (Kutipan Tata Pelaksanaan Hari Suci Galungan, Kuningan, dan Nyepi)',
                'tradition_context' => 'Tradisi Hindu Bali',
                'region' => 'Bali',
                'verification_status' => 'verified',
                'notes' => 'Sumber otoritatif tradisi hari suci dan makna filosofis ritual di Bali.',
            ],
            [
                'title' => 'Pedoman Panca Yadnya',
                'author_or_institution' => 'Parisada Hindu Dharma Indonesia (PHDI) Pusat',
                'source_type' => 'Buku Panduan Resmi Keagamaan',
                'reference_section' => 'Bab III: Dewa Yadnya dan Sarana Upakara Canang Sari',
                'tradition_context' => 'Tradisi Hindu Bali & Nusantara',
                'region' => 'Nasional',
                'verification_status' => 'verified',
                'notes' => 'Rujukan resmi PHDI mengenai susunan filosofis canang, porosan, dan bunga.',
            ],
            [
                'title' => 'Bhagavad Gita As It Is / Terjemahan Bahasa Indonesia',
                'author_or_institution' => 'Kementerian Agama RI - Bimas Hindu',
                'source_type' => 'Kitab Suci (Smrti)',
                'reference_section' => 'Bab IX Sloka 26 (Patram Puspan Phalam Toyam) & Bab II Sloka 47 (Karmanye Vadhikaraste)',
                'tradition_context' => 'Filosofi Hindu Umum',
                'region' => 'Universal',
                'verification_status' => 'verified',
                'notes' => 'Landasan teologis persembahan cinta kasih dan hukum aksi Karma Phala.',
            ],
            [
                'title' => 'Sarasamuccaya',
                'author_or_institution' => 'Bhagawan Wararuci (Saduran Dr. I Gusti Bagus Sugriwa)',
                'source_type' => 'Kitab Sastra Moral & Etika',
                'reference_section' => 'Sloka 4, 8, dan 14 (Keutamaan Menjelma Menjadi Manusia)',
                'tradition_context' => 'Filosofi Hindu Nusantara',
                'region' => 'Jawa Kuno - Bali',
                'verification_status' => 'verified',
                'notes' => 'Pilar ajaran etika perbuatan baik untuk melebur karma buruk.',
            ],
            [
                'title' => 'Kajian Kearifan Lokal Tri Hita Karana',
                'author_or_institution' => 'Universitas Hindu Negeri I Gusti Bagus Sugriwa Denpasar',
                'source_type' => 'Kajian Akademik & Nilai Budaya',
                'reference_section' => 'Halaman 45-62 (Harmonisasi Ekologis dan Sosial di Era Digital)',
                'tradition_context' => 'Tradisi Hindu Bali & Filsafat Ekologi',
                'region' => 'Bali',
                'verification_status' => 'verified',
                'notes' => 'Relevansi Tri Hita Karana dalam pembangunan berkelanjutan dan gaya hidup kontemporer.',
            ],
        ];

        $sourceModels = [];
        foreach ($sourcesData as $source) {
            $sourceModels[] = Source::create($source);
        }

        // 4. Hindu Glossary Terms
        $glossaryData = [
            [
                'term' => 'Dharma',
                'sanskrit_or_balinese_origin' => 'Sansekerta: धृ (Dhr) - Menopang atau memelihara',
                'meaning' => 'Kebenaran abadi, kebajikan, hukum moral, dan kewajiban hidup yang menopang keharmonisan alam semesta.',
                'context' => 'Konsep fondasi dalam agama Hindu; landasan dari setiap perbuatan manusia.',
                'related_traditions' => 'Hari Raya Galungan (Kemenangan Dharma atas Adharma), Catur Purusa Artha.',
                'phonetic_pronunciation' => 'Dhar-ma',
            ],
            [
                'term' => 'Karma Phala',
                'sanskrit_or_balinese_origin' => 'Sansekerta: कर्म (Karma) - perbuatan, फल (Phala) - buah/hasil',
                'meaning' => 'Hukum sebab akibat spiritual di mana setiap tindakan (pikiran, ucapan, perbuatan) pasti mendatangkan akibat yang setimpal.',
                'context' => 'Salah satu dari Panca Sradha (lima keyakinan dasar Hindu).',
                'related_traditions' => 'Sanchita, Prarabdha, dan Kriyamana Karma.',
                'phonetic_pronunciation' => 'Kar-ma Pfa-la',
            ],
            [
                'term' => 'Canang Sari',
                'sanskrit_or_balinese_origin' => 'Bahasa Bali / Kawi: Canang (keindahan yang menyenangkan), Sari (inti/esensi)',
                'meaning' => 'Anyaman janur berbentuk persegi sebagai sarana persembahan harian umat Hindu di Bali, melambangkan penyerahan diri dan rasa syukur tulus.',
                'context' => 'Wujud bakti harian (Nitya Yadnya).',
                'related_traditions' => 'Yadnya harian, persembahyangan Panca Sembah.',
                'phonetic_pronunciation' => 'Tja-nang Sa-ri',
            ],
            [
                'term' => 'Tri Hita Karana',
                'sanskrit_or_balinese_origin' => 'Sansekerta / Bali: Tri (tiga), Hita (kebahagiaan/kesejahteraan), Karana (penyebab)',
                'meaning' => 'Falsafah hidup yang mengajarkan tiga pilar keharmonisan: hubungan harmonis dengan Tuhan (Parhyangan), sesama (Pawongan), dan alam (Palemahan).',
                'context' => 'Tata ruang desa adat Bali, subak (irigasi), dan etika lingkungan hidup.',
                'related_traditions' => 'Subak, Banjar adat, Tumpek Wariga, Tumpek Kandang.',
                'phonetic_pronunciation' => 'Tri Hi-ta Ka-ra-na',
            ],
            [
                'term' => 'Catur Brata Penyepian',
                'sanskrit_or_balinese_origin' => 'Sansekerta: Catur (empat), Vrata (pantangan/pengendalian diri)',
                'meaning' => 'Empat disiplin spiritual saat Hari Raya Nyepi: Amati Geni (tidak menyalakan api/cahaya batin), Amati Karya (tidak bekerja), Amati Lelungan (tidak bepergian), dan Amati Lelanguan (tidak mencari hiburan).',
                'context' => 'Tahun Baru Saka Hindu di Bali.',
                'related_traditions' => 'Tawur Agung Kesanga, Nyepi, Ngembak Geni.',
                'phonetic_pronunciation' => 'Tja-tur Bra-ta Pe-nye-pi-an',
            ],
            [
                'term' => 'Moksha',
                'sanskrit_or_balinese_origin' => 'Sansekerta: मोक्ष (Mokṣa) - Kebebasan / Pelepasan',
                'meaning' => 'Tujuan spiritual tertinggi dalam Hindu, yaitu terbebasnya Atman dari siklus kelahiran kembali (Samsara) dan bersatu kembali dengan Brahman.',
                'context' => 'Tujuan akhir dalam Catur Purusa Artha (Dharma, Artha, Kama, Moksha).',
                'related_traditions' => 'Meditasi, Yoga, Jnana Marga.',
                'phonetic_pronunciation' => 'Mok-sha',
            ],
            [
                'term' => 'Porosan',
                'sanskrit_or_balinese_origin' => 'Bahasa Bali: Poros (inti penyatu)',
                'meaning' => 'Jantung dari canang sari yang terdiri atas daun sirih (simbol Wisnu), kapur sirih (simbol Siwa), dan pinang (simbol Brahma) yang menyatu dalam Tri Murti.',
                'context' => 'Simbol Tri Murti yang menghidupkan setiap sarana upakara.',
                'related_traditions' => 'Canang sari, banten pejati.',
                'phonetic_pronunciation' => 'Po-ro-san',
            ],
            [
                'term' => 'Tirtha',
                'sanskrit_or_balinese_origin' => 'Sansekerta: तीर्थ (Tīrtha) - Air suci / tempat penyeberangan suci',
                'meaning' => 'Air suci yang telah disucikan melalui doa dan mantra, berfungsi membersihkan kekotoran jasmani dan rohani serta menganugerahkan kedamaian batin.',
                'context' => 'Puncak dari setiap persembahyangan dan upacara yadnya di Bali.',
                'related_traditions' => 'Melukat, Panca Sembah, Piodalan.',
                'phonetic_pronunciation' => 'Teer-tha',
            ],
        ];

        foreach ($glossaryData as $term) {
            GlossaryTerm::create($term);
        }

        // 5. Create Demo User and Admin
        $demoUser = User::create([
            'name' => 'Dharma Explorer',
            'email' => 'user@ruangdharma.id',
            'email_verified_at' => now(),
            'password' => Hash::make('RuangDharma2026!'),
            'role' => 'user',
            'quota_total' => 10,
            'quota_used' => 1,
            'avatar_url' => null,
        ]);

        // 6. Create Demo Video (Fully functional example labeled clearly as Demo)
        $demoTopic = $topicModels['makna-filosofis-canang-sari'] ?? Topic::first();
        $demoSource = $sourceModels[1] ?? Source::first();

        $demoVideo = Video::create([
            'id' => (string) Str::uuid(),
            'user_id' => $demoUser->id,
            'title' => 'Canang Sari: Ketulusan Hati dalam Selembar Janur',
            'slug' => 'canang-sari-ketulusan-hati-dalam-selembar-janur',
            'hook_text' => 'Mengapa sepotong janur dan beberapa kuntum bunga bisa menjadi simbol cinta paling tulus di muka bumi?',
            'reflection_text' => 'Bukan mahalnya persembahan yang dinilai, melainkan ketulusan hati saat kita melepaskan ego untuk bersyukur.',
            'summary_text' => 'Video 30 detik menyingkap rahasia filosofis canang sari sebagai cermin ketulusan dan keharmonisan indera anak muda.',
            'topic_id' => $demoTopic->id,
            'topic_name' => $demoTopic->title,
            'target_audience' => 'Anak Muda',
            'tone' => 'Inspiratif & Hangat',
            'production_mode' => 'illustration_motion',
            'visual_style' => 'modern_editorial',
            'aspect_ratio' => '9:16',
            'voice_id' => 'Pari',
            'has_subtitles' => true,
            'status' => 'completed',
            'progress_percent' => 100,
            'current_stage_label' => 'Video berhasil diproduksi (Demo)',
            'duration_seconds' => 30.00,
            'video_path' => 'demo/canang-sari-30s.mp4',
            'thumbnail_path' => 'demo/canang-sari-thumb.jpg',
            'subtitles_srt_path' => 'demo/canang-sari.srt',
            'subtitles_vtt_path' => 'demo/canang-sari.vtt',
            'transcript_text' => "Pernahkah kamu bertanya mengapa setiap pagi di Bali dihiasi canang sari? Di balik anyaman janur sederhana, tersimpan pesan agung. Porosan di tengahnya menyatukan sirih, kapur, dan pinang lambang Tri Murti pencipta, pemelihara, dan pelebur. Bunga beraneka warna menenangkan lima indera kita. Canang mengajarkan kita satu hal: ketulusan memberi tanpa pamrih adalah puncak kebahagiaan.",
            'is_public' => true,
            'is_featured' => true,
            'moderation_status' => 'approved',
            'views_count' => 142,
            'downloads_count' => 38,
            'metadata' => [
                'is_demo' => true,
                'resolution' => '1080x1920',
                'fps' => 30,
                'scenes_count' => 5,
                'source_citations' => ['Pedoman Panca Yadnya PHDI Pusat', 'Bhagavad Gita IX.26'],
            ],
        ]);

        // Demo Storyboard Scenes
        $scenes = [
            [
                'scene_order' => 1,
                'start_time' => 0.00,
                'end_time' => 3.50,
                'duration' => 3.50,
                'narration_text' => 'Pernahkah kamu bertanya mengapa setiap pagi di Bali selalu diawali dengan meletakkan canang sari?',
                'onscreen_text' => 'Rahasia Selembar Janur',
                'visual_prompt' => 'Cinematic close-up of fresh Balinese canang sari on a stone temple shrine with morning golden sunlight and soft incense smoke',
                'camera_motion' => 'zoom_in',
                'transition_type' => 'fade',
                'source_id' => $demoSource->id,
                'source_citation' => 'Pedoman Panca Yadnya PHDI',
                'status' => 'ready',
            ],
            [
                'scene_order' => 2,
                'start_time' => 3.50,
                'end_time' => 10.00,
                'duration' => 6.50,
                'narration_text' => 'Di balik anyaman janur persegi ceper yang sederhana, tersimpan simbol bumi pertiwi tempat kita berpijak.',
                'onscreen_text' => 'Ceper: Wadah Pertiwi',
                'visual_prompt' => 'Artistic editorial illustration showing delicate woven coconut leaf ceper tray, balanced sacred geometry',
                'camera_motion' => 'pan_left',
                'transition_type' => 'cut',
                'source_id' => $demoSource->id,
                'source_citation' => 'Lontar Sundarigama',
                'status' => 'ready',
            ],
            [
                'scene_order' => 3,
                'start_time' => 10.00,
                'end_time' => 17.50,
                'duration' => 7.50,
                'narration_text' => 'Di tengahnya ada porosan: paduan sirih, kapur, dan pinang lambang Tri Murti yang menghidupkan seluruh alam semesta.',
                'onscreen_text' => 'Porosan: Nafas Tri Murti',
                'visual_prompt' => 'Detailed macro of porosan betel leaf and areca nut, warm ambient glow, rich cultural texture',
                'camera_motion' => 'tilt_up',
                'transition_type' => 'fade',
                'source_id' => $demoSource->id,
                'source_citation' => 'Pedoman Panca Yadnya PHDI',
                'status' => 'ready',
            ],
            [
                'scene_order' => 4,
                'start_time' => 17.50,
                'end_time' => 24.00,
                'duration' => 6.50,
                'narration_text' => 'Warna-warni bunganya mengarah ke empat penjuru mata angin, melambangkan harmoni pikiran dan ketenangan lima indera.',
                'onscreen_text' => 'Harmoni Panca Indera',
                'visual_prompt' => 'Vibrant colorful flowers white yellow red blue arranged gracefully in cardinal directions',
                'camera_motion' => 'pan_right',
                'transition_type' => 'cut',
                'source_id' => $demoSource->id,
                'source_citation' => 'Bhagavad Gita IX.26',
                'status' => 'ready',
            ],
            [
                'scene_order' => 5,
                'start_time' => 24.00,
                'end_time' => 30.00,
                'duration' => 6.00,
                'narration_text' => 'Canang mengingatkan kita: ketulusan memberi tanpa pamrih adalah kebahagiaan batin tertinggi.',
                'onscreen_text' => 'Tulus Memberi, Damai di Jiwa',
                'visual_prompt' => 'Peaceful Balinese youth with hands folded in Anjali Mudra, warm sunrise backdrop, inspiring calm reflection',
                'camera_motion' => 'zoom_out',
                'transition_type' => 'fade',
                'source_id' => $demoSource->id,
                'source_citation' => 'Sarasamuccaya Sloka 14',
                'status' => 'ready',
            ],
        ];

        foreach ($scenes as $scene) {
            $scene['video_id'] = $demoVideo->id;
            StoryboardScene::create($scene);
        }

        // Demo Quiz
        VideoQuiz::create([
            'video_id' => $demoVideo->id,
            'question' => 'Apa makna utama dari unsur "Porosan" (sirih, kapur, pinang) di tengah canang sari?',
            'options' => [
                'Melambangkan keindahan visual semata',
                'Simbol pemujaan Tri Murti (Brahma, Wisnu, Siwa) yang menyatu',
                'Penanda arah mata angin timur dan barat',
                'Penyedap wewangian canang'
            ],
            'correct_option_index' => 1,
            'explanation' => 'Porosan menyatukan sirih (Wisnu), kapur (Siwa), dan pinang (Brahma), melambangkan kesatuan energi pencipta, pemelihara, dan pelebur.',
        ]);

        VideoQuiz::create([
            'video_id' => $demoVideo->id,
            'question' => 'Berdasarkan filosofi canang sari, apa nilai terpenting dalam sebuah persembahan (yadnya)?',
            'options' => [
                'Kemewahan dan ukuran sarana upakara',
                'Banyaknya orang yang menyaksikan ritual',
                'Ketulusan hati dan keikhlasan tanpa pamrih (Lascarya)',
                'Biaya yang dikeluarkan untuk membeli bunga'
            ],
            'correct_option_index' => 2,
            'explanation' => 'Nilai utama persembahan adalah Lascarya (ketulusan murni tanpa pamrih), sebagaimana diajarkan dalam Bhagavad Gita IX.26.',
        ]);
    }
}
