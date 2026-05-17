<?php

namespace Tests\Feature\Backend;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class TwoFactorSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_admin_can_update_2fa_settings()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('settings.logon.update'), [
            'min_password_length' => 8,
            'max_password_length' => 32,
            'enable_2fa' => 1,
            'force_2fa_mod_user' => 1,
        ]);

        $response->assertSessionHas('success');
        
        $this->assertEquals(1, Setting::where('key', 'enable_2fa')->value('value'));
        $this->assertEquals(1, Setting::where('key', 'force_2fa_mod_user')->value('value'));
    }

    public function test_admin_forcing_2fa_updates_mod_and_user_records_in_db()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $mod = User::factory()->create(['role' => 'mod', 'two_factor_enabled' => false]);
        $user = User::factory()->create(['role' => 'user', 'two_factor_enabled' => false]);
        $none = User::factory()->create(['role' => 'none', 'two_factor_enabled' => false]);

        $this->actingAs($admin)->post(route('settings.logon.update'), [
            'min_password_length' => 8,
            'max_password_length' => 32,
            'enable_2fa' => 1,
            'force_2fa_mod_user' => 1,
        ]);

        $this->assertTrue($mod->fresh()->two_factor_enabled);
        $this->assertTrue($user->fresh()->two_factor_enabled);
        $this->assertFalse($none->fresh()->two_factor_enabled); // Role 'none' should not be forced
    }

    public function test_user_cannot_toggle_2fa_when_forced_by_admin()
    {
        Setting::updateOrCreate(['key' => 'force_2fa_mod_user'], ['value' => 1]);

        $user = User::factory()->create([
            'role' => 'user',
            'two_factor_enabled' => false,
        ]);

        $response = $this->actingAs($user)->post(route('settings.2fa.toggle'), [
            'two_factor_enabled' => 1,
        ]);

        $response->assertSessionHasErrors();
        $this->assertFalse($user->fresh()->two_factor_enabled);
    }

    public function test_user_can_toggle_2fa_when_not_forced()
    {
        Setting::updateOrCreate(['key' => 'force_2fa_mod_user'], ['value' => 0]);

        $user = User::factory()->create([
            'role' => 'user',
            'two_factor_enabled' => false,
        ]);

        $response = $this->actingAs($user)->post(route('settings.2fa.toggle'), [
            'two_factor_enabled' => 1,
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue($user->fresh()->two_factor_enabled);
    }

    public function test_2fa_is_forced_during_login_for_user_role()
    {
        Setting::updateOrCreate(['key' => 'enable_2fa'], ['value' => 1]);
        Setting::updateOrCreate(['key' => 'force_2fa_mod_user'], ['value' => 1]);

        $user = User::factory()->create([
            'role' => 'user',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'two_factor_enabled' => false, // explicitly false
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        // Should redirect to 2fa form because it's forced
        $response->assertRedirect(route('login.2fa'));
    }

    public function test_2fa_is_not_forced_during_login_when_setting_is_disabled()
    {
        Setting::updateOrCreate(['key' => 'enable_2fa'], ['value' => 1]);
        Setting::updateOrCreate(['key' => 'force_2fa_mod_user'], ['value' => 0]);

        $user = User::factory()->create([
            'role' => 'user',
            'email' => 'user@example.com',
            'password' => Hash::make('password123'),
            'two_factor_enabled' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        // Should redirect to dashboard, not 2fa
        $response->assertRedirect('/dashboard');
    }

    public function test_settings_view_shows_2fa_when_enabled()
    {
        Setting::updateOrCreate(['key' => 'enable_2fa'], ['value' => 1]);
        Setting::updateOrCreate(['key' => 'force_2fa_mod_user'], ['value' => 0]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get(route('settings'));

        $response->assertStatus(200);
        $response->assertSee('Uwierzytelnianie Dwuskładnikowe (2FA)');
    }

    public function test_settings_view_hides_2fa_when_disabled()
    {
        Setting::updateOrCreate(['key' => 'enable_2fa'], ['value' => 0]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get(route('settings'));

        $response->assertStatus(200);
        $response->assertDontSee('Uwierzytelnianie Dwuskładnikowe (2FA)');
    }
}
