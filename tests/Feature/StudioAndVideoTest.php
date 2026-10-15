<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoQuiz;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class StudioAndVideoTest extends TestCase
{
    public function test_authenticated_user_can_access_studio(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'quota_total' => 5, 'quota_used' => 0]);

        $response = $this->actingAs($user)->get('/studio');
        $response->assertStatus(200);
    }

    public function test_user_can_start_video_generation(): void
    {
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'title' => 'Makna Canang Sari',
                                        'hook' => 'Pernahkah kamu merenungkan makna persembahan harian?',
                                        'scenes' => [
                                            [
                                                'scene_order' => 1,
                                                'duration_seconds' => 3.0,
                                                'narration_text' => 'Setiap helai janur pada Canang Sari menyimpan doa tulus.',
                                                'onscreen_text' => 'Canang Sari: Persembahan Tulus',
                                                'visual_prompt' => 'Canang sari di atas meja sesaji Bali',
                                                'camera_motion' => 'zoom_in'
                                            ]
                                        ],
                                        'reflection' => 'Ketulusan adalah wujud persembahan tertinggi.',
                                        'quiz' => [
                                            'question' => 'Apa makna utama Canang Sari?',
                                            'options' => ['Rasa syukur tulus', 'Hiasan', 'Kemewahan', 'Kewajiban semata'],
                                            'correct_index' => 0,
                                            'explanation' => 'Canang sari melambangkan keikhlasan dan rasa syukur.'
                                        ]
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $user = User::factory()->create(['email_verified_at' => now(), 'quota_total' => 5, 'quota_used' => 0]);
        $topic = Topic::create([
            'title' => 'Makna Canang Sari',
            'slug' => 'makna-canang-sari',
            'description' => 'Simbol rasa syukur dan pengorbanan suci tulus ikhlas dalam kehidupan sehari-hari.',
            'category' => 'Upacara & Tradisi',
            'cultural_context' => 'Tradisi Hindu Bali',
            'is_popular' => true,
            'icon_name' => 'Sparkles',
        ]);

        $response = $this->actingAs($user)->post('/studio/generate', [
            'topic_id' => $topic->id,
            'target_audience' => 'Anak Muda',
            'tone' => 'Inspiratif & Hangat',
            'production_mode' => 'illustration_motion',
            'visual_style' => 'modern_editorial',
            'aspect_ratio' => '9:16',
            'voice_id' => 'Pari',
            'has_subtitles' => true,
            'review_script_first' => true,
        ]);

        $response->assertRedirect();
        
        $video = Video::where('user_id', $user->id)->first();
        $this->assertNotNull($video);
        $this->assertEquals(30, (int)$video->duration_seconds);
        $this->assertEquals('9:16', $video->aspect_ratio);
        $this->assertEquals('illustration_motion', $video->production_mode);
    }

    public function test_user_cannot_generate_when_quota_exhausted(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'quota_total' => 5, 'quota_used' => 5]);
        $topic = Topic::create([
            'title' => 'Filosofi Galungan',
            'slug' => 'filosofi-galungan',
            'description' => 'Kemenangan Dharma melawan Adharma.',
            'category' => 'Hari Suci',
            'cultural_context' => 'Tradisi Hindu Bali',
            'is_popular' => true,
            'icon_name' => 'Sparkles',
        ]);

        $response = $this->actingAs($user)->post('/studio/generate', [
            'topic_id' => $topic->id,
            'target_audience' => 'Anak Muda',
            'tone' => 'Inspiratif & Hangat',
            'production_mode' => 'illustration_motion',
            'visual_style' => 'modern_editorial',
            'aspect_ratio' => '9:16',
            'voice_id' => 'Pari',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_user_can_view_video_details_and_quizzes(): void
    {
        $user = User::factory()->create();
        $video = Video::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Makna Mendalam Canang Sari',
            'description' => 'Eksplorasi filosofis persembahan harian umat Hindu Bali dalam 30 detik.',
            'aspect_ratio' => '9:16',
            'duration_seconds' => 30,
            'status' => 'completed',
            'is_public' => true,
        ]);

        $quiz = VideoQuiz::create([
            'video_id' => $video->id,
            'question' => 'Apakah makna utama dari Canang Sari?',
            'options' => ['Rasa syukur dan ketulusan', 'Hiasan semata', 'Kekayaan materi', 'Tradisi tanpa makna'],
            'correct_option_index' => 0,
            'explanation' => 'Canang sari melambangkan persembahan tulus ikhlas berlandaskan rasa syukur kehadapan Ida Sang Hyang Widhi Wasa.',
        ]);

        $response = $this->actingAs($user)->get("/videos/{$video->id}");
        $response->assertStatus(200);

        // Test answering quiz
        $quizResponse = $this->actingAs($user)->postJson("/videos/{$video->id}/quiz/submit", [
            'answers' => [
                $quiz->id => 0,
            ],
        ]);

        $quizResponse->assertJson([
            'score' => 1,
            'total' => 1,
            'percentage' => 100,
        ]);
    }
}
