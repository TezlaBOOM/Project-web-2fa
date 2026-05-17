<?php

namespace Tests\Feature\Frontend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserActivity;

class LoginBlockedAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'blocked@example.com',
            'password' => bcrypt('password123'),
            'is_active' => false
        ]);

        $response = $this->post(route('login.post'), [
            'email' => 'blocked@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email' => 'Twoje konto zostało zdezaktywowane. Skontaktuj się z administratorem.']);
        $this->assertGuest();
    }

    public function test_inactive_user_generates_login_failed_log()
    {
        $user = User::factory()->create([
            'email' => 'blocked2@example.com',
            'password' => bcrypt('password123'),
            'is_active' => false
        ]);

        $this->post(route('login.post'), [
            'email' => 'blocked2@example.com',
            'password' => 'password123',
        ]);

        $this->assertDatabaseHas('user_activities', [
            'action' => 'login_failed',
            'description' => "Próba logowania na zablokowane konto: {$user->email}"
        ]);
    }
}
