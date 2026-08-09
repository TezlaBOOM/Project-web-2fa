<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\PModul;
use App\Models\POperacje;
use App\Models\PAccess;
use App\Models\Departament;
use Illuminate\Http\UploadedFile;

class PAccessControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin()
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function createModWithDepartment()
    {
        $mod = User::factory()->create(['role' => 'mod', 'is_active' => true]);
        $dept = Departament::create(['Nazwa' => 'Test Dept']);
        $mod->departments()->attach($dept->ID_Departament);
        return [$mod, $dept];
    }

    public function test_admin_can_manage_access()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $modul = PModul::create(['nazwa' => 'Modul 1']);
        $operacja = POperacje::create(['nazwa' => 'Odczyt']);

        $response = $this->actingAs($admin)->post(route('access.store'), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
        ]);

        $response->assertRedirect(route('access.index', ['user_id' => $user->id]));
        $this->assertDatabaseHas('P_access', [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
        ]);

        $access = PAccess::first();
        $response = $this->actingAs($admin)->delete(route('access.destroy', $access->id));
        $response->assertRedirect(route('access.index', ['user_id' => $access->user_id]));
        $this->assertDatabaseMissing('P_access', ['id' => $access->id]);
    }

    public function test_cannot_assign_duplicate_access()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $modul = PModul::create(['nazwa' => 'Modul 1']);
        $operacja = POperacje::create(['nazwa' => 'Odczyt']);

        PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
        ]);

        $response = $this->actingAs($admin)->post(route('access.store'), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
        ]);

        $response->assertSessionHasErrors(['error' => 'Ten użytkownik posiada już takie aktywne uprawnienie.']);
    }

    public function test_mod_can_only_view_accesses_for_their_department_users()
    {
        [$mod, $dept] = $this->createModWithDepartment();
        
        $myUser = User::factory()->create(['role' => 'user']);
        $myUser->departments()->attach($dept->ID_Departament);

        $otherUser = User::factory()->create(['role' => 'user']);
        // not in dept

        $modul = PModul::create(['nazwa' => 'Mod 1']);
        $op = POperacje::create(['nazwa' => 'Op 1']);

        $accessMy = PAccess::create(['user_id' => $myUser->id, 'p_modul_id' => $modul->id, 'p_operacje_id' => $op->id]);
        $accessOther = PAccess::create(['user_id' => $otherUser->id, 'p_modul_id' => $modul->id, 'p_operacje_id' => $op->id]);

        $response = $this->actingAs($mod)->get(route('access.index'));
        
        $response->assertStatus(200);
        $response->assertSee($myUser->name);
        $response->assertDontSee($otherUser->name);
    }

    public function test_admin_can_set_and_update_validity_dates()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $modul = PModul::create(['nazwa' => 'ERP']);
        $operacja = POperacje::create(['nazwa' => 'Edycja']);

        // 1. Create access with valid date ranges
        $response = $this->actingAs($admin)->post(route('access.store'), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-05-01',
            'valid_to' => '2026-05-31',
        ]);

        $response->assertRedirect(route('access.index', ['user_id' => $user->id]));
        $this->assertDatabaseHas('P_access', [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-05-01 00:00:00',
            'valid_to' => '2026-05-31 00:00:00',
        ]);

        // 2. Try to store with valid_to before valid_from (should fail validation)
        $responseInvalid = $this->actingAs($admin)->post(route('access.store'), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-05-31',
            'valid_to' => '2026-05-01',
        ]);
        $responseInvalid->assertSessionHasErrors(['valid_to']);

        // 3. Update existing access
        $access = PAccess::first();
        $responseUpdate = $this->actingAs($admin)->put(route('access.update', $access->id), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-06-01',
            'valid_to' => null,
        ]);

        $responseUpdate->assertRedirect(route('access.index', ['user_id' => $user->id]));
        $this->assertDatabaseHas('P_access', [
            'id' => $access->id,
            'valid_from' => '2026-06-01 00:00:00',
            'valid_to' => null,
        ]);
    }

    public function test_paccess_is_valid_logic()
    {
        $user = User::factory()->create(['role' => 'user']);
        $modul = PModul::create(['nazwa' => 'ERP']);
        $operacja = POperacje::create(['nazwa' => 'Edycja']);

        // Active: dates covering today (e.g. valid from yesterday, to tomorrow)
        $activeAccess = PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => now()->subDay()->toDateString(),
            'valid_to' => now()->addDay()->toDateString(),
        ]);
        $this->assertTrue($activeAccess->isValid());

        // Active: no limits
        $unlimitedAccess = PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => null,
            'valid_to' => null,
        ]);
        $this->assertTrue($unlimitedAccess->isValid());

        // Expired: valid_to is yesterday
        $expiredAccess = PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => now()->subDays(5)->toDateString(),
            'valid_to' => now()->subDay()->toDateString(),
        ]);
        $this->assertFalse($expiredAccess->isValid());

        // Not yet active: valid_from is tomorrow
        $futureAccess = PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => now()->addDay()->toDateString(),
            'valid_to' => now()->addDays(5)->toDateString(),
        ]);
        $this->assertFalse($futureAccess->isValid());
    }

    public function test_user_has_active_access_logic()
    {
        $user = User::factory()->create(['role' => 'user']);
        $modul = PModul::create(['nazwa' => 'ERP']);
        $opView = POperacje::create(['nazwa' => 'Podgląd']);
        $opEdit = POperacje::create(['nazwa' => 'Edycja']);

        // ERP Podgląd: valid from May 1 2020 to Dec 31 2050 (active today)
        PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $opView->id,
            'valid_from' => '2020-05-01',
            'valid_to' => '2050-12-31',
        ]);

        // ERP Edycja: valid from tomorrow onwards (not yet active today)
        PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $opEdit->id,
            'valid_from' => now()->addDay()->toDateString(),
            'valid_to' => '2050-12-31',
        ]);

        $this->assertTrue($user->hasActiveAccess('ERP', 'Podgląd'));
        $this->assertFalse($user->hasActiveAccess('ERP', 'Edycja'));

        // Admin has access to everything
        $admin = $this->createAdmin();
        $this->assertTrue($admin->hasActiveAccess('ERP', 'Edycja'));
    }

    public function test_p_access_history_archiving_and_multiple_assignments()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $modul = PModul::create(['nazwa' => 'Modul Historyczny']);
        $operacja = POperacje::create(['nazwa' => 'Test']);

        // Create initial access
        $response = $this->actingAs($admin)->post(route('access.store'), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => now()->toDateString(),
            'valid_to' => now()->addDays(5)->toDateString(),
            'login' => 'user_login',
            'uwagi' => 'Jakieś uwagi',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('p_access_history', [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'action' => 'nadano',
        ]);

        // Manually update P_access dates to expire it
        $access = PAccess::where('user_id', $user->id)->first();
        $access->update([
            'valid_from' => now()->subDays(10)->toDateString(),
            'valid_to' => now()->subDays(5)->toDateString(),
        ]);

        // Assigning same access again should archive expired one and create a new active one
        $response = $this->actingAs($admin)->post(route('access.store'), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => now()->toDateString(),
            'valid_to' => now()->addDays(10)->toDateString(),
            'login' => 'user_login_2',
            'uwagi' => 'Nowe uwagi',
        ]);

        $response->assertRedirect();
        
        // Assert expired was archived with 'wygasło' action
        $this->assertDatabaseHas('p_access_history', [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'action' => 'wygasło',
        ]);

        // Assert new access history entry was created
        $this->assertDatabaseHas('p_access_history', [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'action' => 'nadano',
            'login' => 'user_login_2',
        ]);

        // Fetch history via AJAX
        $response = $this->actingAs($admin)->getJson(route('access.history', [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
        ]));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(3, $response->json('history')); // 'nadano' first, 'wygasło' second, 'nadano' third
    }

    public function test_access_csv_features()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['email' => 'import_access@test.pl', 'role' => 'user']);
        
        // 1. View is restricted
        $response = $this->actingAs($admin)->get(route('access.csv'));
        $response->assertStatus(200);

        // 2. Pattern download
        $response = $this->actingAs($admin)->get(route('access.csv.pattern'));
        $response->assertStatus(200);
        $this->assertStringContainsString('email_użytkownika,nazwa_modułu,nazwa_operacji', $response->streamedContent());

        // 3. Import CSV
        $csvData = "email_użytkownika,nazwa_modułu,nazwa_operacji,ważne_od,ważne_do,login,uwagi\n" .
                   "import_access@test.pl,Modul Nowy,Edycja,2026-08-01,2026-12-31,implogin,impuwagi\n";
        
        $file = UploadedFile::fake()->createWithContent('access.csv', $csvData);

        $response = $this->actingAs($admin)->post(route('access.csv.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        
        // Check active access created
        $this->assertDatabaseHas('P_access', [
            'user_id' => $user->id,
            'login' => 'implogin',
            'uwagi' => 'impuwagi',
        ]);

        // Check history logged
        $this->assertDatabaseHas('p_access_history', [
            'user_id' => $user->id,
            'action' => 'nadano',
            'login' => 'implogin',
        ]);

        // 4. Export CSV
        $response = $this->actingAs($admin)->get(route('access.csv.export'));
        $response->assertStatus(200);
        $this->assertStringContainsString('import_access@test.pl,"Modul Nowy",Edycja', $response->streamedContent());
    }

    public function test_csv_import_export_nested_modules()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create([
            'email' => 'nested@test.pl',
        ]);

        // Import CSV with a nested module path
        $csvData = "email_użytkownika,nazwa_modułu,nazwa_operacji,ważne_od,ważne_do,login,uwagi\n" .
                   "nested@test.pl,Kategoria Główna / Podkategoria 1 / Podkategoria 2,Podgląd,2026-08-01,2026-12-31,nlogin,nuwagi\n";
        
        $file = UploadedFile::fake()->createWithContent('access.csv', $csvData);

        $response = $this->actingAs($admin)->post(route('access.csv.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();

        // Check nested PModul structure created
        $root = \App\Models\PModul::where('nazwa', 'Kategoria Główna')->whereNull('parent_id')->first();
        $this->assertNotNull($root);

        $sub1 = \App\Models\PModul::where('nazwa', 'Podkategoria 1')->where('parent_id', $root->id)->first();
        $this->assertNotNull($sub1);

        $sub2 = \App\Models\PModul::where('nazwa', 'Podkategoria 2')->where('parent_id', $sub1->id)->first();
        $this->assertNotNull($sub2);

        // Check active access created with sub2 id
        $this->assertDatabaseHas('P_access', [
            'user_id' => $user->id,
            'p_modul_id' => $sub2->id,
            'login' => 'nlogin',
            'uwagi' => 'nuwagi',
        ]);

        // Export and check nested path serialization
        $response = $this->actingAs($admin)->get(route('access.csv.export'));
        $response->assertStatus(200);
        $this->assertStringContainsString('nested@test.pl,"Kategoria Główna / Podkategoria 1 / Podkategoria 2",Podgląd', $response->streamedContent());
    }
}
