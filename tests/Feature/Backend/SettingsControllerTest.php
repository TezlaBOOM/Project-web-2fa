<?php

namespace Tests\Feature\Backend;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_toggle_2fa()
    {
        $user = User::factory()->create([
            'two_factor_enabled' => false,
        ]);

        $response = $this->actingAs($user)->post(route('settings.2fa.toggle'), [
            'two_factor_enabled' => '1',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue($user->refresh()->two_factor_enabled);
        
        $response = $this->actingAs($user)->post(route('settings.2fa.toggle'), []); // Not sending means false
        $this->assertFalse($user->refresh()->two_factor_enabled);
    }

    public function test_admin_can_update_logon_settings()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('settings.logon.update'), [
            'min_password_length' => 8,
            'max_password_length' => 20,
            'require_special_character' => '1',
            'enable_2fa' => '1',
        ]);

        $response->assertSessionHas('success');
        
        $this->assertEquals('8', Setting::where('key', 'min_password_length')->first()->value);
        $this->assertEquals('1', Setting::where('key', 'enable_2fa')->first()->value);
    }
    
    public function test_non_admin_cannot_update_logon_settings()
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->post(route('settings.logon.update'), [
            'min_password_length' => 8,
            'max_password_length' => 20,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_update_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123'),
        ]);

        $response = $this->actingAs($user)->post(route('settings.password'), [
            'current_password' => 'oldpassword123',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpassword123', $user->refresh()->password));
    }
}
