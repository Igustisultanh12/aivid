<?php

namespace Tests\Feature;

use App\Models\CartoonPromptProject;
use App\Models\CartoonPromptScene;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartoonPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_cartoon_prompts_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/cartoon-prompts');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('CartoonPrompt/Index')
            ->has('themes')
            ->has('visualStyles')
            ->has('targetEngines')
            ->has('savedProjects')
        );
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/cartoon-prompts');

        $response->assertRedirect('/login');
    }

    public function test_user_can_generate_30_second_cartoon_prompts(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cartoon-prompts/generate', [
            'title' => 'Kisah Cerdik Kancil & Apel Emas',
            'theme' => 'fable',
            'target_duration' => 30,
            'visual_style' => 'pixar_3d',
            'target_engine' => 'kling_ai',
            'aspect_ratio' => '9:16',
            'character_description' => 'Kancil kecil cerdik bersyal hijau',
            'story_premise' => 'Kancil mencari buah apel emas di atas pohon tinggi',
        ]);

        $project = CartoonPromptProject::where('user_id', $user->id)->first();

        $this->assertNotNull($project);
        $this->assertEquals('Kisah Cerdik Kancil & Apel Emas', $project->title);
        $this->assertEquals(30, $project->target_duration);
        $this->assertEquals(6, $project->scene_count);
        $this->assertEquals(30.0, (float) $project->total_duration);
        $this->assertCount(6, $project->scenes);

        // Check first scene
        $firstScene = $project->scenes->first();
        $this->assertEquals(1, $firstScene->scene_number);
        $this->assertEquals(0.0, (float) $firstScene->start_second);
        $this->assertEquals(5.0, (float) $firstScene->end_second);
        $this->assertNotEmpty($firstScene->visual_prompt);
        $this->assertStringContainsString('3D Disney Pixar animation style', $firstScene->visual_prompt);

        // Check last scene reaches 30s
        $lastScene = $project->scenes->last();
        $this->assertEquals(6, $lastScene->scene_number);
        $this->assertEquals(30.0, (float) $lastScene->end_second);

        $response->assertRedirect(route('cartoon-prompts.index', ['project_id' => $project->id]));
    }

    public function test_user_can_generate_60_second_cartoon_prompts(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/cartoon-prompts/generate', [
            'title' => 'Petualangan 1 Menit Bima Cilik',
            'theme' => 'dharma_kids',
            'target_duration' => 60,
            'visual_style' => 'ghibli_anime',
            'target_engine' => 'runway_gen3',
            'aspect_ratio' => '16:9',
            'character_description' => 'Bima cilik berambut ikal dengan gada emas mini',
            'story_premise' => 'Bima menjaga kelestarian mata air desa dari kekeringan',
        ]);

        $project = CartoonPromptProject::where('user_id', $user->id)->first();

        $this->assertNotNull($project);
        $this->assertEquals(60, $project->target_duration);
        $this->assertEquals(12, $project->scene_count);
        $this->assertEquals(60.0, (float) $project->total_duration);
        $this->assertCount(12, $project->scenes);

        // Check last scene reaches 60s
        $lastScene = $project->scenes->last();
        $this->assertEquals(12, $lastScene->scene_number);
        $this->assertEquals(60.0, (float) $lastScene->end_second);
        $this->assertStringContainsString('Studio Ghibli anime style', $lastScene->visual_prompt);

        $response->assertRedirect(route('cartoon-prompts.index', ['project_id' => $project->id]));
    }

    public function test_user_can_update_project_metadata(): void
    {
        $user = User::factory()->create();
        $project = CartoonPromptProject::create([
            'user_id' => $user->id,
            'title' => 'Judul Lama',
            'theme' => 'comedy',
            'target_duration' => 30,
            'visual_style' => 'pixar_3d',
            'target_engine' => 'universal',
            'aspect_ratio' => '9:16',
            'scene_count' => 1,
            'total_duration' => 5.0,
        ]);

        $response = $this->actingAs($user)->put("/cartoon-prompts/{$project->id}", [
            'title' => 'Judul Baru yang Diedit',
            'story_premise' => 'Sinopsis baru yang diperbarui',
            'character_description' => 'Karakter baru',
            'visual_style' => 'chibi_3d',
            'target_engine' => 'kling_ai',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cartoon_prompt_projects', [
            'id' => $project->id,
            'title' => 'Judul Baru yang Diedit',
            'visual_style' => 'chibi_3d',
        ]);
    }

    public function test_user_can_update_single_scene(): void
    {
        $user = User::factory()->create();
        $project = CartoonPromptProject::create([
            'user_id' => $user->id,
            'title' => 'Proyek Kartun',
            'theme' => 'fable',
            'target_duration' => 30,
            'visual_style' => 'pixar_3d',
            'target_engine' => 'universal',
            'aspect_ratio' => '9:16',
            'scene_count' => 1,
            'total_duration' => 5.0,
        ]);

        $scene = CartoonPromptScene::create([
            'cartoon_prompt_project_id' => $project->id,
            'scene_number' => 1,
            'start_second' => 0.0,
            'end_second' => 5.0,
            'duration' => 5.0,
            'title' => 'Frame Lama',
            'action_description' => 'Aksi lama',
            'visual_prompt' => 'Old prompt',
        ]);

        $response = $this->actingAs($user)->put("/cartoon-prompts/{$project->id}/scenes/{$scene->id}", [
            'title' => 'Frame Baru yang Direvisi',
            'action_description' => 'Aksi karakter baru melompat tinggi',
            'visual_prompt' => 'New refined visual prompt in 3D Pixar style',
            'camera_movement' => 'Smooth crane shot',
            'dialogue_or_narration' => 'Hore!',
            'sound_effects' => '*Boing!*',
            'music_mood' => 'Cheerful',
            'duration' => 6.0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cartoon_prompt_scenes', [
            'id' => $scene->id,
            'title' => 'Frame Baru yang Direvisi',
            'visual_prompt' => 'New refined visual prompt in 3D Pixar style',
        ]);
    }

    public function test_user_can_regenerate_single_scene(): void
    {
        $user = User::factory()->create();
        $project = CartoonPromptProject::create([
            'user_id' => $user->id,
            'title' => 'Proyek Kartun',
            'theme' => 'fable',
            'target_duration' => 30,
            'visual_style' => 'pixar_3d',
            'target_engine' => 'universal',
            'aspect_ratio' => '9:16',
            'character_description' => 'Kancil lucu bersyal merah',
            'scene_count' => 1,
            'total_duration' => 5.0,
        ]);

        $scene = CartoonPromptScene::create([
            'cartoon_prompt_project_id' => $project->id,
            'scene_number' => 1,
            'start_second' => 0.0,
            'end_second' => 5.0,
            'duration' => 5.0,
            'title' => 'Frame 1',
            'action_description' => 'Aksi awal',
            'visual_prompt' => 'Initial prompt',
        ]);

        $response = $this->actingAs($user)->post("/cartoon-prompts/{$project->id}/scenes/{$scene->id}/regenerate");

        $response->assertRedirect();
        $scene->refresh();
        $this->assertNotEquals('Initial prompt', $scene->visual_prompt);
        $this->assertStringContainsString('Kancil lucu bersyal merah', $scene->visual_prompt);
    }

    public function test_user_can_add_and_delete_scene(): void
    {
        $user = User::factory()->create();
        $project = CartoonPromptProject::create([
            'user_id' => $user->id,
            'title' => 'Proyek Kartun',
            'theme' => 'fable',
            'target_duration' => 30,
            'visual_style' => 'pixar_3d',
            'target_engine' => 'universal',
            'aspect_ratio' => '9:16',
            'scene_count' => 1,
            'total_duration' => 5.0,
        ]);

        $scene1 = CartoonPromptScene::create([
            'cartoon_prompt_project_id' => $project->id,
            'scene_number' => 1,
            'start_second' => 0.0,
            'end_second' => 5.0,
            'duration' => 5.0,
            'title' => 'Frame 1',
            'action_description' => 'Aksi awal',
            'visual_prompt' => 'Initial prompt',
        ]);

        // Add scene
        $this->actingAs($user)->post("/cartoon-prompts/{$project->id}/scenes");

        $project->refresh();
        $this->assertEquals(2, $project->scene_count);
        $this->assertEquals(10.0, (float) $project->total_duration);

        $newScene = $project->scenes()->where('scene_number', 2)->first();
        $this->assertNotNull($newScene);
        $this->assertEquals(5.0, (float) $newScene->start_second);
        $this->assertEquals(10.0, (float) $newScene->end_second);

        // Delete second scene
        $this->actingAs($user)->delete("/cartoon-prompts/{$project->id}/scenes/{$newScene->id}");

        $project->refresh();
        $this->assertEquals(1, $project->scene_count);
        $this->assertEquals(5.0, (float) $project->total_duration);
    }

    public function test_user_can_export_prompts_as_txt_and_json(): void
    {
        $user = User::factory()->create();
        $project = CartoonPromptProject::create([
            'user_id' => $user->id,
            'title' => 'Petualangan Kartun Ekspor',
            'theme' => 'fable',
            'target_duration' => 30,
            'visual_style' => 'pixar_3d',
            'target_engine' => 'universal',
            'aspect_ratio' => '9:16',
            'scene_count' => 1,
            'total_duration' => 5.0,
        ]);

        CartoonPromptScene::create([
            'cartoon_prompt_project_id' => $project->id,
            'scene_number' => 1,
            'start_second' => 0.0,
            'end_second' => 5.0,
            'duration' => 5.0,
            'title' => 'Frame 1',
            'action_description' => 'Aksi awal kancil melompat',
            'visual_prompt' => '3D Disney Pixar cute mouse deer leaping in sunlit tropical glade',
        ]);

        // Test TXT export
        $txtResponse = $this->actingAs($user)->get("/cartoon-prompts/{$project->id}/export/txt");
        $txtResponse->assertStatus(200);
        $this->assertStringContainsString('PROMPT STORYBOARD VIDEO KARTUN', $txtResponse->getContent());
        $this->assertStringContainsString('3D Disney Pixar cute mouse deer leaping', $txtResponse->getContent());

        // Test JSON export
        $jsonResponse = $this->actingAs($user)->get("/cartoon-prompts/{$project->id}/export/json");
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonStructure([
            'project_id',
            'title',
            'target_duration_seconds',
            'scene_count',
            'scenes' => [
                '*' => ['frame', 'timestamp', 'visual_prompt', 'action_description']
            ]
        ]);
    }

    public function test_user_cannot_access_other_users_project(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $project = CartoonPromptProject::create([
            'user_id' => $user1->id,
            'title' => 'Proyek Privat User 1',
            'theme' => 'fable',
            'target_duration' => 30,
            'visual_style' => 'pixar_3d',
            'target_engine' => 'universal',
            'aspect_ratio' => '9:16',
            'scene_count' => 1,
            'total_duration' => 5.0,
        ]);

        $response = $this->actingAs($user2)->delete("/cartoon-prompts/{$project->id}");
        $response->assertStatus(403);
    }
}
