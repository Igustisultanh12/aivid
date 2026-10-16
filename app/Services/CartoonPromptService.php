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
            'description' => 'Gaya render 3D ekspresif khas Pixar/Disney, mata besar bercahaya, tekstur lembut, dan pencahayaan sinematik hangat.',
            'style_prompt' => '3D Disney Pixar animation style, highly detailed 3D CGI render, expressive large glossy eyes, soft subsurface scattering skin, velvety textures, vibrant warm cinematic lighting, raytraced Octane Render aesthetic, whimsical cartoon charm, masterpiece 4k',
            'negative' => 'photorealistic, realistic human, ugly, deformed, low quality, glitch, creepy eyes, 2d flat, live action, washed out',
        ],
        'ghibli_anime' => [
            'id' => 'ghibli_anime',
            'name' => 'Studio Ghibli 2D Anime',
            'badge' => '2D Anime',
            'description' => 'Gaya anime klasik Hayao Miyazaki, lukisan latar cat air rimbun yang hangat, garis bersih, dan suasana hembusan angin alami.',
            'style_prompt' => 'Studio Ghibli anime style, directed by Hayao Miyazaki, hand-painted lush watercolor background, soft expressive cel-shading, delicate ink lineart, warm golden hour atmospheric lighting, flying flower petals, cozy nostalgic aesthetic, masterpiece 4k',
            'negative' => '3d cgi render, realistic, glossy plastic, grotesque, dull colors, oversaturated neon, harsh 3d shadows',
        ],
        'classic_disney_2d' => [
            'id' => 'classic_disney_2d',
            'name' => 'Classic 2D Disney Hand-Drawn',
            'badge' => 'Tradisional 2D',
            'description' => 'Animasi seluloid klasik tahun 90-an dengan goresan luwes, squash & stretch khas kartun bioskop legendaris.',
            'style_prompt' => 'Classic 1990s golden age traditional Disney 2D animation, hand-drawn character design, expressive cartoon squash-and-stretch anatomy, rich gouache painted background, clean ink lines, fluid theatrical animation look, vibrant storybook illustration',
            'negative' => '3d computer generated, hyperrealistic, muddy textures, robotic stiff pose, blurry sketch, vector flat art',
        ],
        'chibi_3d' => [
            'id' => 'chibi_3d',
            'name' => 'Cute Chibi 3D Stylized',
            'badge' => 'Chibi Gemas',
            'description' => 'Proporsi kepala bulat menggemaskan, mata bulat berkilau, tekstur mainan vinil lembut dengan warna-warna pastel ceria.',
            'style_prompt' => 'Super cute 3D Chibi style, miniature adorable proportions, oversized head with giant sparkling eyes, smooth soft vinyl toy shaders, pastel vibrant tones, soft ambient occlusion studio lighting, kawaii aesthetics, 3D blender cute render, 4k resolution',
            'negative' => 'scary, realistic proportions, gloomy, dark shadows, rough messy sketch, mature gritty look',
        ],
        'flat_vector' => [
            'id' => 'flat_vector',
            'name' => 'Modern Flat Vector Motion',
            'badge' => 'Vector Motion',
            'description' => 'Desain kartun vektor modern bernuansa motion graphics, bentuk geometris tegas, warna kontras tinggi yang dinamis.',
            'style_prompt' => 'Modern premium flat vector cartoon motion graphics, clean geometric silhouettes, bold vibrant color blocking, minimalist stylish shapes, crisp digital illustration, dynamic graphic novel flair, trendy editorial animation',
            'negative' => '3d realistic render, photorealism, grunge textures, chaotic pencil strokes, low resolution',
        ],
        'claymation' => [
            'id' => 'claymation',
            'name' => 'Claymation Stop-Motion',
            'badge' => 'Stop-Motion',
            'description' => 'Karakter lilin plastisin ala studio Aardman (Shaun the Sheep) dengan tekstur sidik jari halus dan pencahayaan studio miniatur.',
            'style_prompt' => 'Artisanal claymation stop-motion animation, Aardman Studios style, sculptured plasticine clay characters with subtle fingerprint textures, tactile handcrafted miniature set, warm directional desk lamp lighting, shallow depth of field, charming stop-motion frame rate',
            'negative' => 'smooth digital 2d vector, slick 3d cgi, realistic photography, sharp digital pixels',
        ],
        'comic_book' => [
            'id' => 'comic_book',
            'name' => 'Comic Book & Webtoon Action',
            'badge' => 'Komik Aksi',
            'description' => 'Garis tinta komik yang tebal dan dinamis, efek halftone Ben-Day dots, serta pose kartun aksi dramatis penuh energi.',
            'style_prompt' => 'Dynamic cartoon comic book style, bold ink contours, Ben-Day halftone screen tones, dramatic cel-shaded lighting, vibrant animated action poses, kinetic speed lines, Into the Spider-Verse inspired cartoon aesthetic, vibrant 4k',
            'negative' => 'bland flat painting, blurry realism, lack of lines, dull washed out colors, low contrast',
        ],
    ];

    /**
     * Cartoon Themes.
     */
    public const THEMES = [
        'fable' => [
            'id' => 'fable',
            'name' => 'Fabel & Dongeng Satwa Nusantara',
            'icon' => 'Sparkles',
            'description' => 'Kisah cerdik Kancil, Merak, Harimau Bijak, dan satwa hutan tropis dengan kelucuan serta pesan moral yang mendalam.',
            'default_title' => 'Kisah Cerdik Kancil & Buah Apel Emas di Hutan Ajaib',
            'default_character' => 'Kancil cilik bermata jeli mengenakan syal daun hijau cerah, bertingkah gesit dan ramah',
            'default_premise' => 'Kancil menemukan pohon apel emas misterius dan harus bekerja sama dengan hewan hutan untuk memetiknya tanpa saling berebut.',
        ],
        'dharma_kids' => [
            'id' => 'dharma_kids',
            'name' => 'Kartun Budi Pekerti & Nilai Luhur Dharma',
            'icon' => 'Heart',
            'description' => 'Cerita edukatif anak tentang Tri Hita Karana (menyayangi alam, berbuat baik sesama, dan bersyukur), ketulusan, dan karma baik.',
            'default_title' => 'Petualangan Wayan Cilik & Penjaga Hutan Suci',
            'default_character' => 'Wayan cilik berudeng kuning cerah, berpakaian kain poleng khas Bali yang lucu dan tersenyum ramah',
            'default_premise' => 'Wayan belajar bahwa sebutir bibit pohon beringin yang dirawat dengan kasih sayang dapat menyelamatkan mata air desa dari kekeringan.',
        ],
        'comedy' => [
            'id' => 'comedy',
            'name' => 'Komedi Kartun Sehari-hari (Slapstick Lucu)',
            'icon' => 'Smile',
            'description' => 'Tingkah kocak anak-anak, hewan peliharaan usil, atau insiden dapur jenaka yang memancing tawa hangat penonton.',
            'default_title' => 'Kucing Oren si Pencuri Kue Kukus yang Bernasib Sial',
            'default_character' => 'Kucing oren gembul berwajah jahil dengan ekor gemuk dan mata memelas yang pandai berakting',
            'default_premise' => 'Si Kucing Oren berusaha menyelinap ke meja makan untuk mencicipi kue kukus lezat, namun setiap rencananya berakhir dengan kekonyolan.',
        ],
        'fantasy' => [
            'id' => 'fantasy',
            'name' => 'Petualangan Fantasi & Negeri Ajaib',
            'icon' => 'Compass',
            'description' => 'Ekspedisi seru mencari pusaka ajaib di atas awan, menunggangi naga pelangi, atau menjelajahi gua kristal bercahaya.',
            'default_title' => 'Pencarian Kompas Bintang di Atas Awan Pelangi',
            'default_character' => 'Anak penjelajah cilik berjaket bulu biru dengan tas selempang ajaib dan teropong kuningan',
            'default_premise' => 'Dua sahabat cilik menaiki perahu daun terbang untuk mencari kompas bintang yang dapat mengembalikan pelangi ke langit malam.',
        ],
        'anime_chibi' => [
            'id' => 'anime_chibi',
            'name' => 'Anime Chibi Petualangan Gemas',
            'icon' => 'Wand2',
            'description' => 'Karakter chibi menggemaskan berambut warna-warni dengan ekspresi dramatis komikal dan kilauan sihir magis.',
            'default_title' => 'Kedai Mochi Sihir si Peri Beruang Salju',
            'default_character' => 'Peri beruang salju chibi bertopi koki mini dengan sendok kayu ajaib yang memancarkan serbuk bintang',
            'default_premise' => 'Si peri beruang salju meracik mochi bintang yang dapat membuat siapapun yang memakannya bisa melayang gembira di udara.',
        ],
        'sci_fi' => [
            'id' => 'sci_fi',
            'name' => 'Kartun Fiksi Ilmiah & Robot Cilik',
            'icon' => 'Tv',
            'description' => 'Petualangan antariksa anak-anak bersama robot ramah, roket bertenaga buah tropis, dan planet neon futuristik.',
            'default_title' => 'Ekspedisi Robo-Kancil Menyelamatkan Satelit Tertawa',
            'default_character' => 'Robot mungil berwarna putih-oranye dengan antena berkedip dan layar dada yang menampilkan ekspresi emosi pixel lucu',
            'default_premise' => 'Robo-Kancil terbang ke orbit rendah untuk memperbaiki satelit antariksa yang tersangkut debu komet bercahaya neon.',
        ],
        'custom' => [
            'id' => 'custom',
            'name' => 'Tema Bebas / Kustom Sendiri',
            'icon' => 'Film',
            'description' => 'Tuliskan ide tema, cerita fabel, dongeng, atau naskah kartun bebas sesuai imajinasi dan kebutuhan kreatif Anda.',
            'default_title' => 'Kisah Kartun Pilihan Saya',
            'default_character' => 'Karakter kartun utama yang ekspresif, lincah, dan penuh semangat',
            'default_premise' => 'Petualangan seru dan sarat makna di dunia kartun animasi penuh warna.',
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
            'suffix' => ', highly dynamic cartoon animation motion, 4k ultra detailed cinematic sequence, fluid 30fps movement',
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
        'establishing_wide' => 'Cinematic wide establishing shot, gentle slow pan left',
        'dynamic_push_in' => 'Dynamic low-angle push-in zoom focusing on character action',
        'tracking_run' => 'Smooth horizontal tracking shot following character movement',
        'close_up_reaction' => 'Expressive close-up shot with subtle camera tilt up, shallow depth of field',
        'crane_high_angle' => 'High-angle crane shot descending into the scene, revealing environment',
        'dolly_zoom_climax' => 'Dramatic dolly zoom vertigo effect capturing the comical/epic climax',
        'orbit_around' => 'Smooth 45-degree orbit around the main character with atmospheric rim light',
        'slow_zoom_out' => 'Peaceful slow zoom out revealing the picturesque cartoon landscape',
    ];

    /**
     * Generate structured cartoon video prompts.
     */
    public function generatePrompts(array $validated): array
    {
        $duration = (int) ($validated['target_duration'] ?? 30);
        $duration = in_array($duration, [30, 60], true) ? $duration : 30;

        $themeKey = $validated['theme'] ?? 'fable';
        $themeMeta = self::THEMES[$themeKey] ?? self::THEMES['fable'];

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
            Log::info('Gemini prompt generation returned fallback. Generating procedural cartoon sequence.');
        }

        // Procedural generator engine (100% reliable, zero error, tailored for 30s or 60s)
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
     * Generate prompts using Gemini 3.8 Flash LLM with structured output schema.
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
Anda adalah Direktur Sutradara dan Ahli Prompt Animasi Kartun AI bertaraf dunia.
Tugas Anda adalah merancang rangkaian prompt video per-frame/per-adegan (action breakdown) untuk membuat video kartun pendek berdurasi TEPAT {$duration} DETIK.

PARAMETER PROYEK:
- Judul Cerita: "{$title}"
- Tema: {$themeKey}
- Premis Cerita: "{$premise}"
- Deskripsi Karakter Utama (KONSISTENSI WAJIB): "{$character}"
- Gaya Visual Animasi: {$styleMeta['name']}
- Prompt Gaya Utama: "{$styleMeta['style_prompt']}"
- Target Video Generator: {$engineMeta['name']}
- Format Rasio Aspek: {$aspectRatio}
- Total Durasi: TEPAT {$duration} DETIK.
- Jumlah Frame/Aksi: TEPAT {$sceneCount} adegan berurutan, masing-masing berdurasi {$frameDuration} detik (5.0s per frame).

STRUKTUR FRAME (ALUR CERITA KARTUN 3-ACT YANG DINAMIS):
Setiap frame mewakili klip video 5 detik:
1. Frame 1 (00:00 - 00:05): Hook Pembuka / Establishing Shot (Karakter diperkenalkan dengan aksi menarik dan ekspresif).
2. Frame 2 (00:05 - 00:10): Kejadian Pemantik / Inciting Incident (Karakter menemukan sesuatu atau menghadapi tantangan awal).
3. Frame 3 s/d Frame {$sceneCount} - 2: Eskalasi Aksi Kartun, Kelucuan / Usaha Seru, dan Interaksi Lingkungan.
4. Frame {$sceneCount} - 1: Puncak Aksi / Klimaks Kartun (Momen paling dramatis, lucu, atau menakjubkan).
5. Frame {$sceneCount} (Detik terakhir menuju {$duration}s): Resolusi / Ending Bahagia / Pesan Moral & Pose Penutup Ikonik.

PANDUAN PROMPT VISUAL (SANGAT PENTING):
- visual_prompt HARUS ditulis dalam BAHASA INGGRIS tingkat tinggi yang dioptimalkan untuk AI Video Generator (Kling, Runway, Sora, Luma).
- Wajib menyertakan deskripsi visual karakter yang konsisten: "{$character}".
- Cantumkan style tokens: "{$styleMeta['style_prompt']}".
- Cantumkan camera motion dan pergerakan karakter secara deskriptif dan dinamis.
- action_description ditulis dalam BAHASA INDONESIA yang jelas dan hidup menggambarkan apa yang terjadi di layar.
- dialogue_or_narration berisi dialog singkat atau narasi suara karakter/narator.
- sound_effects berisi efek suara kartun (contoh: *Boing!*, *Swoosh*, *Plop*, *Laughter*).
- music_mood mendeskripsikan nuansa musik instrumen pengiring.

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
      "action_description": "Deskripsi aksi karakter dalam bahasa Indonesia",
      "visual_prompt": "English visual prompt with character, environment, action, camera motion, style tokens",
      "camera_movement": "Cinematic wide establishing shot, gentle slow pan right",
      "dialogue_or_narration": "Dialog atau narasi pendukung",
      "sound_effects": "SFX kartun",
      "music_mood": "Cheerful orchestral cartoon",
      "negative_prompt": "{$styleMeta['negative']}"
    }
  ]
}
PROMPT;

        try {
            $rawModel = $this->geminiService->model('text');
            $http = $this->geminiService;

            // Generate using GeminiService internal mechanism
            $result = $this->callGeminiApi($geminiKey, $prompt);

            if ($result && !empty($result['scenes']) && is_array($result['scenes'])) {
                // Ensure correct sequential timestamps
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
     * Creative Procedural Cartoon Sequence Generator.
     * Guaranteed to return high-quality, rich 30s or 60s per-frame prompts without any external dependencies.
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

        $themeTemplates = $this->getThemeNarrativeBank($themeKey, $title, $premise, $character);

        $scenes = [];
        $actionsBank = $duration === 30 ? $themeTemplates['actions_30s'] : $themeTemplates['actions_60s'];

        $cameraCycle = array_values(self::CAMERA_MOTIONS);

        for ($i = 0; $i < $sceneCount; $i++) {
            $startSec = round($i * $frameDuration, 2);
            $endSec = round(($i + 1) * $frameDuration, 2);

            $actionData = $actionsBank[$i] ?? [
                'title' => "Adegan " . ($i + 1) . ": Aksi Petualangan Kartun",
                'action_id' => "Karakter melanjutkan petualangannya dengan penuh energi dan tawa gembira.",
                'action_en' => "The main character bounds forward cheerfully across the vibrant cartoon landscape, smiling brightly.",
                'dialogue' => "Ayo, sedikit lagi kita sampai!",
                'sfx' => "*Boing!* *Whoosh!*",
                'music' => "Upbeat and playful cartoon rhythm",
            ];

            $cameraMotion = $cameraCycle[$i % count($cameraCycle)];

            // Build English AI visual prompt
            $visualPrompt = sprintf(
                "%s. Character: %s. Action: %s. Setting: %s, %s. Camera: %s. Lighting: %s. %s%s",
                $styleMeta['style_prompt'],
                $character,
                $actionData['action_en'],
                $themeTemplates['environment'],
                $aspectRatio === '9:16' ? 'vertical 9:16 composition for mobile' : ($aspectRatio === '16:9' ? 'cinematic 16:9 widescreen composition' : 'square 1:1 format'),
                $cameraMotion,
                $actionData['lighting'] ?? 'warm golden sunlight with colorful rim illumination',
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
     * Regenerate a single scene prompt with creative variations.
     */
    public function regenerateSingleScene(CartoonPromptProject $project, CartoonPromptScene $scene): CartoonPromptScene
    {
        $styleMeta = self::VISUAL_STYLES[$project->visual_style] ?? self::VISUAL_STYLES['pixar_3d'];
        $engineMeta = self::TARGET_ENGINES[$project->target_engine] ?? self::TARGET_ENGINES['universal'];
        $aspectRatio = $project->aspect_ratio ?? '9:16';

        $cameraMotions = array_values(self::CAMERA_MOTIONS);
        $randomCamera = $cameraMotions[array_rand($cameraMotions)];

        // Enhanced dynamic variations
        $variations = [
            [
                'title' => 'Momen Aksi Dinamis Karakter',
                'action_id' => 'Karakter utama melompat dengan riang gembira, matanya berbinar melihat keajaiban di sekitarnya.',
                'action_en' => 'The main character jumps with pure childlike joy, eyes sparkling with wonder as vibrant sparkles float around.',
                'sfx' => '*Boing!* *Twinkle!*',
                'music' => 'Playful whimsical flute and marimba melody',
            ],
            [
                'title' => 'Reaksi Terkejut & Kelucuan Spontan',
                'action_id' => 'Karakter terhenyak lucu dengan mulut terbuka lebar, telinga bergoyang gemas sebelum tersenyum lebar.',
                'action_en' => 'The character pauses in comedic surprise with wide expressive eyes, doing a funny cartoon double-take then breaking into a warm grin.',
                'sfx' => '*Gasp!* *Cartoon slide whistle!*',
                'music' => 'Comedic brass punch with energetic tempo',
            ],
            [
                'title' => 'Gerakan Heroik & Pose Penuh Semangat',
                'action_id' => 'Karakter berpose penuh percaya diri di bawah cahaya mentari hangat, siap melanjutkan petualangan.',
                'action_en' => 'The character strikes a confident, heroic cute pose in glowing morning sunlight, nodding enthusiastically.',
                'sfx' => '*Whoosh!* *Chime!*',
                'music' => 'Uplifting orchestral swell with cheerful brass',
            ],
            [
                'title' => 'Eksplorasi Lingkungan yang Mengagumkan',
                'action_id' => 'Karakter menyusuri jalan setapak ajaib bertabur bunga warna-warni yang bergoyang ditiup angin sepoi-sepoi.',
                'action_en' => 'The character explores a magical blooming pathway, colorful petals drifting in a gentle warm breeze.',
                'sfx' => '*Rustle!* *Soft wind chime!*',
                'music' => 'Gentle acoustic strings and magical celeste',
            ],
        ];

        $var = $variations[array_rand($variations)];

        $newVisualPrompt = sprintf(
            "%s. Character: %s. Action: %s. Setting: Colorful lush cartoon world with glowing ambient particles, %s. Camera: %s. Lighting: Dynamic volumetric golden rim lighting. %s%s",
            $styleMeta['style_prompt'],
            $project->character_description ?: 'Expressive energetic cartoon protagonist',
            $var['action_en'],
            $aspectRatio === '9:16' ? 'vertical 9:16 portrait view' : 'widescreen 16:9 view',
            $randomCamera,
            $styleMeta['style_prompt'],
            $engineMeta['suffix']
        );

        $scene->update([
            'title' => $var['title'] . ' (Variasi Baru)',
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
     * Rich Story & Narrative Bank for Procedural Generation.
     */
    protected function getThemeNarrativeBank(string $themeKey, string $title, string $premise, string $character): array
    {
        switch ($themeKey) {
            case 'fable':
                return [
                    'environment' => 'vibrant tropical rainforest with oversized mushrooms, hanging vines, and golden sunbeams filtering through dense canopy',
                    'actions_30s' => [
                        [
                            'title' => 'Detik 0-5: Hook & Pengenalan Kancil',
                            'action_id' => 'Kancil melongok dari balik semak dedaunan raksasa, telinganya bergoyang lucu mendengar kicauan burung merdu.',
                            'action_en' => 'The clever little mouse deer peeks out from oversized tropical ferns, ears wiggling adorably, sniffing the morning air with curious bright eyes.',
                            'dialogue' => 'Wah, pagi yang cerah di hutan tropis!',
                            'sfx' => '*Rustle!* *Chirp chirp!*',
                            'music' => 'Gentle cheerful xylophone and wooden percussion',
                        ],
                        [
                            'title' => 'Detik 5-10: Menemukan Pohon Apel Emas',
                            'action_id' => 'Kancil berjalan jinjit di atas batang pohon tumbang, matanya melebar takjub melihat buah apel emas berkilauan.',
                            'action_en' => 'The mouse deer tiptoes gracefully along a mossy fallen log, jaw dropping in funny cartoon astonishment at a giant golden apple glowing on a high branch.',
                            'dialogue' => 'Lihat itu! Apel emas legendaris!',
                            'sfx' => '*Tiptoe sound!* *Sparkle shine chime!*',
                            'music' => 'Curious and wondrous pizzicato strings',
                        ],
                        [
                            'title' => 'Detik 10-15: Tantangan & Ide Cerdik',
                            'action_id' => 'Kancil meletakkan telunjuk di dagunya berpikir keras, lalu bola lampu imajinasi kartun menyala di atas kepalanya.',
                            'action_en' => 'The mouse deer taps its chin comically in deep thought, then jumps up with a brilliant idea, pointing excitedly towards a vine swing.',
                            'dialogue' => 'Aha! Aku punya akal cerdik!',
                            'sfx' => '*Cartoon ding!* *Spring boing!*',
                            'music' => 'Quickening playful marimba rhythm',
                        ],
                        [
                            'title' => 'Detik 15-20: Aksi Ayunan Menegangkan',
                            'action_id' => 'Kancil menggenggam sulur tanaman dan berayun kencang melintasi udara seperti akrobat sirkus cilik.',
                            'action_en' => 'The mouse deer grabs a sturdy jungle vine and swings heroically through the air, laughing as green leaves swirl in its wake.',
                            'dialogue' => 'Satu, dua, tiga... Meluncur!',
                            'sfx' => '*Whoosh!* *Tarzan-style comedic shout!*',
                            'music' => 'Energetic cartoon orchestral crescendo',
                        ],
                        [
                            'title' => 'Detik 20-25: Memetik dan Berbagi dengan Teman',
                            'action_id' => 'Kancil berhasil mendarat empuk di tumpukan lumut bersama apel emas, lalu membagikannya kepada burung pipit yang lapar.',
                            'action_en' => 'The mouse deer lands softly into a plush moss patch with the golden fruit, warmly breaking it into pieces to share with cute forest birds.',
                            'dialogue' => 'Makanan paling enak adalah yang dinikmati bersama sahabat.',
                            'sfx' => '*Plop!* *Heart chime!*',
                            'music' => 'Warm heartwarming violin and acoustic guitar harmony',
                        ],
                        [
                            'title' => 'Detik 25-30: Refleksi & Penutup Bahagia',
                            'action_id' => 'Kancil mengedipkan mata ke arah penonton sambil melambaikan tangan, tersenyum bangga di tengah pelangi hutan.',
                            'action_en' => 'The mouse deer winks playfully at the camera, giving a cute thumbs-up as a bright rainbow forms in the mist behind, smiling happily.',
                            'dialogue' => 'Kecerdikan sejati adalah yang membawa kebaikan untuk semua!',
                            'sfx' => '*Sparkle wink ding!* *Happy fanfare!*',
                            'music' => 'Triumphant joyful orchestral finale',
                        ],
                    ],
                    'actions_60s' => $this->buildTwelveSceneSequence('fable', $character),
                ];

            case 'dharma_kids':
                return [
                    'environment' => 'picturesque Balinese village temple gate with frangipani trees, green terraced rice fields, and gentle morning mist',
                    'actions_30s' => [
                        [
                            'title' => 'Detik 0-5: Salam Pagi Penuh Syukur',
                            'action_id' => 'Wayan cilik meletakkan kedua telapak tangan di depan dada (Anjali Mudra) sambil tersenyum hangat menyambut fajar.',
                            'action_en' => 'Cute young Balinese boy in traditional sarong bows with hands folded gracefully in Anjali Mudra greeting, morning golden sunlight kissing temple stone carved statues.',
                            'dialogue' => 'Om Swastyastu! Selamat pagi alam semesta yang indah.',
                            'sfx' => '*Gentle temple bell chime!*',
                            'music' => 'Calm soothing gamelan suling and bamboo chime melody',
                        ],
                        [
                            'title' => 'Detik 5-10: Membawa Bibit Pohon Suci',
                            'action_id' => 'Wayan membawa pot tanah kecil berisi tunas pohon beringin muda dengan riang melintasi pematang sawah hijau.',
                            'action_en' => 'The boy walks briskly along lush green rice terrace paths carrying a small clay pot with a tender green sprout, butterflies fluttering around his udeng headband.',
                            'dialogue' => 'Hari ini kita akan menanam bibit harapan untuk mata air desa.',
                            'sfx' => '*Chirping cicadas!* *Footstep grass crunch!*',
                            'music' => 'Uplifting traditional acoustic gamelan rhythm',
                        ],
                        [
                            'title' => 'Detik 10-15: Sahabat Satwa yang Membantu',
                            'action_id' => 'Seekor anak monyet lucu berbulu abu-abu melompat turun dari dahan dan membantu menggali tanah dengan ranting kayu.',
                            'action_en' => 'A friendly little cartoon monkey with big round eyes swings down playfully, helping the boy dig soft moist earth with a miniature wooden trowel.',
                            'dialogue' => 'Terima kasih sahabat kecilku, kita jaga bumi bersama!',
                            'sfx' => '*Monkey joyful chatter!* *Dirt dig sound!*',
                            'music' => 'Playful interactive percussion and marimba',
                        ],
                        [
                            'title' => 'Detik 15-20: Menanam dengan Ketulusan Hati',
                            'action_id' => 'Wayan menanam bibit ke dalam tanah dan menyiramnya dengan air kelapa suci, tunas seketika memancarkan pendar cahaya keemasan.',
                            'action_en' => 'The boy gently pats the fertile soil around the seedling, pouring fresh clear water from a coconut shell, magical golden aura shimmering from the roots.',
                            'dialogue' => 'Tumbuhlah kuat dan teduh, lindungi semua makhluk hidup.',
                            'sfx' => '*Water splash!* *Magical sprout sparkle!*',
                            'music' => 'Inspiring orchestral gamelan fusion swell',
                        ],
                        [
                            'title' => 'Detik 20-25: Pesan Luhur Tri Hita Karana',
                            'action_id' => 'Pohon tumbuh dengan cepat memayungi Wayan dan hewan-hewan desa yang berkumpul damai di bawahnya.',
                            'action_en' => 'The young tree blossoms into a beautiful canopy, birds, monkeys, and deer resting peacefully together in harmonious coexistence beneath the shade.',
                            'dialogue' => 'Ketika kita mengasihi alam, alam akan menjaga kita selamanya.',
                            'sfx' => '*Gentle breeze whoosh!* *Birds singing in harmony!*',
                            'music' => 'Heartwarming majestic instrumental chorus',
                        ],
                        [
                            'title' => 'Detik 25-30: Penutup Doa Damai',
                            'action_id' => 'Wayan tersenyum damai menangkupkan tangan, kelopak bunga kamboja melayang lembut menutupi layar.',
                            'action_en' => 'The boy smiles serenely at the viewer with folded palms, fragrant frangipani blossoms drifting gently across the screen, radiant sunset glow.',
                            'dialogue' => 'Om Shanti Shanti Shanti Om. Damai di hati, damai di dunia.',
                            'sfx' => '*Resonant brass gong!* *Soft wind chime!*',
                            'music' => 'Deeply peaceful meditative gamelan outro',
                        ],
                    ],
                    'actions_60s' => $this->buildTwelveSceneSequence('dharma_kids', $character),
                ];

            case 'comedy':
                return [
                    'environment' => 'lively cartoon traditional village kitchen with stacked clay pots, wooden steamers, and floating aroma lines',
                    'actions_30s' => [
                        [
                            'title' => 'Detik 0-5: Misi Rahasia si Kucing Oren',
                            'action_id' => 'Kucing oren gembul mengendap-endap dengan kacamata hitam mini di balik tudung saji bambu, mengincar piring kue.',
                            'action_en' => 'A chubby orange tabby cat wears miniature funny sunglasses, sneaking on tiptoes behind a woven food cover, targeting a steaming plate of cakes.',
                            'dialogue' => '*Meow meong*... Target terdeteksi di atas meja!',
                            'sfx' => '*Pink Panther-style stealth tiptoe!*',
                            'music' => 'Sneaky playful jazz cartoon rhythm',
                        ],
                        [
                            'title' => 'Detik 5-10: Gerakan Akrobatik yang Gagal',
                            'action_id' => 'Kucing melompat tinggi berniat mendarat di kursi, namun kakinya terpeleset kulit pisang kartun.',
                            'action_en' => 'The cat leaps dramatically in slow-motion, but its back paws comically slip on a bright yellow cartoon banana peel, legs spinning like windmills.',
                            'dialogue' => 'Aduh duh duh... Rem blong!',
                            'sfx' => '*Slipping rubber screech!* *Cartoon slide whistle!*',
                            'music' => 'Frantic comedic brass and slide trombone',
                        ],
                        [
                            'title' => 'Detik 10-15: Kekacauan Dapur Menggelikan',
                            'action_id' => 'Kucing mendarat tepat di atas wajan tepung, membuat tepung putih meledak menutupi wajahnya seperti badut.',
                            'action_en' => 'The cat crash-lands into a wooden flour bowl, a big comical puff of white flour exploding, leaving the cat looking like a bewildered snowman.',
                            'dialogue' => 'Uhuk uhuk! Sejak kapan aku jadi kucing salju?',
                            'sfx' => '*Poof!* *Cough sound!* *Cymbal crash!*',
                            'music' => 'Fast-paced circus cartoon gallop',
                        ],
                        [
                            'title' => 'Detik 15-20: Pengejaran Sapu Terbang Mbok Made',
                            'action_id' => 'Mbok Made datang memegang sapu lidi sambil tertawa geli, mengejar si kucing oren yang berlari zig-zag.',
                            'action_en' => 'A kindly cartoon village grandmother chuckles warmly, chasing the white-floured orange cat who scampers in hilarious zigzag cartoon speed trails.',
                            'dialogue' => 'Heh Meng! Mau kue minta baik-baik, jangan mencuri!',
                            'sfx' => '*Bong-bong-bong footsteps!* *Laughter sound!*',
                            'music' => 'Zany slapstick chase music with banjos and horns',
                        ],
                        [
                            'title' => 'Detik 20-25: Akhirnya Diberi Makan dengan Senang',
                            'action_id' => 'Kucing duduk manis dengan wajah melas andalannya, dan Mbok Made menyuapinya sepotong kue kukus hangat.',
                            'action_en' => 'The cat sits upright doing giant cute teary anime puppy eyes, and the grandmother happily hands him a delicious warm sweet treat on a banana leaf.',
                            'dialogue' => 'Nyam nyam... Kalau minta baik-baik ternyata langsung dikasih!',
                            'sfx' => '*Purr purr!* *Munching smacks!*',
                            'music' => 'Sweet heartwarming cartoon waltz',
                        ],
                        [
                            'title' => 'Detik 25-30: Perut Kenyang dan Kedipan Usil',
                            'action_id' => 'Kucing oren tidur terlentang dengan perut bulat kenyang, mengacungkan jempol ke kamera sambil tersenyum lebar.',
                            'action_en' => 'The round-bellied cat reclines happily like a king on a woven mat, tapping its full tummy and giving a cheeky cartoon wink to viewers.',
                            'dialogue' => 'Ingat kawan: mintalah dengan sopan, jangan pakai drama terpeleset!',
                            'sfx' => '*Satisfied burp chime!* *Funny ding!*',
                            'music' => 'Jovial brass horn comedy ending chord',
                        ],
                    ],
                    'actions_60s' => $this->buildTwelveSceneSequence('comedy', $character),
                ];

            default:
                return [
                    'environment' => 'colorful wondrous cartoon adventure land filled with glowing flora, sparkling crystal formations, and warm ambient sunlight',
                    'actions_30s' => [
                        [
                            'title' => 'Detik 0-5: Pembuka Petualangan yang Menakjubkan',
                            'action_id' => 'Karakter utama melangkah keluar ke padang rumput bercahaya dengan tatapan penuh rasa ingin tahu dan keberanian.',
                            'action_en' => 'The protagonist steps forward into a vibrant glowing meadow, eyes shining with boundless adventurous spirit, soft breeze rustling clothing.',
                            'dialogue' => 'Petualangan terbesar selalu dimulai dari satu langkah berani!',
                            'sfx' => '*Whoosh!* *Magical twinkle!*',
                            'music' => 'Uplifting cinematic fantasy cartoon theme',
                        ],
                        [
                            'title' => 'Detik 5-10: Menemukan Petunjuk Misteri',
                            'action_id' => 'Karakter menemukan peta kuno bercahaya di atas batu kristal dan memperhatikannya dengan ekspresi takjub.',
                            'action_en' => 'The character discovers an ancient glowing cartoon scroll hovering above a magical crystal pedestal, examining it with joyful wonder.',
                            'dialogue' => 'Lihat peta ini, jalannya terbuka untuk kita!',
                            'sfx' => '*Chime!* *Energy hum!*',
                            'music' => 'Curious and adventurous woodwind cadence',
                        ],
                        [
                            'title' => 'Detik 10-15: Menghadapi Rintangan dengan Tawa',
                            'action_id' => 'Karakter melompati batu-batu apung di atas sungai pelangi dengan gerakan lincah dan tawa ceria.',
                            'action_en' => 'The character hops playfully across floating stepping stones above a luminescent rainbow river, balancing with comedic grace.',
                            'dialogue' => 'Lompat ke kiri, lompat ke kanan... Berhasil!',
                            'sfx' => '*Boing!* *Splash twinkle!*',
                            'music' => 'Dynamic kinetic orchestral sprint',
                        ],
                        [
                            'title' => 'Detik 15-20: Bekerja Sama dengan Teman Setia',
                            'action_id' => 'Sahabat karakter mengulurkan tangan membantu menariknya ke tebing atas saat hampir terpeleset.',
                            'action_en' => 'A loyal animated companion reaches down from the grassy cliff ledge, gripping hands and hoisting the character up with a triumph shout.',
                            'dialogue' => 'Pegang tanganku! Bersama kita tidak terkalahkan!',
                            'sfx' => '*Grip whoosh!* *Cheering shout!*',
                            'music' => 'Triumphant heroic brass crescendo',
                        ],
                        [
                            'title' => 'Detik 20-25: Menemukan Puncak Keindahan',
                            'action_id' => 'Keduanya berdiri di puncak bukit memandang kastil awan megah dengan kembang api kebaikan meletup di langit.',
                            'action_en' => 'Both characters gaze in awe from the scenic hilltop at a majestic cloud castle, colorful pastel fireworks exploding peacefully in the sky.',
                            'dialogue' => 'Semua kerja keras dan kejujuran berbuah manis!',
                            'sfx' => '*Firework whistle!* *Pop sparkles!*',
                            'music' => 'Grand emotional cinematic cartoon swell',
                        ],
                        [
                            'title' => 'Detik 25-30: Salam Kemenangan & Pesan Moral',
                            'action_id' => 'Karakter menoleh ke arah penonton, tersenyum lebar dan mengangkat jempol sebagai penutup cerita yang hangat.',
                            'action_en' => 'The character faces the audience with a beaming warm smile, waving enthusiastically as credits aura shines around the frame.',
                            'dialogue' => 'Percayalah pada kebaikan hatimu, sampai jumpa di petualangan berikutnya!',
                            'sfx' => '*Heart chime!* *Victory fanfare!*',
                            'music' => 'Uplifting grand cartoon finale flourish',
                        ],
                    ],
                    'actions_60s' => $this->buildTwelveSceneSequence($themeKey, $character),
                ];
        }
    }

    /**
     * Build 12 structured 5-second scenes totaling exactly 60 seconds (1 Minute).
     */
    protected function buildTwelveSceneSequence(string $themeKey, string $character): array
    {
        return [
            [
                'title' => 'Frame 01 (00:00 - 00:05): Hook Pembuka yang Menawan',
                'action_id' => 'Karakter utama terbangun di pagi hari dengan senyuman lebar, membuka jendela pondok kayu yang menghadap pemandangan magis.',
                'action_en' => 'The protagonist awakens cheerfully, throwing open rustic wooden cottage shutters to reveal a breathtaking panoramic cartoon vista bathed in morning sunlight.',
                'dialogue' => 'Pagi yang luar biasa! Hari baru, petualangan baru!',
                'sfx' => '*Shutter creek!* *Morning rooster/bird call!*',
                'music' => 'Warm acoustic opening melody with flute',
            ],
            [
                'title' => 'Frame 02 (00:05 - 00:10): Persiapan dan Perlengkapan Unik',
                'action_id' => 'Karakter mengemas ransel kecilnya dengan barang-barang lucu (botol air bambu, kompas buah, dan bekal kue manis).',
                'action_en' => 'The character playfully packs a colorful backpack, tossing in whimsical cartoon supplies while doing a happy little dance.',
                'dialogue' => 'Bekal sudah siap, topi sudah rapi, saatnya berangkat!',
                'sfx' => '*Zip!* *Bag thump!* *Jingle chime!*',
                'music' => 'Playful marching tempo with marimba',
            ],
            [
                'title' => 'Frame 03 (00:10 - 00:15): Perjalanan Melintasi Hutan Ajaib',
                'action_id' => 'Karakter berjalan riang di jalan setapak, menyapa bunga-bunga kartun yang bernyanyi dan bergoyang ditiup angin.',
                'action_en' => 'The character skips happily along a magical forest path, waving at singing colorful blossoms that sway to the rhythm.',
                'dialogue' => 'Halo bunga-bunga cantik, semoga hari kalian menyenangkan!',
                'sfx' => '*Boing steps!* *Sweet musical chime!*',
                'music' => 'Bouncy rhythmic cartoon woodwind ensemble',
            ],
            [
                'title' => 'Frame 04 (00:15 - 00:20): Insiden Pemantik / Teka-teki Pertama',
                'action_id' => 'Di tengah jalan, karakter menemukan jembatan gantung tua yang putus salah satu talinya karena tertiup angin kencang.',
                'action_en' => 'The character halts at a scenic wooden suspension bridge over a sparkling canyon, noticing a detached rope swaying gently in the breeze.',
                'dialogue' => 'Waduh, jembatannya terputus! Bagaimana caraku menyeberang?',
                'sfx' => '*Creaking rope!* *Sudden comical brake sound!*',
                'music' => 'Curious mystery tension with pizzicato strings',
            ],
            [
                'title' => 'Frame 05 (00:20 - 00:25): Munculnya Sahabat Tak Terduga',
                'action_id' => 'Seekor satwa hutan cilik yang lucu muncul dari balik dedaunan membawakan sulur rotan kuat untuk membantu.',
                'action_en' => 'An adorable cartoon creature with big glassy eyes emerges from the ferns carrying a strong woven jungle vine, chirping happily.',
                'dialogue' => 'Hai sahabat kecil, kamu mau membantuku memperbaiki jembatan?',
                'sfx' => '*Creature cute squeak!* *Rustle!*',
                'music' => 'Warm friendship theme with soft harp',
            ],
            [
                'title' => 'Frame 06 (00:25 - 00:30): Gotong Royong Memperbaiki Jembatan',
                'action_id' => 'Keduanya bekerja sama mengikat sulur dengan simpul kuat, saling bertukar tawa dan tos tangan kartun.',
                'action_en' => 'Both characters tie the vine together in comical synchronized teamwork, ending with an energetic cartoon high-five.',
                'dialogue' => 'Tarik bersama-sama! Satu, dua, tiga... Kuat sekali!',
                'sfx' => '*Rope tighten whoosh!* *High-five clap sound!*',
                'music' => 'Energetic positive cooperation rhythm',
            ],
            [
                'title' => 'Frame 07 (00:30 - 00:35): Menyeberangi Jurang Penuh Keberanian',
                'action_id' => 'Karakter melangkah mantap menyeberangi jembatan yang kini kokoh, memandang air terjun pelangi di bawahnya.',
                'action_en' => 'The character marches confidently across the restored bridge, looking down at a mesmerizing rainbow waterfall spraying glistening mist.',
                'dialogue' => 'Lihat ke bawah! Sungguh pemandangan yang tiada duanya!',
                'sfx' => '*Waterfall roar in background!* *Wind whoosh!*',
                'music' => 'Inspiring orchestral crescendo',
            ],
            [
                'title' => 'Frame 08 (00:35 - 00:40): Memasuki Lembah Rahasia',
                'action_id' => 'Mereka tiba di lembah tersembunyi yang dipenuhi pohon kristal buah bercahaya dalam berbagai warna pastel.',
                'action_en' => 'They step into a hidden mythical valley filled with luminescent crystal trees bearing fruit in soft pastel hues, glowing gently.',
                'dialogue' => 'Ini dia Lembah Impian yang sering diceritakan kakek!',
                'sfx' => '*Magical shimmer sound!* *Chime harmonic!*',
                'music' => 'Wondrous celestial bells and strings',
            ],
            [
                'title' => 'Frame 09 (00:40 - 00:45): Rintangan Lucu di Puncak Lembah',
                'action_id' => 'Karakter dikejutkan oleh hembusan angin usil yang menerbangkan topinya ke atas dahan pohon tertinggi.',
                'action_en' => 'A playful cartoon gust of wind swoops in, blowing the character\'s hat high up into a tall bouncy mushroom branch.',
                'dialogue' => 'Eits! Topiku jangan kabur dong!',
                'sfx' => '*Whoosh gust!* *Cartoon slide whistle up!*',
                'music' => 'Fast comedic chase tempo with brass',
            ],
            [
                'title' => 'Frame 10 (00:45 - 00:50): Klimaks Aksi Melompat Cerdas',
                'action_id' => 'Karakter memanfaatkan jamur pegas untuk melompat tinggi ke udara, menangkap topinya sambil berputar 360 derajat.',
                'action_en' => 'The character bounces off a giant springy mushroom, flying high in a 360-degree comical spin, snatching the hat with a triumphant grin.',
                'dialogue' => 'Kena kau! Hup!',
                'sfx' => '*Boiiiing!* *Sparkle catch sound!*',
                'music' => 'Thrilling acrobatic brass flourish',
            ],
            [
                'title' => 'Frame 11 (00:50 - 00:55): Resolusi dan Berbagi Kebahagiaan',
                'action_id' => 'Mendarat mulus di pelukan sahabatnya, mereka duduk bersama menikmati buah kristal manis sambil memandang langit senja jingga.',
                'action_en' => 'Landing softly on moss, both characters sit together sharing sweet glowing fruit, watching the majestic sunset paint the sky in orange and violet.',
                'dialogue' => 'Tidak ada rintangan yang tak bisa dilewati jika kita selalu ceria dan berusaha.',
                'sfx' => '*Sweet munch smacks!* *Calm evening breeze!*',
                'music' => 'Gentle emotional acoustic guitar and cello',
            ],
            [
                'title' => 'Frame 12 (00:55 - 01:00): Penutup 1 Menit & Pesan Kebaikan',
                'action_id' => 'Karakter menoleh ke arah penonton, tersenyum lebar dengan mata berbinar, melambaikan tangan bersama sahabatnya saat layar perlahan memudar.',
                'action_en' => 'Both characters stand side-by-side, waving happily with big warm cartoon smiles at the camera as golden twilight dust envelopes the frame.',
                'dialogue' => 'Terima kasih sudah menemani petualangan 1 menit ini. Tetaplah berbuat baik dan ceria setiap hari!',
                'sfx' => '*Chime sparkle finale!* *Warm celebratory chime!*',
                'music' => 'Grand heartwarming cartoon orchestral finale',
            ],
        ];
    }
}
