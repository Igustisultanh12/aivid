<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Topic;
use App\Models\Source;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get('/admin/settings/gemini');
        $response->assertStatus(403);

        $response = $this->actingAs($user)->get('/admin/topics');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_gemini_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get('/admin/settings/gemini');
        $response->assertStatus(200);
    }

    public function test_admin_can_store_encrypted_gemini_api_key(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models*' => \Illuminate\Support\Facades\Http::response([
                'models' => [
                    ['name' => 'models/gemini-2.5-flash'],
                    ['name' => 'models/imagen-3.0-generate-002'],
                ],
            ], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/settings/gemini/key', [
            'api_key' => 'AIzaSyA_TEST_SECURE_GEMINI_KEY_XYZ987654321',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify key is encrypted in database
        $setting = AppSetting::where('key', 'gemini_api_key')->first();
        $this->assertNotNull($setting);
        $this->assertNotEquals('AIzaSyA_TEST_SECURE_GEMINI_KEY_XYZ987654321', $setting->value);
        $this->assertEquals('AIzaSyA_TEST_SECURE_GEMINI_KEY_XYZ987654321', Crypt::decryptString($setting->value));

        // Verify masked key format
        $masked = AppSetting::getMasked('gemini_api_key');
        $this->assertStringStartsWith('AIzaSy', $masked);
        $this->assertStringEndsWith('4321', $masked);
        $this->assertStringContainsString('***', $masked);
    }

    public function test_admin_can_manage_topics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/topics', [
            'title' => 'Filosofi Tri Hita Karana',
            'description' => 'Konsep tiga penyebab keharmonisan hidup: Parahyangan, Pawongan, Palemahan.',
            'category' => 'Filosofi Kehidupan',
            'cultural_context' => 'Tradisi Hindu Bali',
            'is_popular' => true,
            'icon_name' => 'Sparkles',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('topics', [
            'title' => 'Filosofi Tri Hita Karana',
            'slug' => 'filosofi-tri-hita-karana',
        ]);
    }

    public function test_admin_can_manage_sources(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/admin/sources', [
            'title' => 'Bhagavad Gita',
            'author_or_institution' => 'Maharsi Vyasa',
            'source_type' => 'Kitab Suci / Smrti',
            'reference_section' => 'Bab II Sloka 47',
            'tradition_context' => 'Tradisi Veda Sanatana Dharma',
            'region' => 'Nusantara & Global',
            'verification_status' => 'verified',
            'notes' => 'Rujukan sah tentang Karma Yoga tanpa keterikatan pada hasil buah perbuatan.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sources', [
            'title' => 'Bhagavad Gita',
            'verification_status' => 'verified',
        ]);
    }

    public function test_admin_can_update_user_quota_and_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user', 'quota_total' => 5]);

        $response = $this->actingAs($admin)->put("/admin/users/{$user->id}/quota", [
            'quota_total' => 20,
            'role' => 'user',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'quota_total' => 20,
        ]);
    }
}
