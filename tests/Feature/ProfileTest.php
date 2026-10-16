<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page
            ->component('Profile/Edit')
            ->has('user')
        );
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@example.com',
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Nama Baru Lengkap',
            'email' => 'baru@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $user->refresh();
        $this->assertSame('Nama Baru Lengkap', $user->name);
        $this->assertSame('baru@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_email_is_not_changed(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch('/profile', [
            'name' => 'Nama Baru Saja',
            'email' => $user->email,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('Nama Baru Saja', $user->name);
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password-lama-123'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password-lama-123',
            'password' => 'password-baru-456!',
            'password_confirmation' => 'password-baru-456!',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertTrue(Hash::check('password-baru-456!', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password-lama-123'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password-salah',
            'password' => 'password-baru-456!',
            'password_confirmation' => 'password-baru-456!',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertFalse(Hash::check('password-baru-456!', $user->refresh()->password));
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password-valid-123'),
        ]);

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'password-valid-123',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull(User::find($user->id));
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password-valid-123'),
        ]);

        $response = $this->actingAs($user)->delete('/profile', [
            'password' => 'password-salah',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertNotNull(User::find($user->id));
    }
}
