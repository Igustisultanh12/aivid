<?php

namespace Tests\Feature;

use App\Models\StoryboardScene;
use App\Models\User;
use App\Models\Video;
use App\Services\QuotaService;
use Tests\TestCase;

class VideoRenderPipelineTest extends TestCase
{
    public function test_storyboard_scenes_total_strictly_30_seconds(): void
    {
        $user = User::factory()->create();
        $video = Video::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Filosofi Tri Hita Karana',
            'aspect_ratio' => '9:16',
            'duration_seconds' => 30,
            'status' => 'pending',
        ]);

        $scenes = [
            [
                'scene_order' => 1,
                'duration' => 3.0,
                'narration_text' => 'Pernahkah kamu bertanya apa rahasia kedamaian sejati?',
                'onscreen_text' => 'Harmoni Tiga Hubungan Suci',
                'visual_prompt' => 'Pura Besakih megah dengan latar Gunung Agung di pagi hari',
                'camera_motion' => 'zoom_in',
            ],
            [
                'scene_order' => 2,
                'duration' => 8.0,
                'narration_text' => 'Tri Hita Karana mengajarkan tiga pilar keharmonisan hidup.',
                'onscreen_text' => 'Parahyangan: Hubungan dengan Tuhan',
                'visual_prompt' => 'Umat Hindu Bali bersembahyang dengan canang sari penuh khidmat',
                'camera_motion' => 'pan_left',
            ],
            [
                'scene_order' => 3,
                'duration' => 6.0,
                'narration_text' => 'Pawongan adalah menjaga kerukunan dan welas asih sesama manusia.',
                'onscreen_text' => 'Pawongan: Kasih Sesama Manusia',
                'visual_prompt' => 'Masyarakat desa adat Bali bergotong royong di banjar',
                'camera_motion' => 'pan_right',
            ],
            [
                'scene_order' => 4,
                'duration' => 6.0,
                'narration_text' => 'Dan Palemahan merawat alam semesta beserta seluruh isinya.',
                'onscreen_text' => 'Palemahan: Kelestarian Lingkungan',
                'visual_prompt' => 'Sawah terasering Jatiluwih yang hijau asri dengan sistem subak',
                'camera_motion' => 'tilt_up',
            ],
            [
                'scene_order' => 5,
                'duration' => 7.0,
                'narration_text' => 'Saat ketiganya selaras, kedamaian lahir dari dalam diri kita.',
                'onscreen_text' => 'Selaraskan Hidupmu Hari Ini',
                'visual_prompt' => 'Pemuda Bali tersenyum damai menghirup udara pegunungan segar',
                'camera_motion' => 'zoom_out',
            ],
        ];

        foreach ($scenes as $sceneData) {
            $video->scenes()->create($sceneData);
        }

        $totalDuration = $video->scenes()->sum('duration');
        $this->assertEquals(30.0, (float) $totalDuration);
    }

    public function test_quota_service_checks_and_reserves_properly(): void
    {
        $quotaService = new QuotaService();
        $user = User::factory()->create(['quota_total' => 2, 'quota_used' => 0]);

        $check1 = $quotaService->canCreateVideo($user);
        $this->assertTrue($check1['allowed']);

        // Use quota
        $user->increment('quota_used');
        $check2 = $quotaService->canCreateVideo($user);
        $this->assertTrue($check2['allowed']);

        // Exhaust quota
        $user->increment('quota_used');
        $check3 = $quotaService->canCreateVideo($user);
        $this->assertFalse($check3['allowed']);
        $this->assertStringContainsString('habis', $check3['message']);
    }
}
