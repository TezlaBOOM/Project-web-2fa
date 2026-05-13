<?php

namespace Tests\Feature\Frontend;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use App\Mail\TwoFactorCodeMail;
use Tests\TestCase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_without_2fa()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'two_factor_enabled' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_redirected_to_2fa_when_enabled()
    {
        Mail::fake();
        Setting::create(['key' => 'enable_2fa', 'value' => '1']);
        
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'two_factor_enabled' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login.2fa'));
        $this->assertGuest(); // Not fully authenticated yet
        $this->assertTrue(session()->has('2fa:user:id'));
        
        Mail::assertSent(TwoFactorCodeMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_user_can_verify_2fa_code()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'two_factor_enabled' => true,
            'two_factor_code' => '123456',
            'two_factor_expires_at' => now()->addMinutes(5),
        ]);

        session()->put('2fa:user:id', $user->id);

        $response = $this->post(route('login.2fa.verify'), [
            'two_factor_code' => '123456',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        
        $user->refresh();
        $this->assertNull($user->two_factor_code);
        $this->assertNull($user->two_factor_expires_at);
        $this->assertFalse(session()->has('2fa:user:id'));
    }

    public function test_2fa_verification_fails_with_expired_code()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'two_factor_enabled' => true,
            'two_factor_code' => '123456',
            'two_factor_expires_at' => now()->subMinutes(1),
        ]);

        session()->put('2fa:user:id', $user->id);

        $response = $this->post(route('login.2fa.verify'), [
            'two_factor_code' => '123456',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        
        $user->refresh();
        $this->assertNull($user->two_factor_code);
        $this->assertNull($user->two_factor_expires_at);
    }
}
