<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = 'https://generativelanguage.googleapis.com/v1beta';

    protected function setUp(): void
    {
        parent::setUp();
        AppSetting::set('gemini_api_key', 'test-key', true, 'gemini');
        Storage::fake('public');
    }

    private function textInteraction(string $text): array
    {
        return [
            'id' => 'int_1',
            'status' => 'completed',
            'steps' => [
                ['type' => 'thought', 'content' => [['type' => 'text', 'text' => 'thinking...']]],
                ['type' => 'model_output', 'content' => [['type' => 'text', 'text' => $text]]],
            ],
            'usage' => ['total_input_tokens' => 12, 'total_output_tokens' => 7],
        ];
    }

    public function test_legacy_model_ids_resolve_to_current_models(): void
    {
        $this->assertSame('gemini-3.8-flash', GeminiService::resolveModel('text', 'gemini-3.0-flash'));
        $this->assertSame('gemini-3.8-flash', GeminiService::resolveModel('text', 'models/gemini-3.0-flash'));
        $this->assertSame('gemini-nano-banana-2.1', GeminiService::resolveModel('image', 'imagen-3.0-generate-002'));
        $this->assertSame('gemini-omni-1.1-flash', GeminiService::resolveModel('video', 'veo-2.0-generate-001'));
        $this->assertSame('gemini-3.8-flash', GeminiService::resolveModel('text', null));
        $this->assertSame('gemini-3.8-flash', GeminiService::resolveModel('text', 'gemini-3.8'));
    }

    public function test_text_uses_gemini_38_flash_via_interactions_without_sampling_params(): void
    {
        AppSetting::set('gemini_text_model', 'gemini-3.0-flash', false, 'gemini');

        Http::fake([
            self::BASE . '/interactions' => Http::response($this->textInteraction('Ketulusan adalah doa terindah.')),
        ]);

        $result = app(GeminiService::class)->testTextGeneration();

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('gemini-3.8-flash', $result['model']);
        $this->assertSame('Ketulusan adalah doa terindah.', $result['output']);

        Http::assertSent(function (Request $request) {
            $body = $request->data();
            return $request->url() === self::BASE . '/interactions'
                && $request->hasHeader('x-goog-api-key', 'test-key')
                && $body['model'] === 'gemini-3.8-flash'
                && $body['generation_config']['thinking_level'] === 'low'
                && !isset($body['generation_config']['temperature'])
                && !isset($body['temperature']);
        });
    }

    public function test_text_falls_back_to_next_model_when_model_not_found(): void
    {
        Http::fake(function (Request $request) {
            $model = $request->data()['model'] ?? null;
            $notFound = Http::response(['error' => ['code' => 404, 'message' => 'models/gemini-3.8-flash is not found', 'status' => 'NOT_FOUND']], 404);

            if (str_ends_with($request->url(), '/interactions')) {
                return $model === 'gemini-3.6-flash'
                    ? Http::response($this->textInteraction('Jawaban cadangan.'))
                    : $notFound;
            }

            return $notFound; // generateContent juga 404
        });

        $result = app(GeminiService::class)->testTextGeneration();

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('gemini-3.6-flash', $result['model']);
    }

    public function test_real_errors_are_reported_without_trying_fallback_models(): void
    {
        Http::fake([
            '*' => Http::response(['error' => ['code' => 400, 'message' => 'API key not valid.', 'status' => 'INVALID_ARGUMENT']], 400),
        ]);

        $result = app(GeminiService::class)->testTextGeneration();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('API key not valid', $result['message']);
        Http::assertSentCount(2); // interactions + generateContent untuk gemini-3.8-flash saja
    }

    public function test_image_uses_nano_banana_instead_of_imagen(): void
    {
        AppSetting::set('gemini_image_model', 'imagen-3.0-generate-002', false, 'gemini');

        Http::fake([
            self::BASE . '/interactions' => Http::response([
                'status' => 'completed',
                'steps' => [[
                    'type' => 'model_output',
                    'content' => [['type' => 'image', 'mime_type' => 'image/jpeg', 'data' => base64_encode('fake-jpeg')]],
                ]],
            ]),
        ]);

        $result = app(GeminiService::class)->testImageGeneration();

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('gemini-nano-banana-2.1', $result['model']);

        Http::assertSent(fn (Request $r) => ($r->data()['model'] ?? null) === 'gemini-nano-banana-2.1'
            && $r->data()['response_format']['type'] === 'image');
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'imagen') || str_contains($r->url(), ':predict'));
    }

    public function test_video_check_uses_current_video_model_instead_of_veo_2(): void
    {
        AppSetting::set('gemini_video_model', 'veo-2.0-generate-001', false, 'gemini');

        Http::fake([
            self::BASE . '/models/gemini-omni-1.1-flash' => Http::response([
                'name' => 'models/gemini-omni-1.1-flash',
                'supportedGenerationMethods' => ['createInteraction'],
            ]),
        ]);

        $result = app(GeminiService::class)->testVideoGeneration();

        $this->assertTrue($result['success'], $result['message']);
        $this->assertSame('gemini-omni-1.1-flash', $result['model']);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'veo-2.0'));
    }

    public function test_script_generation_requests_json_schema_and_parses_output(): void
    {
        $script = [
            'title' => 'Makna Canang Sari',
            'hook_text' => 'Pernahkah kamu bertanya?',
            'reflection_text' => 'Tulus itu indah.',
            'summary_text' => 'Ringkasan.',
            'scenes' => array_map(fn ($i) => [
                'scene_order' => $i,
                'duration' => 6,
                'narration_text' => "Narasi {$i}",
                'onscreen_text' => "Teks {$i}",
                'visual_prompt' => 'Balinese offering',
                'camera_motion' => 'zoom_in',
                'transition_type' => 'fade',
                'source_citation' => 'Lontar',
            ], range(1, 5)),
        ];

        Http::fake([
            self::BASE . '/interactions' => Http::response($this->textInteraction(json_encode($script))),
        ]);

        $result = app(GeminiService::class)->generateScript('Canang Sari');

        $this->assertSame('gemini-3.8-flash', $result['model'] ?? null);
        $this->assertSame('Makna Canang Sari', $result['data']['title']);
        $this->assertEqualsWithDelta(30.0, array_sum(array_column($result['data']['scenes'], 'duration')), 0.01);

        Http::assertSent(fn (Request $r) => ($r->data()['response_format']['mime_type'] ?? null) === 'application/json'
            && ($r->data()['response_format']['schema']['type'] ?? null) === 'object');
    }
}
