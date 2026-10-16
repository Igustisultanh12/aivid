<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\CartoonPromptProject;
use App\Models\CartoonPromptScene;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CartoonPromptService
{
    public function __construct(
        protected GeminiService $geminiService
    ) {}

    /**
     * Cartoon Visual Style Presets.
     */
    public const VISUAL_STYLES = [
        'pixar_3d' => [
            'id' => 'pixar_3d',
            'name' => '3D Pixar & Disney Animation',
            'badge' => '3D Render',
            'description' => 'Gaya render 3D ekspresif Pixar/Disney, pencahayaan sinematik hangat, kehalusan tekstur kain dan ornamen suci Hindu berkilau.',
            'style_prompt' => '3D Disney Pixar animation style, highly detailed 3D CGI render, expressive large glossy eyes, soft subsurface scattering skin, velvety silk textures, vibrant warm cinematic lighting, raytraced Octane Render aesthetic, sacred Hindu cultural ornament details, masterpiece 4k',
            'negative' => 'photorealistic, realistic live-action human, ugly, deformed, distorted limbs, low quality, glitch, creepy eyes, 2d flat, washed out, morphing',
        ],
        'ghibli_anime' => [
            'id' => 'ghibli_anime',
            'name' => 'Studio Ghibli 2D Anime',
            'badge' => '2D Anime',
            'description' => 'Gaya anime klasik Hayao Miyazaki, lukisan latar cat air rimbun kuil dan pura Hindu yang asri, garis bersih, dan suasana hembusan angin alami.',
            'style_prompt' => 'Studio Ghibli anime style, directed by Hayao Miyazaki, hand-painted lush watercolor background, sacred Balinese Hindu pura temple courtyard, soft expressive cel-shading, delicate ink lineart, warm golden hour atmospheric lighting, floating frangipani petals, cozy nostalgic aesthetic, masterpiece 4k',
            'negative' => '3d cgi render, realistic, glossy plastic, grotesque, dull colors, oversaturated neon, harsh 3d shadows, deformed fingers',
        ],
        'classic_disney_2d' => [
            'id' => 'classic_disney_2d',
            'name' => 'Classic 2D Disney Hand-Drawn',
            'badge' => 'Tradisional 2D',
            'description' => 'Animasi seluloid klasik tahun 90-an dengan goresan luwes, squash & stretch khas kartun bioskop legendaris bertema spiritual.',
            'style_prompt' => 'Classic 1990s golden age traditional Disney 2D animation, hand-drawn character design, expressive cartoon squash-and-stretch anatomy, rich gouache painted temple background, clean ink lines, fluid theatrical animation look, vibrant storybook illustration',
            'negative' => '3d computer generated, hyperrealistic, muddy textures, robotic stiff pose, blurry sketch, vector flat art, flickering',
        ],
        'chibi_3d' => [
            'id' => 'chibi_3d',
            'name' => 'Cute Chibi 3D Stylized',
            'badge' => 'Chibi Gemas',
            'description' => 'Proporsi kepala bulat menggemaskan, mata bulat berkilau, tekstur mainan vinil lembut dengan warna-warna pastel ceria bernuansa suci.',
            'style_prompt' => 'Super cute 3D Chibi style, miniature adorable proportions, oversized head with giant sparkling eyes, smooth soft vinyl toy shaders, pastel vibrant tones, soft ambient occlusion studio lighting, sacred Hindu iconography, kawaii aesthetics, 3D blender cute render, 4k resolution',
            'negative' => 'scary, realistic proportions, gloomy, dark shadows, rough messy sketch, mature gritty look, morphing bodies',
        ],
    ];

    /**
     * Specifically Hindu Story Themes (No fables or non-Hindu fairy tales).
     */
    public const THEMES = [
        'ramayana_mahabharata' => [
            'id' => 'ramayana_mahabharata',
            'name' => 'Kisah Epik Ramayana & Mahabharata Cilik',
            'icon' => 'Sparkles',
            'description' => 'Kisah kepahlawanan dan kesetiaan suci tokoh-tokoh Hindu: Sang Hanuman, Panca Pandawa, Sri Rama, dan Krishna dalam menegakkan panji Dharma.',
            'default_title' => 'Kisah Kesetiaan Sang Hanuman Cilik Mencari Tanaman Obat Sanjiwani',
            'default_character' => 'Sang Hanuman cilik berbulu putih salju kemilau, mengenakan mahkota emas permata dan selendang sutra merah jingga berkilau, berwajah tulus pemberani dengan ekor lentur lincah',
            'default_premise' => 'Hanuman cilik terbang melintasi langit senja dengan gagah berani untuk mencari tanaman obat suci Sanjiwani di tebing Gunung Dronagiri demi menolong sesama yang membutuhkan pertolongan.',
        ],
        'dharma_philosophy' => [
            'id' => 'dharma_philosophy',
            'name' => 'Filosofi Luhur & Budi Pekerti Hindu (Tri Hita Karana & Tri Kaya Parisudha)',
            'icon' => 'Heart',
            'description' => 'Cerita edukatif anak mempraktikkan Tri Kaya Parisudha (berpikir, berkata, dan berbuat suci), kasih sayang pada alam, dan kebaikan Karma Phala.',
            'default_title' => 'Belajar Tri Kaya Parisudha: Kesucian Pikiran, Perkataan, dan Perbuatan',
            'default_character' => 'Pandita cilik berpakaian serba putih suci dengan selendang kuning keemasan, tersenyum teduh penuh welas asih membawa lontar kebajikan',
            'default_premise' => 'Tiga sahabat cilik mempraktikkan Manacika (pikiran bersih), Wacika (tutur kata santun jujur), dan Kayika (tindakan nyata menolong alam dan sesama) di sebuah ashram desa yang damai.',
        ],
        'hindu_traditions' => [
            'id' => 'hindu_traditions',
            'name' => 'Tradisi & Hari Suci Hindu Nusantara (Galungan, Nyepi, Saraswati)',
            'icon' => 'Compass',
            'description' => 'Kemuliaan makna Hari Suci Galungan & Kuningan, keheningan Catur Brata Penyepian, hari turunnya ilmu suci Saraswati, dan keindahan persembahan Canang Sari.',
            'default_title' => 'Kemuliaan Hari Suci Galungan: Kemenangan Dharma Atas Adharma',
            'default_character' => 'Wayan dan Ni Luh cilik berpakaian adat Bali madya serba putih dan kuning anggun, berudeng rapi, membawa canang sari harum bertabur bunga kenanga dan dupa mengepul wangi',
            'default_premise' => 'Keluarga menyambut hari suci Galungan dengan merangkai penjor megah dari janur kelapa di depan pura keluarga, memanjatkan puja syukur atas tegaknya kebajikan di muka bumi.',
        ],
        'deva_wisdom' => [
            'id' => 'deva_wisdom',
            'name' => 'Kisah Kebijaksanaan Dewa & Avatara Hindu',
            'icon' => 'Wand2',
            'description' => 'Kisah ketulusan Dewa Ganesha menghormati orang tua, Sang Garuda mencari Tirta Amerta, dan kebijaksanaan Avatara pelindung alam semesta.',
            'default_title' => 'Kisah Dewa Ganesha Cilik: Kasih Tulus Berbakti kepada Orang Tua',
            'default_character' => 'Sang Ganesha cilik berkepala gajah lucu menggemaskan berbelalai lentur, bermahkota keemasan megah, memegang mangkuk modak manis, berhati bijak penuh senyuman',
            'default_premise' => 'Ganesha cilik membuktikan bahwa mengelilingi kedua orang tuanya (Dewa Siwa dan Dewi Parwati) dengan rasa hormat dan cinta tulus sama mulianya dengan mengelilingi seluruh alam semesta.',
        ],
        'custom_hindu' => [
            'id' => 'custom_hindu',
            'name' => 'Tema & Cerita Hindu Pilihan Sendiri',
            'icon' => 'Film',
            'description' => 'Tuliskan topik cerita, itihasa, purana, atau nilai kebajikan Hindu khusus yang ingin Anda jadikan naskah video kartun.',
            'default_title' => 'Cerita Ajaran Suci Hindu Pilihan',
            'default_character' => 'Karakter kesatria cilik berbudi luhur yang memegang teguh ajaran Dharma',
            'default_premise' => 'Kisah edukatif anak yang mengangkat nilai luhur ajaran suci Hindu dan kebajikan universal bagi kedamaian dunia.',
        ],
    ];

    /**
     * Target AI Video Generator syntax engine presets.
     */
    public const TARGET_ENGINES = [
        'universal' => [
            'id' => 'universal',
            'name' => 'Universal (Kling, Runway, Sora, Luma, Pika)',
            'description' => 'Sintaks prompt seimbang yang kompatibel untuk seluruh platform AI Video generasi terbaru.',
            'suffix' => ', highly dynamic cartoon animation motion, smooth continuous action, fluid 24fps character movement, 4k ultra detailed cinematic sequence',
        ],
        'kling_ai' => [
            'id' => 'kling_ai',
            'name' => 'Kling AI (v1.5 / v2.0)',
            'description' => 'Dioptimalkan untuk konsistensi gerakan karakter Kling AI, instruksi kamera dinamis, dan kestabilan pose.',
            'suffix' => ', cinematic high framerate character action, coherent physics motion, vibrant cartoon anime aesthetic, Masterpiece quality, Kling AI render',
        ],
        'runway_gen3' => [
            'id' => 'runway_gen3',
            'name' => 'Runway Gen-3 Alpha',
            'description' => 'Menyertakan kata kunci directional kamera (dolly, pedestal, pan) dan dynamic lighting khas Runway.',
            'suffix' => ', cinematic volumetric lighting, dynamic motion blur, hyper-expressive character facial animation, professional studio cinematic render, Gen-3 Alpha quality',
        ],
        'luma_dream_machine' => [
            'id' => 'luma_dream_machine',
            'name' => 'Luma Dream Machine',
            'description' => 'Menonjolkan kinetika momentum alami, kelembutan pergerakan objek, dan kedalaman dimensional 3D.',
            'suffix' => ', natural physical kinetic momentum, rich character animation keyframing, wide dynamic range lighting, Dream Machine render',
        ],
        'sora' => [
            'id' => 'sora',
            'name' => 'OpenAI Sora / Haiper',
            'description' => 'Fokus pada narasi temporal yang mengalir, interaksi karakter dengan lingkungan, dan detail render tinggi.',
            'suffix' => ', continuous smooth camera motion, photorealistic cartoon physics, high fidelity environmental interaction, 4k 60fps presentation',
        ],
    ];

    /**
     * Camera motion presets for cartoon frames.
     */
    public const CAMERA_MOTIONS = [
        'establishing_wide' => 'Cinematic wide establishing shot, gentle slow pan left, revealing the sacred temple architecture',
        'dynamic_push_in' => 'Dynamic low-angle push-in zoom focusing on character action and sacred symbols',
        'tracking_run' => 'Smooth horizontal tracking shot following character movement with natural momentum',
        'close_up_reaction' => 'Expressive close-up shot with subtle camera tilt up, shallow depth of field f/2.2',
        'crane_high_angle' => 'High-angle crane shot descending into the sacred courtyard, atmospheric incense smoke',
        'dolly_zoom_climax' => 'Dramatic slow dolly zoom capturing the noble spiritual climax with radiant aura',
        'orbit_around' => 'Smooth 45-degree orbit around the main character with warm golden rim illumination',
        'slow_zoom_out' => 'Peaceful slow zoom out revealing the harmonious sacred landscape at sunset, Om Shanti',
    ];

    /**
     * Normalize theme keys to Hindu-only themes.
     */
    public static function normalizeThemeKey(string $themeKey): string
    {
        $map = [
            'fable' => 'ramayana_mahabharata',
            'comedy' => 'hindu_traditions',
            'sci_fi' => 'deva_wisdom',
            'fantasy' => 'deva_wisdom',
            'anime_chibi' => 'ramayana_mahabharata',
            'dharma_kids' => 'dharma_philosophy',
            'custom' => 'custom_hindu',
        ];

        return $map[$themeKey] ?? (isset(self::THEMES[$themeKey]) ? $themeKey : 'ramayana_mahabharata');
    }

    /**
     * Generate structured cartoon video prompts.
     */
    public function generatePrompts(array $validated): array
    {
        $duration = (int) ($validated['target_duration'] ?? 30);
        $duration = in_array($duration, [30, 60], true) ? $duration : 30;

        $rawThemeKey = $validated['theme'] ?? 'ramayana_mahabharata';
        $themeKey = self::normalizeThemeKey($rawThemeKey);
        $themeMeta = self::THEMES[$themeKey] ?? self::THEMES['ramayana_mahabharata'];

        $styleKey = $validated['visual_style'] ?? 'pixar_3d';
        $styleMeta = self::VISUAL_STYLES[$styleKey] ?? self::VISUAL_STYLES['pixar_3d'];

        $engineKey = $validated['target_engine'] ?? 'universal';
        $engineMeta = self::TARGET_ENGINES[$engineKey] ?? self::TARGET_ENGINES['universal'];

        $aspectRatio = $validated['aspect_ratio'] ?? '9:16';

        $title = !empty($validated['title'])
            ? trim($validated['title'])
            : (!empty($validated['custom_theme_title']) ? trim($validated['custom_theme_title']) : $themeMeta['default_title']);

        $character = !empty($validated['character_description'])
            ? trim($validated['character_description'])
            : $themeMeta['default_character'];

        $premise = !empty($validated['story_premise'])
            ? trim($validated['story_premise'])
            : $themeMeta['default_premise'];

        // Check if Gemini AI is configured
        $geminiKey = $this->geminiService->getApiKey();

        if ($geminiKey) {
            $aiResult = $this->generateWithGemini(
                $geminiKey,
                $duration,
                $themeKey,
                $title,
                $premise,
                $character,
                $styleMeta,
                $engineMeta,
                $aspectRatio
            );

            if ($aiResult['success'] && !empty($aiResult['scenes'])) {
                return $aiResult;
            }
            Log::info('Gemini prompt generation returned fallback. Generating procedural Hindu cartoon sequence.');
        }

        // Procedural generator engine (100% reliable, zero error, tailored for Hindu stories with strict continuity)
        return $this->generateProceduralCartoonSequence(
            $duration,
            $themeKey,
            $title,
            $premise,
            $character,
            $styleMeta,
            $engineMeta,
            $aspectRatio
        );
    }

    /**
     * Generate prompts using Gemini 3.8 Flash LLM with strict Hindu story rules and frame-to-frame continuity.
     */
    protected function generateWithGemini(
        string $geminiKey,
        int $duration,
        string $themeKey,
        string $title,
        string $premise,
        string $character,
        array $styleMeta,
        array $engineMeta,
        string $aspectRatio
    ): array {
        $sceneCount = $duration === 30 ? 6 : 12;
        $frameDuration = 5.0; // 5 seconds per frame

        $prompt = <<<PROMPT
Anda adalah Sutradara Animasi dan Ahli Prompt Video AI Khusus Konten Edukasi Spiritual Hindu ("Ruang Dharma").
Tugas Anda adalah merancang rangkaian prompt video per-frame/per-adegan (action breakdown) untuk membuat video kartun anak edukatif berdurasi TEPAT {$duration} DETIK.

ATURAN UTAMA & WAJIB (STRICT REQUIREMENTS):
1. KONTEN KHUSUS HINDU (TIDAK BOLEH fabel hewan biasa, dongeng umum, robot sci-fi, atau komedi tanpa nilai Dharma):
   - Wajib mengangkat ajaran suci, filosofi, tradisi, atau epik Hindu (Ramayana, Mahabharata, Tri Hita Karana, Tri Kaya Parisudha, Karma Phala, Hari Suci Galungan/Nyepi/Saraswati, Dewa Ganesha, Sang Hanuman).
   - Nilai moral berlandaskan Dharma (kebenaran), Satya (kejujuran), Ahimsa (tanpa kekerasan), dan Bhakti (cinta kasih suci).

2. KONTINUITAS KETAT FRAME-DEMI-FRAME (SEAMLESS NARRATIVE & TEMPORAL CONTINUITY):
   - Alur cerita dari Frame 1 sampai Frame {$sceneCount} HARUS 100% MENYAMBUNG dan mengalir tanpa melompat.
   - Akhir pergerakan di frame sebelumnya harus menjadi titik awal frame berikutnya (match-cut action).
   - Pembicaraan (narasi/dialog) harus selaras dan tersinkronisasi sempurna dengan apa yang dilakukan karakter pada detik tersebut.

3. KONSISTENSI VISUAL ANCHOR TINGGI:
   - Karakter utama "{$character}" WAJIB diulang dengan atribut pakaian, warna busana adat, wajah, dan aksesoris yang persis sama di setiap visual_prompt.
   - Setting lokasi (Pura, candi, ashram, atau gunung suci) harus konsisten dari frame ke frame.

4. SYNTAX PROMPT AI VIDEO LENGKAP:
   - visual_prompt HARUS dalam BAHASA INGGRIS tingkat tinggi yang lengkap untuk model video AI ({$engineMeta['name']}).
   - Struktur prompt visual:
     "[Frame X of {$sceneCount}: Continuous shot] {$styleMeta['style_prompt']}. Character Anchor: {$character}. Exact Kinetic Action: (deskripsi pergerakan fisik karakter secara mendalam). Sacred Setting: (latar kuil/pura/lingkungan suci Hindu). Camera: (pergerakan kamera, 35mm lens, f/2.4, 24fps motion). Lighting: (warm sacred golden hour lighting, volumetric rays). {$styleMeta['style_prompt']}{$engineMeta['suffix']}"

PARAMETER PROYEK:
- Judul: "{$title}"
- Tema: {$themeKey}
- Premis: "{$premise}"
- Karakter: "{$character}"
- Gaya: {$styleMeta['name']}
- Rasio: {$aspectRatio}
- Durasi: TEPAT {$duration} DETIK ({$sceneCount} frame @ {$frameDuration}s).

Kembalikan jawaban HANYA dalam JSON valid:
{
  "title": "{$title}",
  "story_premise": "{$premise}",
  "character_description": "{$character}",
  "target_duration": {$duration},
  "total_duration": {$duration},
  "scene_count": {$sceneCount},
  "scenes": [
    {
      "scene_number": 1,
      "start_second": 0.0,
      "end_second": 5.0,
      "duration": 5.0,
      "title": "Nama Adegan 1",
      "action_description": "Deskripsi aksi karakter dalam bahasa Indonesia yang menyambung",
      "visual_prompt": "English visual prompt with complete continuity, character anchor, kinetic action, camera motion, and style tokens",
      "camera_movement": "Cinematic wide establishing shot, gentle slow pan right",
      "dialogue_or_narration": "Dialog atau narasi yang menyambung dengan tema Hindu",
      "sound_effects": "*Temple bell chime!* *Gentle breeze!*",
      "music_mood": "Sacred peaceful gamelan suling and orchestral blend",
      "negative_prompt": "{$styleMeta['negative']}"
    }
  ]
}
PROMPT;

        try {
            $result = $this->callGeminiApi($geminiKey, $prompt);

            if ($result && !empty($result['scenes']) && is_array($result['scenes'])) {
                $this->realignSceneTimestamps($result['scenes'], $duration, $sceneCount);
                return [
                    'success' => true,
                    'title' => $result['title'] ?? $title,
                    'story_premise' => $result['story_premise'] ?? $premise,
                    'character_description' => $result['character_description'] ?? $character,
                    'target_duration' => $duration,
                    'total_duration' => (float) $duration,
                    'scene_count' => count($result['scenes']),
                    'scenes' => $result['scenes'],
                    'engine_info' => $engineMeta['name'],
                    'style_info' => $styleMeta['name'],
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini call in CartoonPromptService error: ' . $e->getMessage());
        }

        return ['success' => false];
    }

    /**
     * Direct HTTP call helper to Gemini API for prompt generation.
     */
    protected function callGeminiApi(string $key, string $promptText): ?array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.8-flash:generateContent";

        $response = \Illuminate\Support\Facades\Http::timeout(90)
            ->withHeaders([
                'x-goog-api-key' => $key,
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $promptText]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'maxOutputTokens' => 8192,
                ],
            ]);

        if ($response->successful()) {
            $text = $response->json('candidates.0.content.parts.0.text', '');
            $text = trim($text);
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
            $decoded = json_decode($text, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Creative Procedural Hindu Cartoon Sequence Generator with strict continuity.
     * Guaranteed 100% interconnected frame-by-frame storyline without error.
     */
    public function generateProceduralCartoonSequence(
        int $duration,
        string $themeKey,
        string $title,
        string $premise,
        string $character,
        array $styleMeta,
        array $engineMeta,
        string $aspectRatio
    ): array {
        $sceneCount = $duration === 30 ? 6 : 12;
        $frameDuration = 5.0;

        $themeTemplates = $this->getHinduThemeNarrativeBank($themeKey, $title, $premise, $character);

        $scenes = [];
        $actionsBank = $duration === 30 ? $themeTemplates['actions_30s'] : $themeTemplates['actions_60s'];

        $cameraCycle = array_values(self::CAMERA_MOTIONS);

        for ($i = 0; $i < $sceneCount; $i++) {
            $startSec = round($i * $frameDuration, 2);
            $endSec = round(($i + 1) * $frameDuration, 2);

            $actionData = $actionsBank[$i] ?? [
                'title' => "Frame " . sprintf('%02d', $i + 1) . ": Kelanjutan Dharma",
                'action_id' => "Karakter melanjutkan langkah bajiknya dengan penuh keteguhan hati di jalan Dharma.",
                'action_en' => "The character steps forward gracefully in adherence to sacred Dharma, glowing aura surrounding them.",
                'dialogue' => "Kebaikan yang kita tanam hari ini akan berbuah kedamaian esok hari.",
                'sfx' => "*Gentle temple bell chime!*",
                'music' => "Peaceful Balinese gamelan suling and gentle strings",
            ];

            $cameraMotion = $cameraCycle[$i % count($cameraCycle)];

            // Highly detailed industry-standard AI video prompt with continuous framing tokens
            $visualPrompt = sprintf(
                "[Frame %d of %d: Continuous sequence action from previous shot] %s. Character Anchor: %s. Action Dynamics: %s. Sacred Setting: %s, %s. Cinematography: %s, 35mm cinematic lens, f/2.4 shallow depth of field, fluid 24fps motion. Lighting: %s. %s%s",
                $i + 1,
                $sceneCount,
                $styleMeta['style_prompt'],
                $character,
                $actionData['action_en'],
                $themeTemplates['environment'],
                $aspectRatio === '9:16' ? 'vertical 9:16 portrait composition for mobile screens' : ($aspectRatio === '16:9' ? 'cinematic 16:9 widescreen composition' : 'square 1:1 format'),
                $cameraMotion,
                $actionData['lighting'] ?? 'sacred golden hour morning sunlight, atmospheric soft incense smoke and drifting petals',
                $styleMeta['style_prompt'],
                $engineMeta['suffix']
            );

            $scenes[] = [
                'scene_number' => $i + 1,
                'start_second' => $startSec,
                'end_second' => $endSec,
                'duration' => $frameDuration,
                'title' => $actionData['title'],
                'action_description' => $actionData['action_id'],
                'visual_prompt' => $visualPrompt,
                'camera_movement' => $cameraMotion,
                'dialogue_or_narration' => $actionData['dialogue'],
                'sound_effects' => $actionData['sfx'],
                'music_mood' => $actionData['music'],
                'negative_prompt' => $styleMeta['negative'],
            ];
        }

        return [
            'success' => true,
            'title' => $title,
            'story_premise' => $premise,
            'character_description' => $character,
            'target_duration' => $duration,
            'total_duration' => (float) $duration,
            'scene_count' => $sceneCount,
            'scenes' => $scenes,
            'engine_info' => $engineMeta['name'],
            'style_info' => $styleMeta['name'],
        ];
    }

    /**
     * Regenerate a single scene prompt with creative variations matching Hindu spirituality.
     */
    public function regenerateSingleScene(CartoonPromptProject $project, CartoonPromptScene $scene): CartoonPromptScene
    {
        $styleMeta = self::VISUAL_STYLES[$project->visual_style] ?? self::VISUAL_STYLES['pixar_3d'];
        $engineMeta = self::TARGET_ENGINES[$project->target_engine] ?? self::TARGET_ENGINES['universal'];
        $aspectRatio = $project->aspect_ratio ?? '9:16';

        $cameraMotions = array_values(self::CAMERA_MOTIONS);
        $randomCamera = $cameraMotions[array_rand($cameraMotions)];

        $variations = [
            [
                'title' => 'Momen Kebaktian & Doa Suci (Bhakti)',
                'action_id' => 'Karakter menangkupkan kedua telapak tangan di depan dada dalam sikap Anjali Mudra, memancarkan aura ketenangan dan rasa syukur yang mendalam.',
                'action_en' => 'The character gracefully folds hands together in sacred Anjali Mudra at heart level, eyes closed peacefully with a gentle sincere smile, glowing golden aura radiating.',
                'sfx' => '*Resonant temple bell chime!* *Soft gong sound!*',
                'music' => 'Meditative Balinese gamelan suling with celestial flute harmony',
            ],
            [
                'title' => 'Langkah Berani Menegakkan Kebenaran (Dharma)',
                'action_id' => 'Karakter melangkah mantap ke depan dengan sorot mata penuh ketulusan dan tekad luhur, kain selendangnya berkibar ditiup angin sepoi-sepoi.',
                'action_en' => 'The character strides forward with determined posture and eyes full of noble devotion, silk sash fluttering in the mountain breeze, sacred temple background.',
                'sfx' => '*Wind whoosh!* *Chime sparkle!*',
                'music' => 'Uplifting cinematic orchestral gamelan swell',
            ],
            [
                'title' => 'Persembahan Tulus Canang Sari',
                'action_id' => 'Karakter meletakkan canang sari harum berisi bunga kamboja segar ke atas pelinggih batu suci dengan kedua tangan penuh hormat.',
                'action_en' => 'The character gently places a fragrant woven palm leaf canang sari with colorful blossoms onto a carved volcanic stone shrine with utmost respect.',
                'sfx' => '*Gentle incense waft sound!* *Water droplet sparkle!*',
                'music' => 'Serene bamboo bell chimes and warm string harmony',
            ],
            [
                'title' => 'Pancaran Kedamaian Universal (Shanti)',
                'action_id' => 'Karakter tersenyum teduh memandang fajar keemasan yang menyinari pura, kelopak bunga melati dan dupa melayang menenangkan jiwa.',
                'action_en' => 'The character gazes in peaceful wonder at the radiant golden sunrise bathing the temple gate, jasmine petals and fragrant mist curling softly.',
                'sfx' => '*Morning bird song!* *Temple bell toll!*',
                'music' => 'Profoundly peaceful sacred flute melody, Om Shanti',
            ],
        ];

        $var = $variations[array_rand($variations)];

        $newVisualPrompt = sprintf(
            "[Frame %d of %d: Continuity variation] %s. Character Anchor: %s. Action Dynamics: %s. Sacred Setting: Sacred Balinese Hindu pura temple courtyard with volcanic stone carvings, %s. Cinematography: %s, 35mm lens, f/2.4 depth of field, 24fps. Lighting: Sacred golden hour warm sunlight, volumetric incense rays. %s%s",
            $scene->scene_number,
            $project->scene_count,
            $styleMeta['style_prompt'],
            $project->character_description ?: 'Virtuous young Hindu character in traditional sacred attire',
            $var['action_en'],
            $aspectRatio === '9:16' ? 'vertical 9:16 portrait composition' : 'widescreen 16:9 composition',
            $randomCamera,
            $styleMeta['style_prompt'],
            $engineMeta['suffix']
        );

        $scene->update([
            'title' => $var['title'],
            'action_description' => $var['action_id'],
            'visual_prompt' => $newVisualPrompt,
            'camera_movement' => $randomCamera,
            'sound_effects' => $var['sfx'],
            'music_mood' => $var['music'],
            'negative_prompt' => $styleMeta['negative'],
        ]);

        return $scene->fresh();
    }

    /**
     * Ensure correct timestamps sequentially.
     */
    protected function realignSceneTimestamps(array &$scenes, int $targetDuration, int $sceneCount): void
    {
        $step = round($targetDuration / max(count($scenes), 1), 2);
        $current = 0.0;

        foreach ($scenes as $index => &$scene) {
            $scene['scene_number'] = $index + 1;
            $scene['start_second'] = round($current, 2);
            $end = ($index === count($scenes) - 1) ? (float) $targetDuration : round($current + $step, 2);
            $scene['end_second'] = $end;
            $scene['duration'] = round($end - $current, 2);
            $current = $end;
        }
    }

    /**
     * Purely Hindu Narrative Bank with High Temporal & Plot Continuity.
     */
    protected function getHinduThemeNarrativeBank(string $themeKey, string $title, string $premise, string $character): array
    {
        switch ($themeKey) {
            case 'ramayana_mahabharata':
                return [
                    'environment' => 'sacred Mount Dronagiri foothills with lush medicinal flora, misty cliffs, golden sunbeams, and traditional ancient Hindu stone carved reliefs',
                    'actions_30s' => [
                        [
                            'title' => 'Frame 01 (00:00 - 00:05): Hook - Hanuman Siap Meluncur',
                            'action_id' => 'Hanuman cilik berdiri gagah di puncak batu karang Gunung Gandhamadana, merapatkan kedua telapak tangan dalam sikap hormat, lalu merentangkan tangannya bersiap terbang.',
                            'action_en' => 'Little Hanuman stands heroically atop a jagged stone pinnacle, hands folded in brief reverent Anjali Mudra prayer, then spreads arms wide with wind billowing his saffron sash as he coils his legs to leap.',
                            'dialogue' => 'Demi kesetiaan pada Sri Rama dan pertolongan sesama, aku akan membawa tanaman Sanjiwani!',
                            'sfx' => '*Wind gust whoosh!* *Sacred conch shell call in distance!*',
                            'music' => 'Epic heroic orchestral gamelan overture with powerful drums',
                        ],
                        [
                            'title' => 'Frame 02 (00:05 - 00:10): Terbang Melintasi Samudra Awan',
                            'action_id' => '[Menyambung langsung dari Frame 1] Hanuman melesat tinggi menembus kabut awan emas, meluncur secepat kilat dengan pandangan jeli mencari lereng gunung yang bercahaya.',
                            'action_en' => '[Continues directly from Frame 1] Hanuman soars through golden billowing clouds at lightning speed, white fur glistening in morning light, eyes focused forward scanning the peaks below.',
                            'dialogue' => 'Di sana lereng Gunung Dronagiri! Cahaya suci tanaman obat itu mulai terlihat.',
                            'sfx' => '*High-speed wind roar!* *Divine shimmer sound!*',
                            'music' => 'Dynamic fast-paced heroic flight rhythm with soaring brass and flute',
                        ],
                        [
                            'title' => 'Frame 03 (00:10 - 00:15): Mendarat di Tebing Tanaman Suci',
                            'action_id' => '[Menyambung langsung dari Frame 2] Hanuman mendarat dengan anggun di teras tebing bebatuan, debu suci terpencar lembut saat kakinya menjejak tanah berbunga keemasan.',
                            'action_en' => '[Continues directly from Frame 2] Hanuman descends gracefully onto a mossy cliff ledge, landing softly on one knee, glowing golden Sanjiwani herbs shimmering in the rock crevices around him.',
                            'dialogue' => 'Berkat dewata, tanaman obat suci ini tumbuh subur dan bercahaya!',
                            'sfx' => '*Soft landing thump!* *Magical crystal sparkle!*',
                            'music' => 'Wondrous emotional melody with temple bells and harp',
                        ],
                        [
                            'title' => 'Frame 04 (00:15 - 00:20): Memohon Izin dengan Ketulusan Hati',
                            'action_id' => '[Menyambung langsung dari Frame 3] Menghindari keserakahan, Hanuman berlutut penuh hormat memohon izin kepada Dewa penjaga alam sebelum memetik tanaman penyembuh.',
                            'action_en' => '[Continues directly from Frame 3] Hanuman kneels reverently before the glowing flora, bowing his head in sincere humility, asking permission with utmost devotion rather than taking by force.',
                            'dialogue' => 'Wahai Hyang Giri, izinkan hamba memetik anugerah ini demi menyelamatkan nyawa saudara kita.',
                            'sfx' => '*Divine choral hum!* *Gentle breeze rustling leaves!*',
                            'music' => 'Sacred spiritual choir and serene Balinese bamboo suling',
                        ],
                        [
                            'title' => 'Frame 05 (00:20 - 00:25): Mengangkat Puncak Tebing Penuh Berkah',
                            'action_id' => '[Menyambung langsung dari Frame 4] Seluruh lereng memancarkan cahaya berkah hangat; Hanuman mengangkat bukit kecil penyembuh tersebut dengan senyuman gembira.',
                            'action_en' => '[Continues directly from Frame 4] Radiant divine golden aura envelopes the cliff; Hanuman gently hoists the sacred herbal mound on his palm, smiling with radiant purity and joyous strength.',
                            'dialogue' => 'Doa yang tulus selalu membuka jalan kebajikan dan keajaiban!',
                            'sfx' => '*Majestic power chime!* *Harmonic ringing tone!*',
                            'music' => 'Triumphant glorious orchestral crescendo with triumphant trumpets',
                        ],
                        [
                            'title' => 'Frame 06 (00:25 - 00:30): Resolusi & Salam Kedamaian Dharma',
                            'action_id' => '[Menyambung langsung dari Frame 5] Hanuman terbang kembali ke arah penonton sambil tersenyum tulus mengatupkan satu tangan di dada, pelangi suci membentang di langit senja.',
                            'action_en' => '[Continues directly from Frame 5] Hanuman glides smoothly toward the camera over a majestic rainbow mist, nodding with a peaceful heroic smile, hands held in peace gesture.',
                            'dialogue' => 'Tegakkan Dharma dengan ketulusan dan keberanian. Om Shanti Shanti Shanti Om.',
                            'sfx' => '*Resonant temple bell finale!* *Peaceful chime reverberation!*',
                            'music' => 'Deeply moving sacred Balinese gamelan and choir finale',
                        ],
                    ],
                    'actions_60s' => $this->buildTwelveHinduSceneSequence('ramayana_mahabharata', $character),
                ];

            case 'dharma_philosophy':
                return [
                    'environment' => 'peaceful traditional Balinese Hindu ashram village with banyan trees, lotus pond, carved stone shrines, and golden morning sunlight',
                    'actions_30s' => [
                        [
                            'title' => 'Frame 01 (00:00 - 00:05): Hook - Pagi Suci di Pelataran Ashram',
                            'action_id' => 'Pandita cilik duduk bersila tenang di bale bengong dekat kolam teratai, menghirup udara pagi yang segar sembari mengatupkan tangan dalam puja Tri Sandhya.',
                            'action_en' => 'Young pandita boy sits cross-legged in peaceful meditation beside a blooming lotus pond, hands held gracefully in Anjali Mudra greeting the morning sun.',
                            'dialogue' => 'Pagi yang damai. Mari kita mulai hari ini dengan kesucian Tri Kaya Parisudha.',
                            'sfx' => '*Gentle water ripple!* *Soft bronze bell chime!*',
                            'music' => 'Serene meditative bamboo flute (suling) and soft harp',
                        ],
                        [
                            'title' => 'Frame 02 (00:05 - 00:10): Manacika - Kesucian Pikiran',
                            'action_id' => '[Menyambung langsung dari Frame 1] Pandita cilik membuka mata dengan tatapan jernih, memandang sekuntum teratai putih yang mekar tanpa terkotori lumpur.',
                            'action_en' => '[Continues directly from Frame 1] The boy opens eyes with a tranquil expression, gently gazing at a pure white lotus blossom rising unstained from pond waters.',
                            'dialogue' => 'Pertama: Manacika Parisudha. Menjaga pikiran tetap bersih, tanpa iri hati atau amarah.',
                            'sfx' => '*Water droplet plop!* *Wind chime whisper!*',
                            'music' => 'Gentle warm acoustic gamelan progression',
                        ],
                        [
                            'title' => 'Frame 03 (00:10 - 00:15): Wacika - Kesucian Perkataan',
                            'action_id' => '[Menyambung langsung dari Frame 2] Bangkit melangkah ke jalan setapak, pandita cilik menyapa sahabatnya yang sedang belajar dengan senyuman dan sapaan santun.',
                            'action_en' => '[Continues directly from Frame 2] The boy steps onto a cobblestone path, warmly greeting a young friend reading palm-leaf scriptures with a kind smile and gentle bow.',
                            'dialogue' => 'Kedua: Wacika Parisudha. Berkata jujur, menyejukkan hati, dan tidak menyakiti sesama.',
                            'sfx' => '*Light footsteps on stone!* *Friendly soft greeting voice!*',
                            'music' => 'Playful uplifting strings and xylophone harmony',
                        ],
                        [
                            'title' => 'Frame 04 (00:15 - 00:20): Kayika - Perbuatan Luhur Nyata',
                            'action_id' => '[Menyambung langsung dari Frame 3] Keduanya melihat anak burung yang terjatuh dari dahan pohon kamboja, lalu bersama-sama mengangkatnya dengan lembut kembali ke sarang.',
                            'action_en' => '[Continues directly from Frame 3] Both children spot a little bird on the grass, carefully cradling it with both hands and safely returning it to its nest in the frangipani branch.',
                            'dialogue' => 'Ketiga: Kayika Parisudha. Perbuatan baik nyata, mengasihi semua makhluk ciptaan Tuhan.',
                            'sfx' => '*Bird gentle chirp!* *Leaves rustling softly!*',
                            'music' => 'Heartwarming inspiring orchestral swell with acoustic guitar',
                        ],
                        [
                            'title' => 'Frame 05 (00:20 - 00:25): Buah Manis Hukum Karma Phala',
                            'action_id' => '[Menyambung langsung dari Frame 4] Pohon kamboja bergoyang lembut menjatuhkan bunga-bunga harum, anak burung berkicau riang memancarkan pendar kehangatan.',
                            'action_en' => '[Continues directly from Frame 4] Frangipani blossoms shower gently over the children, golden sunlight filtering through leaves, reflecting the joy of selfless virtue.',
                            'dialogue' => 'Hukum Karma Phala mengajarkan: siapa menanam kebajikan, pasti memetik kebahagiaan.',
                            'sfx' => '*Flower shower flutter!* *Golden harmonic bell!*',
                            'music' => 'Lush joyful gamelan orchestral crescendo',
                        ],
                        [
                            'title' => 'Frame 06 (00:25 - 00:30): Penutup - Doa Kedamaian Semesta',
                            'action_id' => '[Menyambung langsung dari Frame 5] Kedua anak berdiri berdampingan mengatupkan tangan ke arah kamera, tersenyum damai saat latar pura disinari fajar keemasan.',
                            'action_en' => '[Continues directly from Frame 5] Both children stand side by side, bowing politely with palms pressed together, radiant warm smile towards viewers.',
                            'dialogue' => 'Jadilah pribadi yang suci pikiran, kata, dan perbuatan. Om Shanti Shanti Shanti Om.',
                            'sfx' => '*Resonant temple gong chime!* *Sacred chime finish!*',
                            'music' => 'Deep spiritual chorus and Balinese suling finale',
                        ],
                    ],
                    'actions_60s' => $this->buildTwelveHinduSceneSequence('dharma_philosophy', $character),
                ];

            case 'hindu_traditions':
                return [
                    'environment' => 'vibrant Balinese village street lined with majestic curved penjor bamboo poles decorated with yellow young coconut leaves (janur) leading to a grand candi bentar temple gate',
                    'actions_30s' => [
                        [
                            'title' => 'Frame 01 (00:00 - 00:05): Hook - Semarak Hari Raya Galungan',
                            'action_id' => 'Wayan cilik menatap takjub deretan penjor bambu tinggi melengkung yang dihiasi janur kuning keemasan berkilauan di sepanjang jalan desa.',
                            'action_en' => 'Little Wayan dressed in immaculate white traditional Balinese safari shirt and yellow udeng headcloth gazes up in wonder at majestic curved penjor poles lining the stone village road.',
                            'dialogue' => 'Rahajeng Rahina Galungan! Hari kemenangan Dharma atas Adharma telah tiba.',
                            'sfx' => '*Festive temple gong chime!* *Breeze fluttering coconut palm leaves!*',
                            'music' => 'Celebratory traditional Balinese gamelan Gong Kebyar with bright brass',
                        ],
                        [
                            'title' => 'Frame 02 (00:05 - 00:10): Menghaturkan Canang Sari Harum',
                            'action_id' => '[Menyambung langsung dari Frame 1] Ni Luh mendekat membawa baki anyaman bambu berisi canang sari segar bertabur bunga kenanga, kamboja, dan pacar air.',
                            'action_en' => '[Continues directly from Frame 1] Little sister Ni Luh steps up carrying a woven bamboo tray of fresh fragrant canang sari offerings filled with colorful flowers and burning incense stick.',
                            'dialogue' => 'Setiap kelopak bunga pada Canang Sari melambangkan rasa syukur dan ketulusan hati kita.',
                            'sfx' => '*Incense fragrant crackle!* *Footsteps on stone path!*',
                            'music' => 'Gentle harmonious gamelan melody with warm bells',
                        ],
                        [
                            'title' => 'Frame 03 (00:10 - 00:15): Meletakkan Sesaji di Pelinggih Suci',
                            'action_id' => '[Menyambung langsung dari Frame 2] Keduanya bersama-sama meletakkan canang sari ke atas pelinggih batu berukir di depan gapura pura keluarga.',
                            'action_en' => '[Continues directly from Frame 2] Both children place the canang sari with both hands onto a black volcanic stone family temple shrine adorned with yellow sacred cloth.',
                            'dialogue' => 'Kami haturkan rasa syukur atas berkah kesehatan, kedamaian, dan keselamatan hidup.',
                            'sfx' => '*Soft shrine placement sound!* *Wafting incense smoke!*',
                            'music' => 'Solemn peaceful acoustic gamelan suling and strings',
                        ],
                        [
                            'title' => 'Frame 04 (00:15 - 00:20): Percikan Tirta Suci Penyucian',
                            'action_id' => '[Menyambung langsung dari Frame 3] Pandita sepuh memercikkan air suci (Tirta) menggunakan pucuk daun ilalang ke kepala anak-anak yang menunduk khidmat.',
                            'action_en' => '[Continues directly from Frame 3] An elder priest in white sprinkles glistening holy water (Tirta) using sacred grass onto the children\'s heads, water droplets glowing in sunlight.',
                            'dialogue' => 'Tirta suci membersihkan pikiran, perkataan, dan raga kita agar senantiasa teguh di jalan kebenaran.',
                            'sfx' => '*Water droplet splash chime!* *Priest soft sacred mantra chant!*',
                            'music' => 'Transcendent spiritual chorus with sacred bell resonance',
                        ],
                        [
                            'title' => 'Frame 05 (00:20 - 00:25): Biji Beras Bija di Kening Kesucian',
                            'action_id' => '[Menyambung langsung dari Frame 4] Wayan dan Ni Luh saling tersenyum bahagia dengan butiran beras suci (Bija) terpasang di dahi mereka, melambangkan kemakmuran batin.',
                            'action_en' => '[Continues directly from Frame 4] The children smile joyously at each other with sacred raw rice grains (Bija) pressed onto their foreheads and throats, radiating spiritual bliss.',
                            'dialogue' => 'Hati terasa begitu damai dan tentram ketika kita senantiasa bersyukur.',
                            'sfx' => '*Chime twinkle!* *Happy laughter echo!*',
                            'music' => 'Joyful celebratory gamelan orchestral harmony',
                        ],
                        [
                            'title' => 'Frame 06 (00:25 - 00:30): Resolusi & Salam Rahajeng Galungan',
                            'action_id' => '[Menyambung langsung dari Frame 5] Keduanya mengatupkan tangan di depan candi bentar pura megah, mengucapkan salam damai bagi seluruh umat manusia.',
                            'action_en' => '[Continues directly from Frame 5] The children fold their hands in formal prayer greeting before the towering stone temple split-gate, warm sunset glowing behind the penjor poles.',
                            'dialogue' => 'Semoga Dharma senantiasa menang dalam diri kita semua. Om Shanti Shanti Shanti Om.',
                            'sfx' => '*Grand temple gong toll!* *Evening breeze whoosh!*',
                            'music' => 'Grand majestic Balinese gamelan finale with deep brass resonance',
                        ],
                    ],
                    'actions_60s' => $this->buildTwelveHinduSceneSequence('hindu_traditions', $character),
                ];

            default: // deva_wisdom or custom_hindu
                return [
                    'environment' => 'heavenly sacred Mount Kailash landscape with golden pavilions, blooming blue lotus lakes, and divine radiant cloud formations',
                    'actions_30s' => [
                        [
                            'title' => 'Frame 01 (00:00 - 00:05): Hook - Kebijaksanaan Sang Ganesha Cilik',
                            'action_id' => 'Ganesha cilik berkepala gajah lucu tersenyum cerdas memegang mangkuk modak manis, memandang saudaranya Kartikeya yang sedang menunggangi burung merak.',
                            'action_en' => 'Little Lord Ganesha with cute elephant head and golden jewel crown smiles warmly holding a bowl of sweet golden modak sweets, watching brother Kartikeya prepare on his peacock.',
                            'dialogue' => 'Siapapun yang mengelilingi dunia paling cepat akan mendapatkan buah kebijaksanaan dewata.',
                            'sfx' => '*Divine bell chime!* *Peacock flap whoosh!*',
                            'music' => 'Playful celestial Indian-Balinese fusion orchestral rhythm',
                        ],
                        [
                            'title' => 'Frame 02 (00:05 - 00:10): Kartikeya Melesat Keliling Dunia',
                            'action_id' => '[Menyambung langsung dari Frame 1] Kartikeya terbang melesat mengelilingi tujuh benua dan samudra, sementara Ganesha cilik tetap tenang berpikir jernih.',
                            'action_en' => '[Continues directly from Frame 1] Kartikeya zooms away across outer planetary skies, while little Ganesha pauses with his index finger on chin, pondering with profound divine wit.',
                            'dialogue' => 'Kakakku terbang mengelilingi jagat raya fisik dengan kecepatan kilat...',
                            'sfx' => '*Sonic flight whoosh!* *Chime ding!*',
                            'music' => 'Curious playful marimba and sitar tempo',
                        ],
                        [
                            'title' => 'Frame 03 (00:10 - 00:15): Melangkah Penuh Hormat kepada Orang Tua',
                            'action_id' => '[Menyambung langsung dari Frame 2] Ganesha cilik melangkah anggun mendekati tahta Dewa Siwa dan Dewi Parwati yang sedang duduk bersanding penuh kasih.',
                            'action_en' => '[Continues directly from Frame 2] Little Ganesha walks with gentle respectful steps toward the throne where parents Lord Shiva and Goddess Parvati sit with loving smiles.',
                            'dialogue' => 'Namun bagiku, semesta yang paling agung dan suci ada di hadapanku.',
                            'sfx' => '*Soft footsteps on cloud!* *Lotus petal flutter!*',
                            'music' => 'Heartwarming spiritual melody with sweet flute and strings',
                        ],
                        [
                            'title' => 'Frame 04 (00:15 - 00:20): Mengelilingi Orang Tua dengan Kasih Tulus (Pradaksina)',
                            'action_id' => '[Menyambung langsung dari Frame 3] Ganesha cilik berjalan mengitari kedua orang tuanya (Pradaksina) tiga kali dengan kedua tangan terkatup penuh rasa hormat.',
                            'action_en' => '[Continues directly from Frame 3] Little Ganesha circles around his divine parents with hands pressed in deep prayer, radiant golden halos connecting parent and child.',
                            'dialogue' => 'Ibu dan Ayah adalah sumber dari seluruh kehidupan, cinta, dan alam semesta itu sendiri.',
                            'sfx' => '*Warm sacred hum!* *Sparkling aura chime!*',
                            'music' => 'Deeply emotional orchestral swell celebrating parental love',
                        ],
                        [
                            'title' => 'Frame 05 (00:20 - 00:25): Berkah dan Pujian Dewata Semesta',
                            'action_id' => '[Menyambung langsung dari Frame 4] Dewa Siwa dan Dewi Parwati tersenyum bangga memeluk Ganesha cilik, hujan kelopak bunga emas turun dari langit kahyangan.',
                            'action_en' => '[Continues directly from Frame 4] Lord Shiva and Parvati embrace little Ganesha with supreme pride, golden flower petals raining down as devas cheer in divine approval.',
                            'dialogue' => 'Bakti tulus kepada orang tua bernilai lebih mulia daripada mengelilingi seluruh penjuru bumi.',
                            'sfx' => '*Celestial shower chime!* *Trumpet fanfare!*',
                            'music' => 'Triumphant glorious heavenly orchestral climax',
                        ],
                        [
                            'title' => 'Frame 06 (00:25 - 00:30): Resolusi & Doa Kebijaksanaan',
                            'action_id' => '[Menyambung langsung dari Frame 5] Ganesha cilik tersenyum manis ke arah penonton mengangkat tangan kanannya dalam mudra berkah keselamatan (Abhaya Mudra).',
                            'action_en' => '[Continues directly from Frame 5] Little Ganesha faces viewers with joyful sparkling eyes, raising right hand in Abhaya Mudra blessing of protection and wisdom.',
                            'dialogue' => 'Hormatilah orang tuamu dengan tulus, niscaya jalan hidupmu senantiasa terang. Om Shanti Shanti Shanti Om.',
                            'sfx' => '*Grand sacred gong bell chime!* *Soft Om resonance!*',
                            'music' => 'Sacred peaceful Hindu flute and temple bell outro',
                        ],
                    ],
                    'actions_60s' => $this->buildTwelveHinduSceneSequence('deva_wisdom', $character),
                ];
        }
    }

    /**
     * Build 12 structured 5-second scenes totaling exactly 60 seconds (1 Minute) specifically for Hindu stories.
     */
    protected function buildTwelveHinduSceneSequence(string $themeKey, string $character): array
    {
        return [
            [
                'title' => 'Frame 01 (00:00 - 00:05): Hook - Pengenalan Tokoh Suci di Pagi Hari',
                'action_id' => 'Karakter utama terbangun di ashram suci, mengatupkan tangan dalam puja Tri Sandhya menyambut sang surya terbit di ufuk timur.',
                'action_en' => '[Scene 01 of 12] The virtuous character begins the day beside an ancient Balinese temple shrine, hands in sacred Anjali Mudra greeting golden morning sunrise.',
                'dialogue' => 'Om Swastyastu. Pagi yang suci untuk memulai langkah kebajikan di jalan Dharma.',
                'sfx' => '*Sacred bronze bell chime!* *Morning songbird melody!*',
                'music' => 'Calm Balinese bamboo suling and acoustic strings opening',
            ],
            [
                'title' => 'Frame 02 (00:05 - 00:10): Niat Suci Menjalankan Amanat Dharma',
                'action_id' => '[Menyambung langsung dari Frame 1] Beranjak dari tempat duduknya, karakter merapikan pakaian adat putih suci dan mengambil lontar suci petunjuk kebajikan.',
                'action_en' => '[Continues directly from Frame 1] Stepping smoothly onto the courtyard stones, the character checks a sacred palm-leaf scripture with a calm focused expression.',
                'dialogue' => 'Hari ini kita akan mengamalkan Tri Hita Karana demi menjaga keharmonisan alam dan sesama.',
                'sfx' => '*Soft footsteps on stone!* *Rustle of palm leaf!*',
                'music' => 'Uplifting gentle gamelan rhythm with marimba',
            ],
            [
                'title' => 'Frame 03 (00:10 - 00:15): Perjalanan Melintasi Pematang Sawah Suci',
                'action_id' => '[Menyambung langsung dari Frame 2] Karakter berjalan riang menyusuri jalan pematang sawah berundak hijau, menyapa petani desa dengan senyuman santun.',
                'action_en' => '[Continues directly from Frame 2] Walking along lush green terraced rice fields (Subak), the character bows politely to village elders, yellow udeng cloth bright in the sun.',
                'dialogue' => 'Hubungan yang harmonis dengan sesama manusia adalah wujud nyata Pawongan.',
                'sfx' => '*Cicada chirps!* *Water flowing in stone aqueduct!*',
                'music' => 'Bouncy rhythmic traditional flute and percussive harmony',
            ],
            [
                'title' => 'Frame 04 (00:15 - 00:20): Ujian Kebajikan & Insiden di Hutan Pura',
                'action_id' => '[Menyambung langsung dari Frame 3] Tiba di gerbang hutan pura, karakter mendapati mata air suci tersumbat oleh ranting pohon tumbang yang menghalangi aliran air ke desa.',
                'action_en' => '[Continues directly from Frame 3] Approaching a moss-covered forest spring, the character notices fallen tree branches choking the sacred water flow to the village.',
                'dialogue' => 'Mata air desa tersumbat! Jika dibiarkan, sawah dan warga akan kekurangan air bersih.',
                'sfx' => '*Splashing water trickle!* *Creaking wood sound!*',
                'music' => 'Curious and slightly dramatic strings with light tension',
            ],
            [
                'title' => 'Frame 05 (00:20 - 00:25): Mengajak Sahabat Gotong Royong (Paras Paros)',
                'action_id' => '[Menyambung langsung dari Frame 4] Karakter memanggil sahabat-sahabat ashram cilik, dan mereka berkumpul dengan semangat kebersamaan saling mengulurkan tangan.',
                'action_en' => '[Continues directly from Frame 4] The character waves to friends who run forward enthusiastically, rolling up sleeves in joyful traditional teamwork (Gotong Royong).',
                'dialogue' => 'Mari kita bersatu! Dengan semangat Paras Paros Sarpanaya, beban berat menjadi ringan.',
                'sfx' => '*Friendly shouting voice!* *Footsteps running on grass!*',
                'music' => 'Energetic positive cooperation rhythm with wooden bells',
            ],
            [
                'title' => 'Frame 06 (00:25 - 00:30): Bekerja Sama Mengangkat Rintangan',
                'action_id' => '[Menyambung langsung dari Frame 5] Bersama-sama, mereka menarik ranting kayu besar dengan tali rotan, saling memberi aba-aba dengan senyuman dan tawa ceria.',
                'action_en' => '[Continues directly from Frame 5] The children pull the fallen log together using strong woven vines, timing their pulls with joyous synchronized cartoon energy.',
                'dialogue' => 'Satu, dua, tiga... Tarik! Kebersamaan adalah kekuatan Dharma.',
                'sfx' => '*Wood creak whoosh!* *High-five slap sound!*',
                'music' => 'Lively rhythmic Balinese gamelan fusion with cheerful horns',
            ],
            [
                'title' => 'Frame 07 (00:30 - 00:35): Air Suci Mengalir Jernih Kembali',
                'action_id' => '[Menyambung langsung dari Frame 6] Kayu berhasil disingkirkan, air mata air yang jernih memancar deras dan berkilauan mengaliri parit-parit sawah.',
                'action_en' => '[Continues directly from Frame 6] Crystal clear water bursts forth through the stone channel, sparkling with rainbow droplets as small fish swim joyously.',
                'dialogue' => 'Lihat! Tirta suci kehidupan kembali mengalir lancar ke seluruh penjuru desa.',
                'sfx' => '*Rushing clear water roar!* *Sparkling bubble sounds!*',
                'music' => 'Inspiring orchestral crescendo with flute flourishes',
            ],
            [
                'title' => 'Frame 08 (00:35 - 00:40): Menghaturkan Canang Sari Rasa Syukur',
                'action_id' => '[Menyambung langsung dari Frame 7] Sebagai wujud terima kasih pada alam (Palemahan), karakter meletakkan canang sari harum di dekat pancuran mata air suci.',
                'action_en' => '[Continues directly from Frame 7] The character kneels gently beside the pristine spring, placing a fresh flower canang sari on a stone ledge in grateful reverence.',
                'dialogue' => 'Menjaga kelestarian alam adalah wujud cinta kasih kita kepada Sang Pencipta.',
                'sfx' => '*Incense crackle!* *Gentle water cascade!*',
                'music' => 'Heartwarming emotional melody with strings and bells',
            ],
            [
                'title' => 'Frame 09 (00:40 - 00:45): Burung dan Satwa Datang Minum Damai',
                'action_id' => '[Menyambung langsung dari Frame 8] Burung-burung pipit dan satwa hutan turun minum dengan damai di tepi aliran air tanpa rasa takut kepada anak-anak.',
                'action_en' => '[Continues directly from Frame 8] Colorful songbirds and gentle forest deer sip the clear fresh water alongside the smiling children in complete harmony.',
                'dialogue' => 'Semua makhluk hidup hidup rukun dan berdampingan dalam kedamaian semesta.',
                'sfx' => '*Sweet bird singing in chorus!* *Gentle wind whoosh!*',
                'music' => 'Lush atmospheric orchestral swell with serene flute',
            ],
            [
                'title' => 'Frame 10 (00:45 - 00:50): Petang Menjelang di Pelataran Pura',
                'action_id' => '[Menyambung langsung dari Frame 9] Matahari sore melukiskan langit jingga keemasan saat anak-anak melangkah pulang menuju pelataran candi bentar pura agung.',
                'action_en' => '[Continues directly from Frame 9] The children walk back together toward the majestic candi bentar temple gate bathed in glorious golden sunset hues.',
                'dialogue' => 'Hari yang dipenuhi perbuatan baik selalu meninggalkan kebahagiaan mendalam di hati.',
                'sfx' => '*Evening temple gong toll!* *Distant breeze!*',
                'music' => 'Grand noble gamelan orchestral theme',
            ],
            [
                'title' => 'Frame 11 (00:50 - 00:55): Berkumpul dalam Puja Shanti Bersama',
                'action_id' => '[Menyambung langsung dari Frame 10] Warga desa menyambut anak-anak dengan senyuman hangat, semua duduk bersila bersama memanjatkan doa kedamaian.',
                'action_en' => '[Continues directly from Frame 10] Villagers and children gather in the temple courtyard, all seated cross-legged with hands in Anjali Mudra, incense smoke curling upward.',
                'dialogue' => 'Kebaikan kecil yang dilakukan dengan tulus akan membawa kedamaian bagi seluruh semesta.',
                'sfx' => '*Resonant bronze bell chime!* *Deep bell reverberation!*',
                'music' => 'Deep spiritual chorus with serene gamelan bells',
            ],
            [
                'title' => 'Frame 12 (00:55 - 01:00): Resolusi 1 Menit & Salam Luhur',
                'action_id' => '[Menyambung langsung dari Frame 11] Karakter utama menoleh ke arah penonton, tersenyum teduh dan mengatupkan kedua tangan di depan dada menutup kisah 1 menit.',
                'action_en' => '[Continues directly from Frame 11] The young protagonist bows with a luminous peaceful smile toward camera, sunset glow crowning the temple towers.',
                'dialogue' => 'Semoga semua makhluk senantiasa hidup damai dan berbahagia. Om Shanti Shanti Shanti Om.',
                'sfx' => '*Grand temple gong resonance finale!* *Peaceful chime decay!*',
                'music' => 'Grand sacred Balinese gamelan and choir orchestral finale',
            ],
        ];
    }
}
