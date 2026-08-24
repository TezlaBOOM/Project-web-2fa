<?php

namespace Tests\Feature\Backend;

use Tests\TestCase;
use App\Models\User;
use App\Models\Departament;
use App\Models\PModul;
use App\Models\POperacje;
use App\Models\PAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin()
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createMod()
    {
        return User::factory()->create([
            'role' => 'mod',
            'is_active' => true,
        ]);
    }

    public function test_csv_view_is_restricted_to_admin()
    {
        $admin = $this->createAdmin();
        $mod = $this->createMod();

        $response = $this->actingAs($admin)->get(route('users.csv'));
        $response->assertStatus(200);

        $response = $this->actingAs($mod)->get(route('users.csv'));
        $response->assertStatus(403);
    }

    public function test_csv_pattern_download()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('users.csv.pattern'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="wzor_uzytkownicy.csv"');
        $this->assertStringContainsString('imię_i_nazwisko,email,rola,status,wydział,data_od,data_do', $response->streamedContent());
    }

    public function test_csv_export()
    {
        $admin = $this->createAdmin();
        $user1 = User::factory()->create(['name' => 'Janusz Kowal', 'email' => 'janusz@test.pl', 'role' => 'user', 'is_active' => true]);
        $dept = Departament::create(['Nazwa' => 'Dzial Marketingu']);
        $user1->departments()->attach($dept->ID_Departament);

        $response = $this->actingAs($admin)->get(route('users.csv.export'));
        $response->assertStatus(200);
        
        $content = $response->streamedContent();
        $this->assertStringContainsString('"Janusz Kowal",janusz@test.pl,user,aktywny,"Dzial Marketingu",,', $content);
    }

    public function test_csv_import()
    {
        $admin = $this->createAdmin();

        $csvData = "imię_i_nazwisko,email,rola,status,wydział,data_od,data_do\n" .
                   "Zofia Importowana,zofia@import.pl,mod,aktywny,Dział Testowy,,\n" .
                   "Zofia Importowana,zofia@import.pl,mod,aktywny,Nowy Dział,,\n" .
                   "Stefan Nieaktywny,stefan@import.pl,user,nieaktywny,,,\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csvData);

        $response = $this->actingAs($admin)->post(route('users.csv.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        
        // Assert users were created
        $this->assertDatabaseHas('users', [
            'name' => 'Zofia Importowana',
            'email' => 'zofia@import.pl',
            'role' => 'mod',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'Stefan Nieaktywny',
            'email' => 'stefan@import.pl',
            'role' => 'user',
            'is_active' => false,
        ]);

        // Assert departments were created and synchronized
        $this->assertDatabaseHas('Departament', ['Nazwa' => 'Dział Testowy']);
        $this->assertDatabaseHas('Departament', ['Nazwa' => 'Nowy Dział']);

        $zofia = User::where('email', 'zofia@import.pl')->first();
        $this->assertCount(2, $zofia->departments);
    }

    public function test_csv_import_updates_existing_user_by_id()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create([
            'name' => 'Stary Nazwisko',
            'email' => 'stary@test.pl',
            'role' => 'user',
            'is_active' => true,
        ]);

        $csvData = "id,imię_i_nazwisko,email,rola,status,wydział,data_od,data_do\n" .
                   "{$user->id},Nowy Nazwisko,stary@test.pl,mod,nieaktywny,Wydział Nowy,,\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csvData);

        $response = $this->actingAs($admin)->post(route('users.csv.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();

        // Check updated fields
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nowy Nazwisko',
            'role' => 'mod',
            'is_active' => false,
        ]);

        // Check change logs created
        $this->assertDatabaseHas('user_change_logs', [
            'user_id' => $user->id,
            'field_name' => 'name',
            'old_value' => 'Stary Nazwisko',
            'new_value' => 'Nowy Nazwisko',
        ]);
    }

    public function test_user_searching_including_history()
    {
        $admin = $this->createAdmin();
        
        $user = User::factory()->create([
            'name' => 'Bieżący Użytkownik',
            'email' => 'current@test.pl',
        ]);

        // Add history record for name change
        \App\Models\UserChangeLog::create([
            'user_id' => $user->id,
            'editor_id' => $admin->id,
            'field_name' => 'name',
            'old_value' => 'Dawne Imię',
            'new_value' => 'Bieżący Użytkownik',
        ]);

        // Search by current name
        $response = $this->actingAs($admin)->get(route('users.index', ['search' => 'Bieżący']));
        $response->assertStatus(200);
        $response->assertSee('current@test.pl');

        // Search by historical name
        $response = $this->actingAs($admin)->get(route('users.index', ['search' => 'Dawne']));
        $response->assertStatus(200);
        $response->assertSee('current@test.pl');
    }

    public function test_user_sorting()
    {
        $admin = $this->createAdmin();

        $u1 = User::factory()->create(['name' => 'Adam Nowak', 'email' => 'adam@test.pl']);
        $u2 = User::factory()->create(['name' => 'Zenon Kowalski', 'email' => 'zenon@test.pl']);

        // 1. Sort by name asc
        $response = $this->actingAs($admin)->get(route('users.index', ['sort_by' => 'name', 'sort_dir' => 'asc']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $posAdam = strpos($html, 'Adam Nowak');
        $posZenon = strpos($html, 'Zenon Kowalski');
        $this->assertTrue($posAdam < $posZenon, "Adam should appear before Zenon when sorting by name asc");

        // 2. Sort by name desc
        $response = $this->actingAs($admin)->get(route('users.index', ['sort_by' => 'name', 'sort_dir' => 'desc']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $posAdam = strpos($html, 'Adam Nowak');
        $posZenon = strpos($html, 'Zenon Kowalski');
        $this->assertTrue($posZenon < $posAdam, "Zenon should appear before Adam when sorting by name desc");

        // 3. Sort by email asc
        $response = $this->actingAs($admin)->get(route('users.index', ['sort_by' => 'email', 'sort_dir' => 'asc']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $posAdamEmail = strpos($html, 'adam@test.pl');
        $posZenonEmail = strpos($html, 'zenon@test.pl');
        $this->assertTrue($posAdamEmail < $posZenonEmail, "adam@test.pl should appear before zenon@test.pl when sorting by email asc");

        // 4. Sort by email desc
        $response = $this->actingAs($admin)->get(route('users.index', ['sort_by' => 'email', 'sort_dir' => 'desc']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $posAdamEmail = strpos($html, 'adam@test.pl');
        $posZenonEmail = strpos($html, 'zenon@test.pl');
        $this->assertTrue($posZenonEmail < $posAdamEmail, "zenon@test.pl should appear before adam@test.pl when sorting by email desc");

        // 5. Sort by ID asc
        $response = $this->actingAs($admin)->get(route('users.index', ['sort_by' => 'id', 'sort_dir' => 'asc']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $posU1 = strpos($html, $u1->name);
        $posU2 = strpos($html, $u2->name);
        $this->assertTrue($posU1 < $posU2, "u1 should appear before u2 when sorting by ID asc");

        // 6. Sort by ID desc
        $response = $this->actingAs($admin)->get(route('users.index', ['sort_by' => 'id', 'sort_dir' => 'desc']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $posU1 = strpos($html, $u1->name);
        $posU2 = strpos($html, $u2->name);
        $this->assertTrue($posU2 < $posU1, "u2 should appear before u1 when sorting by ID desc");
    }

    public function test_admin_user_list_shows_status_instead_of_registered_date()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['is_active' => false, 'name' => 'Inactive User']);

        $response = $this->actingAs($admin)->get(route('users.index'));
        $response->assertStatus(200);

        // Header checks
        $response->assertSee('Status');
        $response->assertDontSee('Zarejestrowano');

        // Status badge checks
        $response->assertSee('● Aktywny'); // For the logged in admin
        $response->assertSee('● Nieaktywny'); // For the inactive user
    }

    public function test_admin_can_assign_departments_with_dates_and_it_logs_changes()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create();

        $d1 = Departament::create(['Nazwa' => 'Marketing']);
        $d2 = Departament::create(['Nazwa' => 'Techniczny']);

        $response = $this->actingAs($admin)->put(route('users.update', $user->id), [
            'name' => 'Updated User',
            'email' => $user->email,
            'role' => 'user',
            'assignments' => [
                ['department_id' => $d1->ID_Departament, 'od' => '2026-08-10', 'do' => ''],
                ['department_id' => $d2->ID_Departament, 'od' => '2026-08-01', 'do' => '2026-08-09'],
                ['department_id' => $d1->ID_Departament, 'od' => '2026-01-01', 'do' => '2026-05-01'], // Back to same department!
            ],
        ]);

        $response->assertRedirect(route('users.index'));

        // Check if DB has pivot records with dates (including duplicate departments)
        $this->assertDatabaseHas('DepartamentUsers', [
            'ID_Users' => $user->id,
            'ID_Departament' => $d1->ID_Departament,
            'od' => '2026-08-10',
            'do' => null,
        ]);

        $this->assertDatabaseHas('DepartamentUsers', [
            'ID_Users' => $user->id,
            'ID_Departament' => $d2->ID_Departament,
            'od' => '2026-08-01',
            'do' => '2026-08-09',
        ]);

        $this->assertDatabaseHas('DepartamentUsers', [
            'ID_Users' => $user->id,
            'ID_Departament' => $d1->ID_Departament,
            'od' => '2026-01-01',
            'do' => '2026-05-01',
        ]);

        // Check if logs are generated with the new format (including duplicate departments)
        $this->assertDatabaseHas('user_change_logs', [
            'user_id' => $user->id,
            'field_name' => 'departments',
            'old_value' => 'Brak',
            'new_value' => 'Marketing (od: 2026-01-01, do: 2026-05-01), Marketing (od: 2026-08-10, do: aktualnie), Techniczny (od: 2026-08-01, do: 2026-08-09)',
        ]);
    }

    public function test_user_csv_export_and_import_with_department_dates()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['name' => 'Kamil Śliwka', 'email' => 'kamil@test.pl']);
        $d1 = Departament::create(['Nazwa' => 'Logistyka']);
        $d2 = Departament::create(['Nazwa' => 'Sprzedaż']);

        // Assign depts with dates to user
        $user->departments()->attach($d1->ID_Departament, ['od' => '2026-03-01', 'do' => '']);
        $user->departments()->attach($d2->ID_Departament, ['od' => '2026-01-01', 'do' => '2026-02-28']);

        // 1. Verify Export format
        $response = $this->actingAs($admin)->get(route('users.csv.export'));
        $response->assertStatus(200);
        $content = $response->streamedContent();

        $this->assertStringContainsString('Logistyka,2026-03-01,', $content);
        $this->assertStringContainsString('Sprzedaż,2026-01-01,2026-02-28', $content);

        // 2. Verify Import format parsing (with multiple periods for the same department!)
        $csvData = "id,imię_i_nazwisko,email,rola,status,wydział,data_od,data_do\n" .
                   "{$user->id},Kamil Śliwka,kamil@test.pl,user,aktywny,Magazyn,2026-05-10,2026-05-20\n" .
                   "{$user->id},Kamil Śliwka,kamil@test.pl,user,aktywny,Finanse,2026-06-01,\n" .
                   "{$user->id},Kamil Śliwka,kamil@test.pl,user,aktywny,Magazyn,2026-07-01,\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csvData);

        $response = $this->actingAs($admin)->post(route('users.csv.import'), [
            'csv_file' => $file,
        ]);
        $response->assertRedirect();

        // Check if new departments were assigned with pivot dates
        $dMagazyn = Departament::where('Nazwa', 'Magazyn')->first();
        $dFinanse = Departament::where('Nazwa', 'Finanse')->first();

        $this->assertNotNull($dMagazyn);
        $this->assertNotNull($dFinanse);

        $this->assertDatabaseHas('DepartamentUsers', [
            'ID_Users' => $user->id,
            'ID_Departament' => $dMagazyn->ID_Departament,
            'od' => '2026-05-10',
            'do' => '2026-05-20',
        ]);

        $this->assertDatabaseHas('DepartamentUsers', [
            'ID_Users' => $user->id,
            'ID_Departament' => $dMagazyn->ID_Departament,
            'od' => '2026-07-01',
            'do' => null,
        ]);

        $this->assertDatabaseHas('DepartamentUsers', [
            'ID_Users' => $user->id,
            'ID_Departament' => $dFinanse->ID_Departament,
            'od' => '2026-06-01',
            'do' => null,
        ]);
    }

    public function test_user_index_renders_expired_department_in_red()
    {
        $admin = $this->createAdmin();
        $user = User::factory()->create(['name' => 'Test Red Date', 'email' => 'red@test.pl']);
        $deptActive = Departament::create(['Nazwa' => 'Wydział Aktywny']);
        $deptExpired = Departament::create(['Nazwa' => 'Wydział Wygasły']);

        // Attach active department (do in future)
        $user->departments()->attach($deptActive->ID_Departament, [
            'od' => '2026-01-01',
            'do' => '2099-12-31',
        ]);

        // Attach expired department (do in past)
        $user->departments()->attach($deptExpired->ID_Departament, [
            'od' => '2020-01-01',
            'do' => '2020-12-31',
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));
        $response->assertStatus(200);

        // Check if red color styling (#ef4444) is present for expired department
        $response->assertSee('#ef4444');
        $response->assertSee('(do: 2020-12-31)');
    }

    public function test_mod_and_admin_user_status_filtering()
    {
        $dept = Departament::create(['Nazwa' => 'Wydział Testowy']);
        $mod = User::factory()->create(['role' => 'mod', 'is_active' => true]);
        $mod->departments()->attach($dept->ID_Departament);

        $activeUser = User::factory()->create(['name' => 'Aktywny ModUser', 'role' => 'user', 'is_active' => true]);
        $activeUser->departments()->attach($dept->ID_Departament);

        $inactiveUser = User::factory()->create(['name' => 'Nieaktywny ModUser', 'role' => 'user', 'is_active' => false]);
        $inactiveUser->departments()->attach($dept->ID_Departament);

        // 1. Filter active for mod
        $responseActive = $this->actingAs($mod)->get(route('users.index', ['status' => 'active']));
        $responseActive->assertStatus(200);
        $responseActive->assertSee('Aktywny ModUser');
        $responseActive->assertDontSee('Nieaktywny ModUser');

        // 2. Filter inactive for mod
        $responseInactive = $this->actingAs($mod)->get(route('users.index', ['status' => 'inactive']));
        $responseInactive->assertStatus(200);
        $responseInactive->assertSee('Nieaktywny ModUser');
        $responseInactive->assertDontSee('Aktywny ModUser');
    }

    public function test_user_can_view_own_profile_with_permissions()
    {
        $user = User::factory()->create(['name' => 'Profile User', 'role' => 'user', 'is_active' => true]);
        $modul = PModul::create(['nazwa' => 'Moduł Profilowy']);
        $op = POperacje::create(['nazwa' => 'Podgląd']);

        PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $op->id,
        ]);

        $response = $this->actingAs($user)->get(route('profile'));
        $response->assertStatus(200);
        $response->assertSee('Mój Profil');
        $response->assertSee('Profile User');
        $response->assertSee('Moduł Profilowy');
        $response->assertSee('Podgląd');
    }
}

