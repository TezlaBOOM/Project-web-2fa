<?php

namespace Tests\Feature\Backend;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_admin_can_export_database()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('settings.database.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('attachment; filename="backup_', $response->headers->get('content-disposition'));
        $this->assertStringEndsWith('.sql"', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Kopia zapasowa bazy danych', $content);
        $this->assertStringContainsString('users', $content);
        $this->assertStringContainsString($admin->email, $content);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $admin->id,
            'action' => 'export_database',
        ]);
    }

    public function test_non_admin_cannot_export_database()
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);
        $mod = User::factory()->create([
            'role' => 'mod',
        ]);
        $view = User::factory()->create([
            'role' => 'view',
        ]);

        $this->actingAs($user)->get(route('settings.database.export'))->assertStatus(403);
        $this->actingAs($mod)->get(route('settings.database.export'))->assertStatus(403);
        $this->actingAs($view)->get(route('settings.database.export'))->assertStatus(403);
    }

    public function test_settings_page_shows_export_card_for_admin_only()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $view = User::factory()->create(['role' => 'view']);

        $resAdmin = $this->actingAs($admin)->get(route('settings'));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Kopia zapasowa i Import bazy danych');
        $resAdmin->assertSee(route('settings.database.export'));
        $resAdmin->assertSee(route('settings.database.import'));

        $resView = $this->actingAs($view)->get(route('settings'));
        $resView->assertStatus(200);
        $resView->assertDontSee('Kopia zapasowa i Import bazy danych');
        $resView->assertDontSee(route('settings.database.export'));
        $resView->assertDontSee(route('settings.database.import'));
    }

    public function test_admin_can_import_database()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $sqlContent = "
        INSERT INTO settings (key, value, created_at, updated_at) VALUES ('test_imported_key', 'test_imported_val', datetime('now'), datetime('now'));
        ";

        $file = UploadedFile::fake()->createWithContent('backup.sql', $sqlContent);

        $response = $this->actingAs($admin)->post(route('settings.database.import'), [
            'database_file' => $file,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('settings', [
            'key' => 'test_imported_key',
            'value' => 'test_imported_val',
        ]);

        $this->assertDatabaseHas('user_activities', [
            'user_id' => $admin->id,
            'action' => 'import_database',
        ]);
    }

    public function test_non_admin_cannot_import_database()
    {
        $user = User::factory()->create(['role' => 'user']);
        $mod = User::factory()->create(['role' => 'mod']);
        $view = User::factory()->create(['role' => 'view']);

        $file = UploadedFile::fake()->createWithContent('backup.sql', 'SELECT 1;');

        $this->actingAs($user)->post(route('settings.database.import'), ['database_file' => $file])->assertStatus(403);
        $this->actingAs($mod)->post(route('settings.database.import'), ['database_file' => $file])->assertStatus(403);
        $this->actingAs($view)->post(route('settings.database.import'), ['database_file' => $file])->assertStatus(403);
    }

    public function test_import_database_validates_file()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Missing file
        $res = $this->actingAs($admin)->post(route('settings.database.import'), []);
        $res->assertSessionHasErrors(['database_file']);

        // Invalid extension
        $txtFile = UploadedFile::fake()->create('document.pdf', 100);
        $res = $this->actingAs($admin)->post(route('settings.database.import'), [
            'database_file' => $txtFile,
        ]);
        $res->assertSessionHasErrors(['database_file']);

        // Empty file
        $emptySql = UploadedFile::fake()->createWithContent('empty.sql', '');
        $res = $this->actingAs($admin)->post(route('settings.database.import'), [
            'database_file' => $emptySql,
        ]);
        $res->assertSessionHasErrors(['database_file']);
    }
}
