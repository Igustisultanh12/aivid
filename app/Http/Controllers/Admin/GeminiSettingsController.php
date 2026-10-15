<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeminiSettingsController extends Controller
{
    public function index(GeminiService $gemini): Response
    {
        $isConfigured = AppSetting::has('gemini_api_key');
        $maskedKey = AppSetting::getMasked('gemini_api_key');

        $settings = [
            'is_configured' => $isConfigured,
            'masked_key' => $maskedKey,
            'text_model' => AppSetting::get('gemini_text_model', 'gemini-3.0-flash'),
            'image_model' => AppSetting::get('gemini_image_model', 'imagen-3.0-generate-002'),
            'tts_model' => AppSetting::get('gemini_tts_model', 'gemini-3.8-flash-tts'),
            'video_model' => AppSetting::get('gemini_video_model', 'gemini-omni-1.1-flash'),
            'voice_name' => AppSetting::get('gemini_voice_name', 'Pari'),
            'max_video_per_user' => (int) AppSetting::get('app_max_video_per_user', 10),
            'daily_render_limit' => (int) AppSetting::get('app_daily_render_limit', 50),
            'last_tested_at' => AppSetting::get('gemini_last_tested_at'),
            'last_test_result' => json_decode(AppSetting::get('gemini_last_test_result', '{}'), true),
        ];

        // Available models list
        $modelOptions = [
            'text' => [
                ['id' => 'gemini-3.0-flash', 'name' => 'Gemini 3.0 Flash (Rekomendasi Utama Teks Google - Cepat & Cerdas)'],
                ['id' => 'gemini-3.8-flash', 'name' => 'Gemini 3.8 Flash'],
                ['id' => 'gemini-3.0-pro', 'name' => 'Gemini 3.0 Pro (Penalaran Mendalam)'],
                ['id' => 'gemini-2.5-flash', 'name' => 'Gemini 2.5 Flash'],
                ['id' => 'gemini-2.0-flash', 'name' => 'Gemini 2.0 Flash'],
                ['id' => 'gemini-1.5-flash', 'name' => 'Gemini 1.5 Flash'],
                ['id' => 'gemini-1.5-pro', 'name' => 'Gemini 1.5 Pro'],
            ],
            'image' => [
                ['id' => 'imagen-3.0-generate-002', 'name' => 'Imagen 3 (Kualitas Gambar Sinematik Tertinggi)'],
                ['id' => 'gemini-2.0-flash', 'name' => 'Gemini 2.0 Flash Multimodal'],
                ['id' => 'gemini-omni-1.1-flash', 'name' => 'Gemini Omni Flash Multimodal'],
            ],
            'tts' => [
                ['id' => 'gemini-3.8-flash-tts', 'name' => 'Gemini 3.8 Flash TTS (Rekomendasi Resmi Google AI Studio - Audio WAV/RIFF)'],
                ['id' => 'gemini-2.0-flash', 'name' => 'Gemini 2.0 Native Audio Modality'],
                ['id' => 'google-tts-indonesia', 'name' => 'Google Speech Standard ID (Cadangan)'],
            ],
            'video' => [
                ['id' => 'gemini-omni-1.1-flash', 'name' => 'Gemini Omni Flash (gemini-omni-1.1-flash - Rekomendasi Utama Video Google)'],
                ['id' => 'veo-3.1', 'name' => 'Google Veo 3.1 (Generasi Video Sinematik)'],
                ['id' => 'veo-2.0-generate-001', 'name' => 'Veo 2.0 (Model Video Google)'],
                ['id' => 'gemini-2.0-flash-exp', 'name' => 'Gemini 2.0 Experimental Video'],
            ],
            'voices' => [
                ['id' => 'Pari', 'name' => 'Pari - Suara Wanita Lembut & Naratif'],
                ['id' => 'Kore', 'name' => 'Kore - Suara Pria Hangat & Bijak'],
                ['id' => 'Zephyr', 'name' => 'Zephyr - Suara Wanita Dinamis'],
                ['id' => 'Fenrir', 'name' => 'Fenrir - Suara Pria Berwibawa'],
            ]
        ];

        return Inertia::render('Admin/GeminiSettings', [
            'settings' => $settings,
            'modelOptions' => $modelOptions,
        ]);
    }

    public function saveApiKey(Request $request, GeminiService $gemini): RedirectResponse
    {
        $validated = $request->validate([
            'api_key' => ['required', 'string', 'min:20', 'max:150'],
        ], [
            'api_key.required' => 'API Key wajib diisi.',
            'api_key.min' => 'Panjang API Key Google Gemini tidak valid.',
        ]);

        $rawKey = trim($validated['api_key']);

        // Encrypt and store in database
        AppSetting::set('gemini_api_key', $rawKey, true, 'gemini');

        // Immediately test key validity
        $test = $gemini->testAuthentication($rawKey);

        AppSetting::set('gemini_last_tested_at', now()->toIso8601String(), false, 'gemini');
        AppSetting::set('gemini_last_test_result', json_encode($test), false, 'gemini');

        if ($test['success']) {
            return back()->with('success', 'API Key Gemini berhasil disimpan dan terverifikasi aktif.');
        }

        return back()->with('error', 'API Key disimpan, tetapi uji koneksi gagal: ' . ($test['message'] ?? 'Periksa key Anda.'));
    }

    public function removeApiKey(): RedirectResponse
    {
        AppSetting::set('gemini_api_key', null, false, 'gemini');
        AppSetting::set('gemini_last_test_result', null, false, 'gemini');

        return back()->with('success', 'Koneksi Google Gemini API telah diputus.');
    }

    public function updateModels(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'text_model' => ['required', 'string'],
            'image_model' => ['required', 'string'],
            'tts_model' => ['required', 'string'],
            'video_model' => ['required', 'string'],
            'voice_name' => ['required', 'string'],
            'max_video_per_user' => ['required', 'integer', 'min:1', 'max:500'],
            'daily_render_limit' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        AppSetting::set('gemini_text_model', $validated['text_model'], false, 'gemini');
        AppSetting::set('gemini_image_model', $validated['image_model'], false, 'gemini');
        AppSetting::set('gemini_tts_model', $validated['tts_model'], false, 'gemini');
        AppSetting::set('gemini_video_model', $validated['video_model'], false, 'gemini');
        AppSetting::set('gemini_voice_name', $validated['voice_name'], false, 'gemini');
        AppSetting::set('app_max_video_per_user', (string) $validated['max_video_per_user'], false, 'limits');
        AppSetting::set('app_daily_render_limit', (string) $validated['daily_render_limit'], false, 'limits');

        return back()->with('success', 'Pengaturan model dan limitasi berhasil diperbarui.');
    }

    public function testConnection(GeminiService $gemini): JsonResponse
    {
        $result = $gemini->testAuthentication();
        AppSetting::set('gemini_last_tested_at', now()->toIso8601String(), false, 'gemini');
        AppSetting::set('gemini_last_test_result', json_encode($result), false, 'gemini');

        return response()->json($result);
    }

    public function testText(GeminiService $gemini): JsonResponse
    {
        $result = $gemini->testTextGeneration();
        return response()->json($result);
    }

    public function testAudio(GeminiService $gemini): JsonResponse
    {
        $result = $gemini->testAudioGeneration();
        return response()->json($result);
    }

    public function testImage(GeminiService $gemini): JsonResponse
    {
        $result = $gemini->testImageGeneration();
        return response()->json($result);
    }

    public function testVideo(GeminiService $gemini): JsonResponse
    {
        $result = $gemini->testVideoGeneration();
        return response()->json($result);
    }
}
