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

        $response->assertSessionHasErrors(['error' => 'Ten użytkownik posiada już uprawnienie do tego modułu w nakładającym się okresie czasowym.']);
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

    public function test_p_access_history_and_multiple_assignments_in_different_periods()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $modul = PModul::create(['nazwa' => 'Modul Historyczny']);
        $operacja = POperacje::create(['nazwa' => 'Test']);

        // Create initial access for period 1 (past/expired)
        $response = $this->actingAs($admin)->post(route('access.store'), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-01-01',
            'valid_to' => '2026-03-31',
            'login' => 'user_login_1',
            'uwagi' => 'Pierwszy okres',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('p_access_history', [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'action' => 'nadano',
            'login' => 'user_login_1',
        ]);

        // Assign same module + operation again for a different period (non-overlapping)
        $response = $this->actingAs($admin)->post(route('access.store'), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-06-01',
            'valid_to' => '2026-08-31',
            'login' => 'user_login_2',
            'uwagi' => 'Drugi okres',
        ]);

        $response->assertRedirect();

        // Both accesses must exist in P_access table simultaneously
        $this->assertEquals(2, PAccess::where('user_id', $user->id)
            ->where('p_modul_id', $modul->id)
            ->where('p_operacje_id', $operacja->id)
            ->count());

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
        $this->assertCount(2, $response->json('history'));
    }

    public function test_cannot_assign_overlapping_periods_for_same_module_and_operation()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $modul = PModul::create(['nazwa' => 'Modul Terminy']);
        $operacja = POperacje::create(['nazwa' => 'Test']);

        // First access: 2026-03-01 to 2026-06-30
        PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-03-01',
            'valid_to' => '2026-06-30',
        ]);

        // Overlapping attempt: 2026-05-01 to 2026-08-31
        $response = $this->actingAs($admin)->post(route('access.store'), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-05-01',
            'valid_to' => '2026-08-31',
        ]);

        $response->assertSessionHasErrors(['error' => 'Ten użytkownik posiada już uprawnienie do tego modułu w nakładającym się okresie czasowym.']);

        // Non-overlapping attempt: 2026-07-01 to 2026-10-31 (should succeed)
        $responseSuccess = $this->actingAs($admin)->post(route('access.store'), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-07-01',
            'valid_to' => '2026-10-31',
        ]);

        $responseSuccess->assertRedirect();
        $this->assertEquals(2, PAccess::where('user_id', $user->id)->count());
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

    public function test_access_user_sorting()
    {
        $admin = $this->createAdmin();
        $u1 = User::factory()->create(['name' => 'Adam Nowak', 'email' => 'adam@test.pl']);
        $u2 = User::factory()->create(['name' => 'Zenon Kowalski', 'email' => 'zenon@test.pl']);

        // 1. Sort by name asc
        $response = $this->actingAs($admin)->get(route('access.index', ['sort_by' => 'name', 'sort_dir' => 'asc']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $posAdam = strpos($html, 'Adam Nowak');
        $posZenon = strpos($html, 'Zenon Kowalski');
        $this->assertTrue($posAdam < $posZenon, "Adam should appear before Zenon when sorting by name asc");

        // 2. Sort by name desc
        $response = $this->actingAs($admin)->get(route('access.index', ['sort_by' => 'name', 'sort_dir' => 'desc']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $posAdam = strpos($html, 'Adam Nowak');
        $posZenon = strpos($html, 'Zenon Kowalski');
        $this->assertTrue($posZenon < $posAdam, "Zenon should appear before Adam when sorting by name desc");
    }

    /**
     * Użytkownicy z wygasłą datą ważności wydziału nie powinni być widoczni
     * przy filtrowaniu po wydziale.
     */
    public function test_user_with_expired_department_date_is_hidden_in_dept_filter()
    {
        $admin = $this->createAdmin();
        $dept = Departament::create(['Nazwa' => 'Wydział A']);

        $activeUser  = User::factory()->create(['name' => 'Aktywny Użytkownik', 'role' => 'user', 'is_active' => true]);
        $expiredUser = User::factory()->create(['name' => 'Wygasły Użytkownik', 'role' => 'user', 'is_active' => true]);
        $noDateUser  = User::factory()->create(['name' => 'Bezterminowy Użytkownik', 'role' => 'user', 'is_active' => true]);

        // Aktywny — data do w przyszłości
        $activeUser->departments()->attach($dept->ID_Departament, [
            'od' => now()->subDays(10)->toDateString(),
            'do' => now()->addDays(30)->toDateString(),
        ]);

        // Wygasły — data do w przeszłości
        $expiredUser->departments()->attach($dept->ID_Departament, [
            'od' => now()->subDays(60)->toDateString(),
            'do' => now()->subDays(1)->toDateString(),
        ]);

        // Bezterminowy — brak daty końcowej
        $noDateUser->departments()->attach($dept->ID_Departament, [
            'od' => now()->subDays(5)->toDateString(),
            'do' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('access.index', ['dept_id' => $dept->ID_Departament]));
        $response->assertStatus(200);

        // Aktywny i bezterminowy powinni być widoczni
        $response->assertSee('Aktywny Użytkownik');
        $response->assertSee('Bezterminowy Użytkownik');

        // Wygasły NIE powinien być widoczny
        $response->assertDontSee('Wygasły Użytkownik');
    }

    /**
     * Użytkownicy z datą ważności wydziału dokładnie dzisiaj powinni być widoczni.
     */
    public function test_user_with_department_date_expiring_today_is_visible()
    {
        $admin = $this->createAdmin();
        $dept = Departament::create(['Nazwa' => 'Wydział B']);

        $todayUser = User::factory()->create(['name' => 'Dzisiejszy Użytkownik', 'role' => 'user', 'is_active' => true]);

        // Data do = dzisiaj — powinien być widoczny (>= today)
        $todayUser->departments()->attach($dept->ID_Departament, [
            'od' => now()->subDays(10)->toDateString(),
            'do' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get(route('access.index', ['dept_id' => $dept->ID_Departament]));
        $response->assertStatus(200);
        $response->assertSee('Dzisiejszy Użytkownik');
    }

    /**
     * Test filtrowania po statusie konta użytkownika (aktywny / nieaktywny).
     */
    public function test_access_index_user_status_filtering()
    {
        $admin = $this->createAdmin();

        $activeUser   = User::factory()->create(['name' => 'Aktywny Jan', 'is_active' => true]);
        $inactiveUser = User::factory()->create(['name' => 'Nieaktywny Piotr', 'is_active' => false]);

        // 1. Filter active
        $responseActive = $this->actingAs($admin)->get(route('access.index', ['user_status' => 'active']));
        $responseActive->assertStatus(200);
        $responseActive->assertSee('Aktywny Jan');
        $responseActive->assertDontSee('Nieaktywny Piotr');

        // 2. Filter inactive
        $responseInactive = $this->actingAs($admin)->get(route('access.index', ['user_status' => 'inactive']));
        $responseInactive->assertStatus(200);
        $responseInactive->assertSee('Nieaktywny Piotr');
        $responseInactive->assertDontSee('Aktywny Jan');
    }

    public function test_access_permissions_sorting_default_module_and_date()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['role' => 'user']);
        
        $modZ = PModul::create(['nazwa' => 'Zarzadzanie']);
        $modA = PModul::create(['nazwa' => 'Administracja']);
        $op = POperacje::create(['nazwa' => 'Podglad']);

        // Create permission for Zarzadzanie (later alphabetically)
        PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modZ->id,
            'p_operacje_id' => $op->id,
            'valid_from' => '2026-01-01',
            'valid_to' => '2026-06-30',
        ]);

        // Create two permissions for Administracja with different dates
        PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modA->id,
            'p_operacje_id' => $op->id,
            'valid_from' => '2026-07-01',
            'valid_to' => '2026-12-31',
        ]);
        PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modA->id,
            'p_operacje_id' => $op->id,
            'valid_from' => '2026-01-01',
            'valid_to' => '2026-03-31',
        ]);

        // 1. Default sorting (module asc, then date asc)
        $response = $this->actingAs($admin)->get(route('access.index', ['user_id' => $user->id]));
        $response->assertStatus(200);
        $html = $response->getContent();
        
        $posA = strpos($html, 'Administracja');
        $posZ = strpos($html, 'Zarzadzanie');
        $this->assertTrue($posA < $posZ, "Administracja should appear before Zarzadzanie");

        $posDateEarly = strpos($html, 'od: 2026-01-01');
        $posDateLate  = strpos($html, 'od: 2026-07-01');
        $this->assertTrue($posDateEarly < $posDateLate, "Earlier date (2026-01-01) should appear before (2026-07-01)");

        // 2. Sort by module desc
        $responseDesc = $this->actingAs($admin)->get(route('access.index', [
            'user_id' => $user->id,
            'access_sort_by' => 'module',
            'access_sort_dir' => 'desc',
        ]));
        $responseDesc->assertStatus(200);
        $htmlDesc = $responseDesc->getContent();
        $posADesc = strpos($htmlDesc, 'Administracja');
        $posZDesc = strpos($htmlDesc, 'Zarzadzanie');
        $this->assertTrue($posZDesc < $posADesc, "Zarzadzanie should appear before Administracja when sorted desc");
    }

    public function test_access_permissions_sorting_by_date()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['role' => 'user']);
        
        $mod1 = PModul::create(['nazwa' => 'Modul Styczen']);
        $mod2 = PModul::create(['nazwa' => 'Modul Grudzien']);
        $op = POperacje::create(['nazwa' => 'Podglad']);

        PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $mod1->id,
            'p_operacje_id' => $op->id,
            'valid_from' => '2026-01-01',
            'valid_to' => '2026-01-31',
        ]);

        PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $mod2->id,
            'p_operacje_id' => $op->id,
            'valid_from' => '2026-12-01',
            'valid_to' => '2026-12-31',
        ]);

        // Sort by date asc
        $responseAsc = $this->actingAs($admin)->get(route('access.index', [
            'user_id' => $user->id,
            'access_sort_by' => 'date',
            'access_sort_dir' => 'asc',
        ]));
        $responseAsc->assertStatus(200);
        $htmlAsc = $responseAsc->getContent();
        $pos1Asc = strpos($htmlAsc, 'Modul Styczen');
        $pos2Asc = strpos($htmlAsc, 'Modul Grudzien');
        $this->assertTrue($pos1Asc < $pos2Asc, "January module should appear before December module when sorted by date asc");

        // Sort by date desc
        $responseDesc = $this->actingAs($admin)->get(route('access.index', [
            'user_id' => $user->id,
            'access_sort_by' => 'date',
            'access_sort_dir' => 'desc',
        ]));
        $responseDesc->assertStatus(200);
        $htmlDesc = $responseDesc->getContent();
        $pos1Desc = strpos($htmlDesc, 'Modul Styczen');
        $pos2Desc = strpos($htmlDesc, 'Modul Grudzien');
        $this->assertTrue($pos2Desc < $pos1Desc, "December module should appear before January module when sorted by date desc");
    }

    public function test_admin_can_access_create_form_with_duplicate_id_and_prefilled_data()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $modul = PModul::create(['nazwa' => 'Finanse']);
        $operacja = POperacje::create(['nazwa' => 'Eksport']);

        $access = PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-03-01',
            'valid_to' => '2026-06-30',
            'login' => 'j_finanse',
            'uwagi' => 'Uprawnienie pierwotne',
        ]);

        // Check edit view contains duplicate button
        $editResponse = $this->actingAs($admin)->get(route('access.edit', $access->id));
        $editResponse->assertStatus(200);
        $editResponse->assertSee(route('access.create', ['duplicate_id' => $access->id]));
        $editResponse->assertSee('Duplikuj');

        // Check create view with duplicate_id pre-populates fields
        $createResponse = $this->actingAs($admin)->get(route('access.create', ['duplicate_id' => $access->id]));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('Duplikuj uprawnienie');
        $createResponse->assertSee('Dodaj nową pozycję');
        $createResponse->assertSee('value="2026-03-01"', false);
        $createResponse->assertSee('value="2026-06-30"', false);
        $createResponse->assertSee('value="j_finanse"', false);
        $createResponse->assertSee('Uprawnienie pierwotne');
    }

    public function test_access_matching_departments_logic()
    {
        $user = User::factory()->create(['role' => 'user']);
        $deptIT = Departament::create(['Nazwa' => 'Dział IT']);
        $deptFinanse = Departament::create(['Nazwa' => 'Dział Finansów']);

        // User worked in IT from Jan 1 to Jun 30, and in Finanse from Jul 1 to Dec 31
        $user->departments()->attach($deptIT->ID_Departament, [
            'od' => '2026-01-01',
            'do' => '2026-06-30',
        ]);
        $user->departments()->attach($deptFinanse->ID_Departament, [
            'od' => '2026-07-01',
            'do' => '2026-12-31',
        ]);
        $user->load('departments');

        $modul = PModul::create(['nazwa' => 'Modul Test']);
        $operacja = POperacje::create(['nazwa' => 'Operacja Test']);

        // 1. Access completely inside IT period
        $accessIT = PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-02-01',
            'valid_to' => '2026-04-30',
        ]);
        $matchedIT = $accessIT->getMatchingDepartments($user);
        $this->assertCount(1, $matchedIT);
        $this->assertEquals('Dział IT', $matchedIT->first()->Nazwa);

        // 2. Access completely inside Finanse period
        $accessFinanse = PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-08-01',
            'valid_to' => '2026-10-31',
        ]);
        $matchedFinanse = $accessFinanse->getMatchingDepartments($user);
        $this->assertCount(1, $matchedFinanse);
        $this->assertEquals('Dział Finansów', $matchedFinanse->first()->Nazwa);

        // 3. Access overlapping both periods (May 1 to Aug 31)
        $accessBoth = PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-05-01',
            'valid_to' => '2026-08-31',
        ]);
        $matchedBoth = $accessBoth->getMatchingDepartments($user);
        $this->assertCount(2, $matchedBoth);

        // 4. Access outside any department period (year 2027)
        $accessNone = PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2027-01-01',
            'valid_to' => '2027-05-01',
        ]);
        $matchedNone = $accessNone->getMatchingDepartments($user);
        $this->assertCount(0, $matchedNone);
    }

    public function test_access_index_displays_matching_departments()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['role' => 'user']);
        $dept = Departament::create(['Nazwa' => 'Wydział Logistyki']);
        $user->departments()->attach($dept->ID_Departament, [
            'od' => '2026-01-01',
            'do' => '2026-12-31',
        ]);

        $modul = PModul::create(['nazwa' => 'Magazyn']);
        $operacja = POperacje::create(['nazwa' => 'Wydanie']);

        PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $operacja->id,
            'valid_from' => '2026-03-01',
            'valid_to' => '2026-09-30',
        ]);

        $response = $this->actingAs($admin)->get(route('access.index', ['user_id' => $user->id]));
        $response->assertStatus(200);
        $response->assertSee('Wydział Logistyki');
    }
}

