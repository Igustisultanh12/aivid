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
            'text_model' => GeminiService::resolveModel('text', AppSetting::get('gemini_text_model')),
            'image_model' => GeminiService::resolveModel('image', AppSetting::get('gemini_image_model')),
            'tts_model' => GeminiService::resolveModel('tts', AppSetting::get('gemini_tts_model')),
            'video_model' => GeminiService::resolveModel('video', AppSetting::get('gemini_video_model')),
            'voice_name' => $gemini->voice(),
            'max_video_per_user' => (int) AppSetting::get('app_max_video_per_user', 10),
            'daily_render_limit' => (int) AppSetting::get('app_daily_render_limit', 50),
            'last_tested_at' => AppSetting::get('gemini_last_tested_at'),
            'last_test_result' => json_decode(AppSetting::get('gemini_last_test_result', '{}'), true),
        ];

        // Daftar model sesuai https://ai.google.dev/gemini-api/docs/models
        $modelOptions = [
            'text' => [
                ['id' => 'gemini-3.8-flash', 'name' => 'Gemini 3.8 Flash (Rekomendasi Google - Naskah & Kuis)'],
                ['id' => 'gemini-4-argon', 'name' => 'Gemini 4 Argon (Penalaran Mendalam, lebih mahal)'],
                ['id' => 'gemini-3.6-flash', 'name' => 'Gemini 3.6 Flash (Generasi Sebelumnya)'],
                ['id' => 'gemini-3.5-flash-lite', 'name' => 'Gemini 3.5 Flash-Lite (Hemat Biaya)'],
            ],
            'image' => [
                ['id' => 'gemini-nano-banana-2.1', 'name' => 'Nano Banana 2.1 (Rekomendasi Google - Gambar Terbaru)'],
                ['id' => 'gemini-3.1-flash-image', 'name' => 'Nano Banana 2 (gemini-3.1-flash-image)'],
                ['id' => 'gemini-3.1-flash-lite-image', 'name' => 'Nano Banana 2 Lite (Cepat & Hemat)'],
                ['id' => 'gemini-3-pro-image', 'name' => 'Nano Banana Pro (Kualitas Tertinggi)'],
            ],
            'tts' => [
                ['id' => 'gemini-3.8-flash-tts', 'name' => 'Gemini 3.8 Flash TTS (Rekomendasi Google)'],
                ['id' => 'gemini-3.8-flash-lite-tts', 'name' => 'Gemini 3.8 Flash-Lite TTS (Hemat Biaya)'],
                ['id' => 'google-tts-indonesia', 'name' => 'Google Speech Standard ID (Cadangan tanpa Gemini)'],
            ],
            'video' => [
                ['id' => 'gemini-omni-1.1-flash', 'name' => 'Gemini Omni Flash (Rekomendasi Google - Video)'],
                ['id' => 'veo-3.1-generate-preview', 'name' => 'Veo 3.1 (Video Sinematik + Audio)'],
                ['id' => 'veo-3.1-lite-generate-preview', 'name' => 'Veo 3.1 Lite (Hemat Biaya)'],
            ],
            'voices' => [
                ['id' => 'Sulafat', 'name' => 'Sulafat - Suara Wanita Hangat & Naratif'],
                ['id' => 'Charon', 'name' => 'Charon - Suara Pria Tenang & Informatif'],
                ['id' => 'Aoede', 'name' => 'Aoede - Suara Wanita Ringan & Santai'],
                ['id' => 'Orus', 'name' => 'Orus - Suara Pria Tegas & Berwibawa'],
                ['id' => 'Kore', 'name' => 'Kore - Suara Wanita Mantap & Jelas'],
                ['id' => 'Fenrir', 'name' => 'Fenrir - Suara Pria Bersemangat'],
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

        AppSetting::set('gemini_text_model', GeminiService::resolveModel('text', $validated['text_model']), false, 'gemini');
        AppSetting::set('gemini_image_model', GeminiService::resolveModel('image', $validated['image_model']), false, 'gemini');
        AppSetting::set('gemini_tts_model', GeminiService::resolveModel('tts', $validated['tts_model']), false, 'gemini');
        AppSetting::set('gemini_video_model', GeminiService::resolveModel('video', $validated['video_model']), false, 'gemini');
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
