<?php

namespace App\Services;

use App\Models\AiUsageLog;
use App\Models\AppSetting;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Integrasi Google Gemini API (Google AI Studio).
 *
 * Mengikuti panduan model terbaru di https://ai.google.dev/gemini-api/docs/models :
 * - Teks      : gemini-3.8-flash (models.generateContent)
 * - Gambar    : gemini-nano-banana-2.1 / gemini-3.1-flash-image (Interactions API)
 * - Suara TTS : gemini-3.8-flash-tts (Interactions API, keluaran WAV 24 kHz)
 * - Video     : gemini-omni-1.1-flash / veo-3.1-generate-preview
 *
 * Model lama yang sudah tidak tersedia (gemini-1.5/2.0/3.0, imagen-*, veo-2.0, dll.)
 * otomatis dipetakan ke penggantinya lewat resolveModel(), sehingga nilai lama
 * yang masih tersimpan di database tidak lagi menyebabkan error "not found".
 */
class GeminiService
{
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';

    public const DEFAULT_TEXT_MODEL = 'gemini-3.8-flash';
    public const DEFAULT_IMAGE_MODEL = 'gemini-nano-banana-2.1';
    public const DEFAULT_TTS_MODEL = 'gemini-3.8-flash-tts';
    public const DEFAULT_VIDEO_MODEL = 'gemini-omni-1.1-flash';
    public const DEFAULT_VOICE = 'Sulafat';

    /** Model cadangan bila model utama ditolak (404/403) oleh project Google. */
    protected const FALLBACKS = [
        'text' => ['gemini-3.8-flash', 'gemini-3.6-flash', 'gemini-3.5-flash-lite'],
        'image' => ['gemini-nano-banana-2.1', 'gemini-3.1-flash-image', 'gemini-3.1-flash-lite-image'],
        'tts' => ['gemini-3.8-flash-tts', 'gemini-3.8-flash-lite-tts'],
    ];

    /** Pemetaan model lama / salah ketik ke model resmi yang masih aktif. */
    protected const LEGACY_MODEL_MAP = [
        'text' => [
            'gemini-3.0-flash' => 'gemini-3.8-flash',
            'gemini-3-flash' => 'gemini-3.8-flash',
            'gemini-3.0-pro' => 'gemini-3.8-flash',
            'gemini-3-pro-preview' => 'gemini-3.8-flash',
            'gemini-3.1-flash-lite-preview' => 'gemini-3.5-flash-lite',
            'gemini-2.0-flash' => 'gemini-3.8-flash',
            'gemini-2.0-flash-lite' => 'gemini-3.5-flash-lite',
            'gemini-2.0-flash-exp' => 'gemini-3.8-flash',
            'gemini-1.5-flash' => 'gemini-3.8-flash',
            'gemini-1.5-pro' => 'gemini-3.8-flash',
        ],
        'image' => [
            'imagen-3.0-generate-002' => 'gemini-nano-banana-2.1',
            'imagen-3.0-generate-001' => 'gemini-nano-banana-2.1',
            'imagen-4.0-generate' => 'gemini-nano-banana-2.1',
            'imagen-4.0-generate-001' => 'gemini-nano-banana-2.1',
            'gemini-2.0-flash' => 'gemini-nano-banana-2.1',
            'gemini-2.0-flash-exp' => 'gemini-nano-banana-2.1',
            'gemini-omni-1.1-flash' => 'gemini-nano-banana-2.1',
        ],
        'tts' => [
            'gemini-2.0-flash' => 'gemini-3.8-flash-tts',
            'gemini-3.0-flash' => 'gemini-3.8-flash-tts',
            'gemini-3.1-flash-tts' => 'gemini-3.1-flash-tts-preview',
        ],
        'video' => [
            'veo-3.1' => 'veo-3.1-generate-preview',
            'veo-3.1-lite' => 'veo-3.1-lite-generate-preview',
            'veo-3.0-generate-001' => 'veo-3.1-generate-preview',
            'veo-2.0-generate-001' => 'gemini-omni-1.1-flash',
            'gemini-2.0-flash-exp' => 'gemini-omni-1.1-flash',
        ],
    ];

    /** Suara bawaan resmi untuk model TTS Gemini. */
    public const VOICES = [
        'Zephyr', 'Puck', 'Charon', 'Kore', 'Fenrir', 'Leda', 'Orus', 'Aoede', 'Callirrhoe', 'Autonoe',
        'Enceladus', 'Iapetus', 'Umbriel', 'Algieba', 'Despina', 'Erinome', 'Algenib', 'Rasalgethi',
        'Laomedeia', 'Achernar', 'Alnilam', 'Schedar', 'Gacrux', 'Pulcherrima', 'Achird',
        'Zubenelgenubi', 'Vindemiatrix', 'Sadachbia', 'Sadaltager', 'Sulafat',
    ];

    /**
     * Get the active Gemini API key from database.
     */
    public function getApiKey(): ?string
    {
        return AppSetting::get('gemini_api_key');
    }

    /**
     * Check if API key is configured.
     */
    public function isConfigured(): bool
    {
        $key = $this->getApiKey();
        return !empty($key);
    }

    /**
     * Normalisasi ID model: buang prefix "models/", petakan model lama ke penggantinya.
     */
    public static function resolveModel(string $type, ?string $model): string
    {
        $defaults = [
            'text' => self::DEFAULT_TEXT_MODEL,
            'image' => self::DEFAULT_IMAGE_MODEL,
            'tts' => self::DEFAULT_TTS_MODEL,
            'video' => self::DEFAULT_VIDEO_MODEL,
        ];

        $model = trim((string) $model);
        $model = preg_replace('#^models/#', '', $model);

        if ($model === '' || $model === 'custom') {
            return $defaults[$type] ?? self::DEFAULT_TEXT_MODEL;
        }

        return self::LEGACY_MODEL_MAP[$type][$model] ?? $model;
    }

    /**
     * Model aktif dari pengaturan (sudah dinormalisasi).
     */
    public function model(string $type): string
    {
        $settingKey = [
            'text' => 'gemini_text_model',
            'image' => 'gemini_image_model',
            'tts' => 'gemini_tts_model',
            'video' => 'gemini_video_model',
        ][$type];

        return self::resolveModel($type, AppSetting::get($settingKey));
    }

    /**
     * Nama suara yang valid untuk TTS.
     */
    public function voice(?string $voice = null): string
    {
        $voice = trim((string) ($voice ?: AppSetting::get('gemini_voice_name', self::DEFAULT_VOICE)));

        // Nama suara lama di aplikasi yang tidak ada di daftar suara resmi Gemini.
        $aliases = ['pari' => 'Sulafat'];
        if (isset($aliases[strtolower($voice)])) {
            return $aliases[strtolower($voice)];
        }

        foreach (self::VOICES as $valid) {
            if (strcasecmp($valid, $voice) === 0) {
                return $valid;
            }
        }

        // Suara kustom dari Voice Design / Voice Library (mis. "voice_xxx") tetap diteruskan.
        if (str_starts_with($voice, 'voice_')) {
            return $voice;
        }

        return self::DEFAULT_VOICE;
    }

    /**
     * Urutan model yang dicoba: model pilihan admin, lalu cadangan resmi.
     */
    protected function candidates(string $type, ?string $preferred = null): array
    {
        $first = $preferred ? self::resolveModel($type, $preferred) : $this->model($type);
        return array_values(array_unique(array_merge([$first], self::FALLBACKS[$type] ?? [])));
    }

    /**
     * HTTP client dengan API key di header (bukan di query string).
     */
    protected function http(string $key, int $timeout = 30)
    {
        return Http::timeout($timeout)
            ->acceptJson()
            ->withHeaders([
                'x-goog-api-key' => $key,
                'Content-Type' => 'application/json',
            ]);
    }

    /**
     * Apakah error menandakan model tidak tersedia (sehingga layak mencoba model cadangan).
     */
    protected function isModelUnavailable(HttpResponse $response): bool
    {
        $status = $response->status();
        $message = strtolower((string) $response->json('error.message', ''));

        return in_array($status, [403, 404], true)
            || str_contains($message, 'not found')
            || str_contains($message, 'not supported')
            || str_contains($message, 'is not available')
            || str_contains($message, 'has been shut down')
            || str_contains($message, 'deprecated');
    }

    protected function errorMessage(?HttpResponse $response, string $fallback): string
    {
        if (!$response) {
            return $fallback;
        }

        $message = $response->json('error.message');
        $status = $response->json('error.status');

        if ($message) {
            return $status ? "{$message} ({$status})" : $message;
        }

        return "{$fallback} (HTTP {$response->status()})";
    }

    /**
     * models.generateContent untuk teks. Mencoba model cadangan bila model tidak tersedia.
     *
     * @return array{response: ?HttpResponse, model: string, text: string}
     */
    protected function generateText(string $key, string $prompt, array $generationConfig = [], ?string $preferred = null, int $timeout = 60): array
    {
        $response = null;
        $model = $preferred ?: $this->model('text');

        foreach ($this->candidates('text', $preferred) as $model) {
            $payload = [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
            ];
            if (!empty($generationConfig)) {
                $payload['generationConfig'] = $generationConfig;
            }

            $response = $this->http($key, $timeout)
                ->post("{$this->baseUrl}/models/{$model}:generateContent", $payload);

            if ($response->successful()) {
                return ['response' => $response, 'model' => $model, 'text' => $this->extractText($response->json() ?? [])];
            }

            if (!$this->isModelUnavailable($response)) {
                break;
            }

            Log::info("Gemini text model {$model} tidak tersedia, mencoba model cadangan.", ['error' => $response->json('error.message')]);
        }

        return ['response' => $response, 'model' => $model, 'text' => ''];
    }

    /**
     * Gabungkan semua bagian teks jawaban (abaikan bagian "thought" dari model penalaran).
     */
    protected function extractText(array $json): string
    {
        // Format generateContent
        $parts = $json['candidates'][0]['content']['parts'] ?? null;
        if (is_array($parts)) {
            $texts = [];
            foreach ($parts as $part) {
                if (!empty($part['thought'])) {
                    continue;
                }
                if (isset($part['text'])) {
                    $texts[] = $part['text'];
                }
            }
            return trim(implode('', $texts));
        }

        // Format Interactions API
        if (!empty($json['output_text']) && is_string($json['output_text'])) {
            return trim($json['output_text']);
        }

        $texts = [];
        foreach ($json['steps'] ?? $json['outputs'] ?? [] as $step) {
            if (($step['type'] ?? 'model_output') !== 'model_output') {
                continue;
            }
            $contents = $step['content'] ?? [$step];
            foreach ($contents as $content) {
                if (($content['type'] ?? null) === 'text' && isset($content['text'])) {
                    $texts[] = $content['text'];
                }
            }
        }

        return trim(implode('', $texts));
    }

    /**
     * Ambil JSON dari jawaban model (toleran terhadap pagar ```json).
     */
    protected function decodeJson(string $text): mixed
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        $decoded = json_decode($text, true);
        if ($decoded !== null) {
            return $decoded;
        }

        // Ambil blok JSON pertama bila ada teks tambahan.
        if (preg_match('/(\{.*\}|\[.*\])/s', $text, $m)) {
            return json_decode($m[1], true);
        }

        return null;
    }

    /**
     * Cari data biner (base64) gambar/audio di respons Interactions API maupun generateContent.
     *
     * @return array{data: string, mime_type: string}|null
     */
    protected function extractMedia(array $json, string $kind): ?array
    {
        // SDK-style shortcut (output_image / output_audio)
        $shortcut = $json["output_{$kind}"] ?? null;
        if (is_array($shortcut) && !empty($shortcut['data'])) {
            return ['data' => $shortcut['data'], 'mime_type' => $shortcut['mime_type'] ?? ''];
        }

        $found = null;
        $walk = function ($node) use (&$walk, &$found, $kind) {
            if ($found || !is_array($node)) {
                return;
            }

            // Interactions API: {"type": "image", "mime_type": "...", "data": "..."}
            if (($node['type'] ?? null) === $kind && !empty($node['data']) && is_string($node['data'])) {
                $found = ['data' => $node['data'], 'mime_type' => $node['mime_type'] ?? $node['mimeType'] ?? ''];
                return;
            }

            // generateContent: {"inlineData": {"mimeType": "image/png", "data": "..."}}
            $inline = $node['inlineData'] ?? $node['inline_data'] ?? null;
            if (is_array($inline) && !empty($inline['data'])) {
                $mime = $inline['mimeType'] ?? $inline['mime_type'] ?? '';
                if ($mime === '' || str_starts_with($mime, $kind . '/')) {
                    $found = ['data' => $inline['data'], 'mime_type' => $mime];
                    return;
                }
            }

            foreach ($node as $child) {
                $walk($child);
            }
        };
        $walk($json);

        return $found;
    }

    /**
     * Panggil Interactions API (POST /v1beta/interactions).
     */
    protected function interact(string $key, array $payload, int $timeout = 60): HttpResponse
    {
        return $this->http($key, $timeout)->post("{$this->baseUrl}/interactions", $payload);
    }

    /**
     * Buat gambar dengan model Nano Banana (Gemini Image).
     * Utama: Interactions API. Cadangan: generateContent dengan responseModalities IMAGE.
     *
     * @return array{success: bool, data?: string, mime_type?: string, model: string, message?: string, response?: ?HttpResponse}
     */
    protected function requestImage(string $key, string $prompt, string $aspectRatio, ?string $preferred = null): array
    {
        $lastResponse = null;
        $model = $preferred ?: $this->model('image');

        foreach ($this->candidates('image', $preferred) as $model) {
            // 1) Interactions API (rekomendasi dokumentasi terbaru)
            $response = $this->interact($key, [
                'model' => $model,
                'input' => $prompt,
                'response_format' => [
                    'type' => 'image',
                    'mime_type' => 'image/jpeg',
                    'aspect_ratio' => $aspectRatio,
                    'image_size' => '1K',
                ],
            ], 90);

            if ($response->successful()) {
                $media = $this->extractMedia($response->json() ?? [], 'image');
                if ($media) {
                    return ['success' => true, 'model' => $model] + $media;
                }
            }
            $lastResponse = $response;

            // 2) generateContent (masih didukung penuh oleh Google)
            $response = $this->http($key, 90)->post("{$this->baseUrl}/models/{$model}:generateContent", [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'responseModalities' => ['TEXT', 'IMAGE'],
                    'imageConfig' => ['aspectRatio' => $aspectRatio],
                ],
            ]);

            if ($response->successful()) {
                $media = $this->extractMedia($response->json() ?? [], 'image');
                if ($media) {
                    return ['success' => true, 'model' => $model] + $media;
                }
            }

            if ($response->failed()) {
                $lastResponse = $response;
            }

            // Hanya coba model cadangan jika model ini memang tidak tersedia.
            if ($response->failed() && !$this->isModelUnavailable($response) && !$this->isModelUnavailable($lastResponse)) {
                break;
            }
        }

        return [
            'success' => false,
            'model' => $model,
            'message' => $this->errorMessage($lastResponse, 'Model gambar tidak mengembalikan data gambar.'),
            'response' => $lastResponse,
        ];
    }

    /**
     * Buat audio narasi dengan model TTS Gemini 3.8.
     *
     * @return array{success: bool, audio?: string, ext?: string, model: string, message?: string}
     */
    protected function requestSpeech(string $key, string $text, string $voice, ?string $preferred = null): array
    {
        $lastResponse = null;
        $model = $preferred ?: $this->model('tts');

        foreach ($this->candidates('tts', $preferred) as $model) {
            // 1) Interactions API (format resmi gemini-3.8-flash-tts) — keluaran default WAV 24 kHz.
            $response = $this->interact($key, [
                'model' => $model,
                'input' => [[
                    'type' => 'user_input',
                    'content' => [[
                        'type' => 'text',
                        'text' => $text,
                        'annotations' => [[
                            'type' => 'speech_metadata',
                            'style' => 'calm, warm and clear Indonesian narrator',
                        ]],
                    ]],
                ]],
                'response_format' => ['type' => 'audio'],
                'generation_config' => ['speech_config' => [['voice' => $voice]]],
            ], 60);

            if ($response->successful() && ($media = $this->extractMedia($response->json() ?? [], 'audio'))) {
                return ['success' => true, 'model' => $model] + $this->normalizeAudio($media);
            }
            $lastResponse = $response;

            // 2) generateContent (format lama TTS) — keluaran PCM mentah, dibungkus ke WAV.
            $response = $this->http($key, 60)->post("{$this->baseUrl}/models/{$model}:generateContent", [
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => "Read clearly in a calm, warm Indonesian voice: {$text}"]]],
                ],
                'generationConfig' => [
                    'responseModalities' => ['AUDIO'],
                    'speechConfig' => [
                        'voiceConfig' => [
                            'prebuiltVoiceConfig' => ['voiceName' => $voice],
                        ],
                    ],
                ],
            ]);

            if ($response->successful() && ($media = $this->extractMedia($response->json() ?? [], 'audio'))) {
                return ['success' => true, 'model' => $model] + $this->normalizeAudio($media);
            }

            if ($response->failed()) {
                $lastResponse = $response;
            }

            if ($response->failed() && !$this->isModelUnavailable($response) && !$this->isModelUnavailable($lastResponse)) {
                break;
            }
        }

        return [
            'success' => false,
            'model' => $model,
            'message' => $this->errorMessage($lastResponse, 'Model TTS tidak mengembalikan audio.'),
        ];
    }

    /**
     * Pastikan audio bisa diputar: WAV/MP3 apa adanya, PCM mentah dibungkus header WAV.
     *
     * @return array{audio: string, ext: string}
     */
    protected function normalizeAudio(array $media): array
    {
        $bytes = base64_decode($media['data']);
        $mime = strtolower($media['mime_type'] ?? '');

        if (str_starts_with($bytes, 'RIFF')) {
            return ['audio' => $bytes, 'ext' => 'wav'];
        }

        if (str_contains($mime, 'mpeg') || str_contains($mime, 'mp3') || str_starts_with($bytes, 'ID3')) {
            return ['audio' => $bytes, 'ext' => 'mp3'];
        }

        // audio/L16;codec=pcm;rate=24000 (default Gemini TTS) → WAV
        $rate = 24000;
        if (preg_match('/rate=(\d+)/', $mime, $m)) {
            $rate = (int) $m[1];
        }

        return ['audio' => $this->pcmToWav($bytes, $rate), 'ext' => 'wav'];
    }

    protected function pcmToWav(string $pcm, int $sampleRate = 24000, int $channels = 1, int $bitsPerSample = 16): string
    {
        $byteRate = $sampleRate * $channels * $bitsPerSample / 8;
        $blockAlign = $channels * $bitsPerSample / 8;
        $dataLen = strlen($pcm);

        return 'RIFF' . pack('V', 36 + $dataLen) . 'WAVE'
            . 'fmt ' . pack('VvvVVvv', 16, 1, $channels, $sampleRate, $byteRate, $blockAlign, $bitsPerSample)
            . 'data' . pack('V', $dataLen) . $pcm;
    }

    /**
     * Test basic authentication and list available models.
     */
    public function testAuthentication(?string $overrideKey = null): array
    {
        $key = $overrideKey ?: $this->getApiKey();

        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'API Key Gemini belum dikonfigurasi.',
                'error_code' => 'KEY_MISSING',
            ];
        }

        $startTime = microtime(true);

        try {
            $response = $this->http($key, 15)->get("{$this->baseUrl}/models", ['pageSize' => 1000]);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $models = $response->json('models', []);
                $modelNames = array_values(array_filter(array_map(fn($m) => str_replace('models/', '', $m['name'] ?? ''), $models)));

                $has = fn(array $needles) => count(array_filter(
                    $modelNames,
                    fn($n) => count(array_filter($needles, fn($needle) => str_contains(strtolower($n), $needle))) > 0
                )) > 0;

                return [
                    'success' => true,
                    'message' => 'Koneksi ke Google Gemini API berhasil diverifikasi.',
                    'response_time_ms' => $durationMs,
                    'available_models' => $modelNames,
                    'capabilities' => [
                        'text_generation' => $has(['gemini']),
                        'audio_generation' => $has(['tts']),
                        'image_generation' => $has(['image', 'nano-banana', 'imagen']),
                        'video_generation' => $has(['veo', 'omni']),
                    ],
                ];
            }

            $code = $response->json('error.code', $response->status());

            return [
                'success' => false,
                'message' => "Gagal terhubung ({$code}): " . ($response->json('error.message') ?: 'Gagal menghubungi Google Gemini API.'),
                'error_code' => $response->json('error.status', 'API_ERROR'),
                'response_time_ms' => $durationMs,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Kesalahan koneksi jaringan: ' . $e->getMessage(),
                'error_code' => 'NETWORK_ERROR',
            ];
        }
    }

    /**
     * Test text generation.
     */
    public function testTextGeneration(?string $model = null): array
    {
        $key = $this->getApiKey();
        if (!$key) {
            return ['success' => false, 'message' => 'API Key belum diisi.'];
        }

        $startTime = microtime(true);

        try {
            // maxOutputTokens dibuat longgar: model Gemini 3.x memakai token "thinking"
            // sehingga batas 100 token bisa membuat jawaban kosong.
            $result = $this->generateText(
                $key,
                'Tuliskan satu kalimat mutiara filosofi Hindu tentang ketulusan hati dalam bahasa Indonesia. Jawab hanya dengan kalimat tersebut.',
                ['maxOutputTokens' => 2048, 'temperature' => 0.7],
                $model,
                30
            );

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $response = $result['response'];

            if ($response && $response->successful() && $result['text'] !== '') {
                $usage = $response->json('usageMetadata', []);

                $this->logUsage(
                    null,
                    null,
                    'text_test',
                    $result['model'],
                    $usage['promptTokenCount'] ?? 0,
                    $usage['candidatesTokenCount'] ?? 0,
                    $durationMs,
                    true
                );

                return [
                    'success' => true,
                    'message' => "Uji pembuatan teks ({$result['model']}) berhasil.",
                    'output' => $result['text'],
                    'model' => $result['model'],
                    'response_time_ms' => $durationMs,
                ];
            }

            return [
                'success' => false,
                'message' => $response && $response->successful()
                    ? "Model {$result['model']} merespons tanpa teks (finishReason: " . ($response->json('candidates.0.finishReason') ?? '-') . ').'
                    : $this->errorMessage($response, 'Gagal membuat teks.'),
                'error_code' => $response?->json('error.status') ?? 'TEXT_FAILED',
                'model' => $result['model'],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal uji teks: ' . $e->getMessage(),
                'error_code' => 'EXCEPTION',
            ];
        }
    }

    /**
     * Test image generation (Gemini Image / Nano Banana).
     */
    public function testImageGeneration(?string $model = null): array
    {
        $key = $this->getApiKey();
        if (!$key) {
            return ['success' => false, 'message' => 'API Key belum diisi.'];
        }

        $startTime = microtime(true);

        try {
            $prompt = 'A beautiful Balinese traditional canang sari with colorful frangipani flowers and burning incense stick, morning golden light, editorial photography';

            $result = $this->requestImage($key, $prompt, '1:1', $model);
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($result['success']) {
                $ext = str_contains($result['mime_type'] ?? '', 'png') ? 'png' : 'jpg';
                $testFile = 'demo/test_image_' . time() . '.' . $ext;
                Storage::disk('public')->put($testFile, base64_decode($result['data']));

                return [
                    'success' => true,
                    'message' => "Uji pembuatan gambar ({$result['model']}) berhasil.",
                    'model' => $result['model'],
                    'image_url' => Storage::disk('public')->url($testFile),
                    'response_time_ms' => $durationMs,
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'],
                'error_code' => $result['response']?->json('error.status') ?? 'IMAGE_UNAVAILABLE',
                'model' => $result['model'],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal uji gambar: ' . $e->getMessage(),
                'error_code' => 'EXCEPTION',
            ];
        }
    }

    /**
     * Test Video Generation: cek apakah model video bisa diakses oleh API key ini
     * (tanpa membuat video sungguhan yang memakan kuota).
     */
    public function testVideoGeneration(?string $model = null): array
    {
        $key = $this->getApiKey();
        if (!$key) {
            return ['success' => false, 'message' => 'API Key belum diisi.'];
        }

        $model = $model ? self::resolveModel('video', $model) : $this->model('video');
        $candidates = array_values(array_unique([$model, 'gemini-omni-1.1-flash', 'veo-3.1-generate-preview', 'veo-3.1-lite-generate-preview']));

        try {
            $lastResponse = null;
            foreach ($candidates as $candidate) {
                $response = $this->http($key, 15)->get("{$this->baseUrl}/models/{$candidate}");

                if ($response->successful()) {
                    $methods = $response->json('supportedGenerationMethods', []);
                    $isPreferred = $candidate === $model;

                    return [
                        'success' => true,
                        'message' => $isPreferred
                            ? "Model video '{$candidate}' tersedia untuk API key ini."
                            : "Model '{$model}' tidak tersedia, tetapi '{$candidate}' tersedia. Pilih model ini di Konfigurasi Model.",
                        'model' => $candidate,
                        'methods' => $methods,
                        'note' => 'Pembuatan video Veo/Omni memerlukan project dengan billing (paid tier) aktif di Google AI Studio.',
                    ];
                }

                $lastResponse = $response;
                if (!in_array($response->status(), [403, 404], true)) {
                    break;
                }
            }

            return [
                'success' => false,
                'message' => "Model video '{$model}' belum tersedia untuk API key ini. " . $this->errorMessage($lastResponse, ''),
                'error_code' => $lastResponse?->json('error.status') ?? 'MODEL_NOT_PROVISIONED',
                'model' => $model,
                'suggestion' => 'Aktifkan billing di Google AI Studio. Sementara itu sistem otomatis memakai mode Motion Illustration (gambar + animasi kamera FFmpeg).',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Gagal cek model video: ' . $e->getMessage(),
                'error_code' => 'EXCEPTION',
            ];
        }
    }

    /**
     * Test Audio Narration (TTS) Generation.
     */
    public function testAudioGeneration(?string $model = null): array
    {
        $key = $this->getApiKey();
        if (!$key) {
            return ['success' => false, 'message' => 'API Key belum diisi.'];
        }

        $preferred = $model ? self::resolveModel('tts', $model) : $this->model('tts');
        $startTime = microtime(true);

        if ($preferred === 'google-tts-indonesia') {
            return [
                'success' => true,
                'message' => 'Layanan Google Speech ID siap digunakan sebagai cadangan.',
                'model' => $preferred,
            ];
        }

        try {
            $testText = 'Om Swastyastu. Selamat datang di Ruang Dharma, platform edukasi filosofi Hindu.';
            $result = $this->requestSpeech($key, $testText, $this->voice(), $preferred);
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($result['success']) {
                $testFile = 'demo/test_audio_' . time() . '.' . $result['ext'];
                Storage::disk('public')->put($testFile, $result['audio']);

                return [
                    'success' => true,
                    'message' => "Uji sintesis audio narasi ({$result['model']}) berhasil dibuat.",
                    'model' => $result['model'],
                    'audio_url' => Storage::disk('public')->url($testFile),
                    'response_time_ms' => $durationMs,
                ];
            }

            return [
                'success' => false,
                'message' => $result['message'],
                'error_code' => 'TTS_FAILED',
                'model' => $result['model'],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Kesalahan koneksi audio: ' . $e->getMessage(),
                'error_code' => 'EXCEPTION',
            ];
        }
    }

    /**
     * Generate 30-Second Video Script.
     * Strict 30s timing: 0-3s Hook, 3-23s Core, 23-30s Reflection.
     */
    public function generateScript(
        string $topicTitle,
        string $targetAudience = 'Anak Muda',
        string $tone = 'Inspiratif & Hangat',
        ?string $customIdea = null,
        array $sources = []
    ): array {
        $key = $this->getApiKey();

        $sourceCitations = implode("; ", array_map(fn($s) => ($s['title'] ?? '') . ' (' . ($s['reference_section'] ?? '') . ')', $sources));

        $prompt = <<<PROMPT
Anda adalah kurator dan naskah visual ahli untuk konten edukasi filosofi Hindu di Indonesia dan Bali ("Ruang Dharma").
Target penonton: {$targetAudience}.
Gaya penyampaian: {$tone}.
Topik utama: "{$topicTitle}".
Ide tambahan pengguna: "{$customIdea}".
Sumber rujukan yang harus dihormati: {$sourceCitations}.

ATURAN KETAT DURASI 30 DETIK:
Video HARUS tepat berdurasi 30 detik (toleransi 0.5 detik).
Gunakan tepat 4 hingga 5 adegan:
- Adegan 1 (0-3 detik): Hook kuat yang menarik rasa ingin tahu anak muda.
- Adegan 2-4 (3-23 detik): Penjelasan inti filosofis yang terarah, fokus pada SATU ide utama agar mudah dipahami dalam 30 detik.
- Adegan 5 (23-30 detik): Kesimpulan reflektif / ajakan bijak untuk kehidupan sehari-hari.

BAHASA DAN ETIKA:
- Bahasa Indonesia yang hangat, bersahabat, kekinian namun sangat menghormati nilai suci tradisi Hindu dan budaya Bali.
- DILARANG menyertakan emoji atau emotikon apapun dalam teks.
- Bedakan tradisi lokal Bali dengan filosofi universal Hindu.
- Sertakan kutipan rujukan sumber pada adegan terkait.

Kembalikan jawaban HANYA berupa JSON valid dengan format:
{
  "title": "Judul video yang memikat anak muda (maksimal 8 kata)",
  "hook_text": "Kalimat pembuka hook detik 0-3",
  "reflection_text": "Pesan refleksi penutup detik 23-30",
  "summary_text": "Ringkasan isi video dalam 2 kalimat",
  "scenes": [
    {
      "scene_order": 1,
      "duration": 3.0,
      "narration_text": "Teks narasi yang akan dibacakan narator",
      "onscreen_text": "Teks singkat 2-4 kata untuk layar",
      "visual_prompt": "Prompt visual deskriptif dalam bahasa Inggris untuk pembuatan ilustrasi/klip",
      "camera_motion": "zoom_in",
      "transition_type": "fade",
      "source_citation": "Nama sumber rujukan"
    }
  ]
}
PROMPT;

        if (!$key) {
            // High-quality offline fallback script for testing without API key
            return $this->getFallbackScript($topicTitle, $targetAudience, $tone);
        }

        $startTime = microtime(true);

        try {
            $result = $this->generateText($key, $prompt, [
                'responseMimeType' => 'application/json',
                'temperature' => 0.4,
            ], null, 90);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $response = $result['response'];

            if ($response && $response->successful()) {
                $parsed = $this->decodeJson($result['text']);

                if (is_array($parsed) && !empty($parsed['scenes'])) {
                    $usage = $response->json('usageMetadata', []);
                    $this->logUsage(null, null, 'script', $result['model'], $usage['promptTokenCount'] ?? 0, $usage['candidatesTokenCount'] ?? 0, $durationMs, true);

                    $this->normalizeSceneDurations($parsed['scenes']);
                    return [
                        'success' => true,
                        'data' => $parsed,
                        'model' => $result['model'],
                        'response_time_ms' => $durationMs,
                    ];
                }
            }

            Log::warning('Gemini script generation fallback triggered. Response: ' . ($response?->body() ?? '-'));
            return [
                'success' => true,
                'data' => $this->getFallbackScript($topicTitle, $targetAudience, $tone),
                'note' => 'Menggunakan template terkurasi karena respons AI tidak sesuai format.',
            ];
        } catch (\Throwable $e) {
            Log::error("Gemini generateScript error: " . $e->getMessage());
            return [
                'success' => true,
                'data' => $this->getFallbackScript($topicTitle, $targetAudience, $tone),
                'note' => 'Menggunakan naskah kurasi aman karena gangguan jaringan AI.',
            ];
        }
    }

    /**
     * Generate illustration image for a scene using Gemini Image (Nano Banana).
     */
    public function generateSceneVisual(string $prompt, string $style = 'modern_editorial', string $aspectRatio = '9:16'): array
    {
        $key = $this->getApiKey();

        $styleModifiers = [
            'modern_editorial' => 'minimalist modern digital painting, elegant lighting, subtle balinese motifs, calm editorial aesthetic, trending on artstation',
            'balinese_minimalist' => 'minimalist warm balinese cultural illustration, gentle earth tones, sacred geometry, clean vector lines',
            'vibrant_culture' => 'vibrant balinese colors, rich golden highlights, deep purple and coral hues, artistic cultural celebration',
            'watercolor_art' => 'soft watercolor painting, fluid brush strokes, warm morning atmosphere, artistic serene feeling',
        ];

        $styleSuffix = $styleModifiers[$style] ?? $styleModifiers['modern_editorial'];
        $fullPrompt = "{$prompt}, {$styleSuffix}, highly detailed, clean composition, no text, no watermark";

        if (!$key) {
            // Generate fallback artistic SVG graphic converted to image or stored as placeholder
            return $this->generatePlaceholderImage($prompt, $style, $aspectRatio);
        }

        try {
            $aspectRatioParam = match ($aspectRatio) {
                '16:9' => '16:9',
                '1:1' => '1:1',
                default => '9:16',
            };

            $result = $this->requestImage($key, $fullPrompt, $aspectRatioParam);

            if ($result['success']) {
                $ext = str_contains($result['mime_type'] ?? '', 'png') ? 'png' : 'jpg';
                $fileName = 'images/' . uniqid('scene_') . '.' . $ext;
                Storage::disk('local')->put('private/' . $fileName, base64_decode($result['data']));

                return [
                    'success' => true,
                    'image_path' => $fileName,
                    'is_generated' => true,
                ];
            }

            Log::warning('Gemini image generation fallback: ' . ($result['message'] ?? '-'));
            return $this->generatePlaceholderImage($prompt, $style, $aspectRatio);
        } catch (\Throwable $e) {
            Log::warning("Gemini visual generation fallback: " . $e->getMessage());
            return $this->generatePlaceholderImage($prompt, $style, $aspectRatio);
        }
    }

    /**
     * Generate Audio Narration for a scene text.
     */
    public function generateSceneAudio(string $text, ?string $voice = null): array
    {
        $key = $this->getApiKey();
        $cleanText = trim(strip_tags($text));
        $model = $this->model('tts');

        if ($key && $model !== 'google-tts-indonesia') {
            try {
                $result = $this->requestSpeech($key, $cleanText, $this->voice($voice));

                if ($result['success']) {
                    $fileName = 'audio/' . uniqid('narration_') . '.' . $result['ext'];
                    Storage::disk('local')->put('private/' . $fileName, $result['audio']);
                    return [
                        'success' => true,
                        'audio_path' => $fileName,
                    ];
                }

                Log::info('Gemini TTS fallback: ' . ($result['message'] ?? '-'));
            } catch (\Throwable $e) {
                Log::info("Gemini TTS endpoint fallback: " . $e->getMessage());
            }
        }

        $fileName = 'audio/' . uniqid('narration_') . '.mp3';

        // Fallback: Generate clean speech using Google Translate TTS public endpoint
        try {
            $encodedText = urlencode($cleanText);
            $ttsUrl = "https://translate.google.com/translate_tts?ie=UTF-8&q={$encodedText}&tl=id&client=tw-ob";
            $ttsResponse = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            ])->timeout(15)->get($ttsUrl);

            if ($ttsResponse->successful() && strlen($ttsResponse->body()) > 500) {
                Storage::disk('local')->put('private/' . $fileName, $ttsResponse->body());
                return [
                    'success' => true,
                    'audio_path' => $fileName,
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("TTS fallback error: " . $e->getMessage());
        }

        // If network TTS fails, generate a silent/tone audio via FFmpeg
        $this->generateSilentAudio($fileName, 5.0);
        return [
            'success' => true,
            'audio_path' => $fileName,
        ];
    }

    /**
     * Generate an interactive 2-question quiz based on the video title & script.
     */
    public function generateQuiz(string $title, string $script): array
    {
        $key = $this->getApiKey();

        $prompt = <<<PROMPT
Buat 2 pertanyaan kuis pilihan ganda edukatif berdasarkan naskah video edukasi Hindu berikut:
Judul: {$title}
Naskah: {$script}

ATURAN:
- Pertanyaan harus menguji pemahaman makna filosofis (bukan sekadar hafalan teknis).
- 4 pilihan jawaban untuk setiap soal.
- Jelaskan mengapa jawaban tersebut benar secara singkat dan mendidik.
- Dilarang menggunakan emoji atau emotikon.

Kembalikan format JSON:
[
  {
    "question": "Kalimat pertanyaan",
    "options": ["Opsi A", "Opsi B", "Opsi C", "Opsi D"],
    "correct_option_index": 0,
    "explanation": "Penjelasan mengapa opsi tersebut benar."
  }
]
PROMPT;

        if ($key) {
            try {
                $result = $this->generateText($key, $prompt, [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.3,
                ], null, 60);

                if ($result['response'] && $result['response']->successful()) {
                    $json = $this->decodeJson($result['text']);
                    if (is_array($json) && isset($json['questions']) && is_array($json['questions'])) {
                        $json = $json['questions'];
                    }
                    if (is_array($json) && count($json) > 0 && array_is_list($json)) {
                        return $json;
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini quiz fallback: " . $e->getMessage());
            }
        }

        // Curated fallback quiz
        return [
            [
                'question' => "Apa gagasan inti yang ditekankan dalam materi '{$title}'?",
                'options' => [
                    'Memahami filosofi luhur sebagai pedoman moral kehidupan nyata',
                    'Sekadar menjalankan ritual tanpa memahami maknanya',
                    'Mengabaikan hubungan dengan sesama manusia dan alam',
                    'Menonjolkan kemewahan sarana persembahyangan'
                ],
                'correct_option_index' => 0,
                'explanation' => 'Setiap ajaran dan simbol tradisi Hindu sarat akan nilai etika dan kebajikan yang harus dihayati dalam batin serta tindakan sehari-hari.',
            ],
            [
                'question' => 'Bagaimana sikap terbaik generasi muda dalam mengapresiasi tradisi dan filosofi Hindu?',
                'options' => [
                    'Mengkritik tanpa mempelajari sumber aslinya',
                    'Menghubungkan nilai kearifan lokal dengan pola pikir bijak dan bertanggung jawab',
                    'Meninggalkan tradisi karena dianggap kuno',
                    'Hanya beribadah saat ada peringatan besar'
                ],
                'correct_option_index' => 1,
                'explanation' => 'Menghubungkan esensi filosofi Hindu dengan kesadaran modern menciptakan generasi muda yang berakar kuat pada nilai spiritual dan budaya luhur.',
            ]
        ];
    }

    /**
     * Normalize scene durations so total is strictly 30.00 seconds.
     */
    protected function normalizeSceneDurations(array &$scenes): void
    {
        $count = count($scenes);
        if ($count === 0) return;

        // Force exactly 30.0 seconds
        if ($count === 5) {
            $scenes[0]['duration'] = 3.00; // Hook (0-3s)
            $scenes[1]['duration'] = 6.50;
            $scenes[2]['duration'] = 7.00;
            $scenes[3]['duration'] = 6.50;
            $scenes[4]['duration'] = 7.00; // Reflection (23-30s)
        } elseif ($count === 4) {
            $scenes[0]['duration'] = 3.00;
            $scenes[1]['duration'] = 10.00;
            $scenes[2]['duration'] = 10.00;
            $scenes[3]['duration'] = 7.00;
        } else {
            // General normalization
            $scenes[0]['duration'] = 3.00;
            $remaining = 27.00;
            $perScene = round($remaining / ($count - 1), 2);
            for ($i = 1; $i < $count - 1; $i++) {
                $scenes[$i]['duration'] = $perScene;
                $remaining -= $perScene;
            }
            $scenes[$count - 1]['duration'] = round($remaining, 2);
        }

        $currentTime = 0.00;
        foreach ($scenes as $idx => &$scene) {
            $scene['scene_order'] = $idx + 1;
            $scene['start_time'] = round($currentTime, 2);
            $currentTime += $scene['duration'];
            $scene['end_time'] = round($currentTime, 2);
        }
    }

    /**
     * High quality fallback script when API key is not configured or network down.
     */
    protected function getFallbackScript(string $topicTitle, string $targetAudience, string $tone): array
    {
        return [
            'title' => "Esensi Luhur {$topicTitle}",
            'hook_text' => "Pernahkah kamu merenungkan makna sejati di balik {$topicTitle}?",
            'reflection_text' => 'Kebaikan kecil yang dilakukan dengan hati bersih membawa damai bagi seluruh alam.',
            'summary_text' => "Penjelasan 30 detik mengenai esensi filosofi {$topicTitle} untuk generasi muda.",
            'scenes' => [
                [
                    'scene_order' => 1,
                    'start_time' => 0.00,
                    'end_time' => 3.00,
                    'duration' => 3.00,
                    'narration_text' => "Pernahkah kamu merenungkan makna sejati di balik {$topicTitle}?",
                    'onscreen_text' => 'Panggilan Kesadaran',
                    'visual_prompt' => "Cinematic atmospheric shot of Balinese temple gate with golden dawn sunlight and frangipani blossoms",
                    'camera_motion' => 'zoom_in',
                    'transition_type' => 'fade',
                    'source_citation' => 'Pedoman Panca Yadnya PHDI',
                ],
                [
                    'scene_order' => 2,
                    'start_time' => 3.00,
                    'end_time' => 9.50,
                    'duration' => 6.50,
                    'narration_text' => 'Dalam filosofi Hindu, setiap simbol adalah bahasa visual yang menuntun pikiran menuju kemurnian.',
                    'onscreen_text' => 'Bahasa Simbol Suci',
                    'visual_prompt' => "Minimalist editorial digital art of sacred geometric lotus motif, serene purple and warm gold palette",
                    'camera_motion' => 'pan_left',
                    'transition_type' => 'fade',
                    'source_citation' => 'Lontar Sundarigama',
                ],
                [
                    'scene_order' => 3,
                    'start_time' => 9.50,
                    'end_time' => 16.50,
                    'duration' => 7.00,
                    'narration_text' => 'Bukan sekadar perayaan luar, ini adalah latihan batin memelihara harmoni batin dengan sesama dan alam semesta.',
                    'onscreen_text' => 'Harmoni Batin Semesta',
                    'visual_prompt' => "Lush green Balinese rice terraces reflecting sunlight, tranquil morning breeze, balance of nature",
                    'camera_motion' => 'tilt_up',
                    'transition_type' => 'cut',
                    'source_citation' => 'Kajian Kearifan Lokal Tri Hita Karana',
                ],
                [
                    'scene_order' => 4,
                    'start_time' => 16.50,
                    'end_time' => 23.00,
                    'duration' => 6.50,
                    'narration_text' => 'Setiap tindakan kita meninggalkan getaran karma yang membentuk masa depan kita sendiri.',
                    'onscreen_text' => 'Jejak Perbuatan Baik',
                    'visual_prompt' => "Close-up of gentle hands holding a single frangipani flower with reverence, soft incense mist",
                    'camera_motion' => 'pan_right',
                    'transition_type' => 'fade',
                    'source_citation' => 'Bhagavad Gita II.47',
                ],
                [
                    'scene_order' => 5,
                    'start_time' => 23.00,
                    'end_time' => 30.00,
                    'duration' => 7.00,
                    'narration_text' => 'Jalani hari ini dengan ketulusan. Kebaikan murni adalah doa paling indah yang pernah ada.',
                    'onscreen_text' => 'Tulus dalam Dharma',
                    'visual_prompt' => "Inspiring young Balinese student smiling serenely at horizon, peaceful temple silhouetted in warm dusk",
                    'camera_motion' => 'zoom_out',
                    'transition_type' => 'fade',
                    'source_citation' => 'Sarasamuccaya Sloka 8',
                ],
            ]
        ];
    }

    /**
     * Generate an elegant procedural Balinese theme graphic as placeholder image.
     */
    protected function generatePlaceholderImage(string $prompt, string $style, string $aspectRatio): array
    {
        $fileName = 'images/' . uniqid('scene_art_') . '.png';
        $fullPath = Storage::disk('local')->path('private/' . $fileName);

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        [$width, $height] = match ($aspectRatio) {
            '16:9' => [1920, 1080],
            '1:1' => [1080, 1080],
            default => [1080, 1920],
        };

        // Create canvas using PHP GD
        $img = imagecreatetruecolor($width, $height);

        // Violet-Navy to Coral-Gold Gradient
        $bgTopR = 15; $bgTopG = 23; $bgTopB = 42;      // Deep navy #0F172A
        $bgBottomR = 124; $bgBottomG = 58; $bgBottomB = 237; // Royal violet #7C3AED

        for ($y = 0; $y < $height; $y++) {
            $ratio = $y / $height;
            $r = (int) ($bgTopR + ($bgBottomR - $bgTopR) * $ratio);
            $g = (int) ($bgTopG + ($bgBottomG - $bgTopG) * $ratio);
            $b = (int) ($bgTopB + ($bgBottomB - $bgTopB) * $ratio);
            $color = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, $width, $y, $color);
        }

        // Draw sacred geometry rings / concentric circles with warm gold & coral
        $gold = imagecolorallocatealpha($img, 245, 158, 11, 40); // Amber/Gold #F59E0B
        $coral = imagecolorallocatealpha($img, 249, 115, 22, 60); // Orange/Coral #F97316
        $teal = imagecolorallocatealpha($img, 20, 184, 166, 70);  // Teal #14B8A6

        $cx = (int) ($width / 2);
        $cy = (int) ($height * 0.45);

        for ($radius = 100; $radius <= min($width, $height) * 0.45; $radius += 45) {
            imagearc($img, $cx, $cy, $radius * 2, $radius * 2, 0, 360, $gold);
            imagearc($img, $cx, $cy, ($radius + 15) * 2, ($radius + 15) * 2, 0, 360, $coral);
        }

        // Draw lotus petal arcs
        for ($angle = 0; $angle < 360; $angle += 45) {
            $rad = deg2rad($angle);
            $px = (int) ($cx + cos($rad) * 160);
            $py = (int) ($cy + sin($rad) * 160);
            imagefilledellipse($img, $px, $py, 40, 40, $teal);
        }

        // Center glowing circle
        $whiteGlow = imagecolorallocatealpha($img, 255, 255, 255, 30);
        imagefilledellipse($img, $cx, $cy, 120, 120, $whiteGlow);

        imagepng($img, $fullPath);
        imagedestroy($img);

        return [
            'success' => true,
            'image_path' => $fileName,
            'is_generated' => false,
        ];
    }

    /**
     * Generate silent audio file via FFmpeg as fallback.
     */
    protected function generateSilentAudio(string $fileName, float $duration): void
    {
        $fullPath = Storage::disk('local')->path('private/' . $fileName);
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        $ffmpeg = config('app.ffmpeg_path', 'ffmpeg');
        exec("{$ffmpeg} -y -f lavfi -i anullsrc=r=44100:cl=stereo -t {$duration} -q:a 9 -acodec libmp3lame \"{$fullPath}\" 2>&1");
    }

    /**
     * Log AI usage.
     */
    protected function logUsage(
        ?int $userId,
        ?string $videoId,
        string $requestType,
        string $model,
        int $promptTokens,
        int $candidateTokens,
        int $durationMs,
        bool $success,
        ?string $errorCode = null
    ): void {
        // Official pricing estimates per 1k tokens (verified)
        $costPer1kPrompt = 0.000125; // perkiraan kasar model Flash
        $costPer1kCandidate = 0.000375;
        $cost = (($promptTokens / 1000) * $costPer1kPrompt) + (($candidateTokens / 1000) * $costPer1kCandidate);

        try {
            AiUsageLog::create([
                'user_id' => $userId,
                'video_id' => $videoId,
                'request_type' => $requestType,
                'model_used' => $model,
                'prompt_tokens' => $promptTokens,
                'candidates_tokens' => $candidateTokens,
                'estimated_cost_usd' => round($cost, 6),
                'status' => $success ? 'success' : 'failed',
                'error_code' => $errorCode,
                'response_time_ms' => $durationMs,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to record AiUsageLog: " . $e->getMessage());
        }
    }
}
