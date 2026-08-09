<?php

namespace Tests\Feature\Backend;

use Tests\TestCase;
use App\Models\User;
use App\Models\Departament;
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
        $this->assertStringContainsString('imię_i_nazwisko,email,rola,status,wydziały', $response->streamedContent());
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
        $this->assertStringContainsString('"Janusz Kowal",janusz@test.pl,user,aktywny,"Dzial Marketingu"', $content);
    }

    public function test_csv_import()
    {
        $admin = $this->createAdmin();

        $csvData = "imię_i_nazwisko,email,rola,status,wydziały\n" .
                   "Zofia Importowana,zofia@import.pl,mod,aktywny,\"Dział Testowy, Nowy Dział\"\n" .
                   "Stefan Nieaktywny,stefan@import.pl,user,nieaktywny,\"\"\n";

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

        $csvData = "id,imię_i_nazwisko,email,rola,status,wydziały\n" .
                   "{$user->id},Nowy Nazwisko,stary@test.pl,mod,nieaktywny,\"Wydział Nowy\"\n";

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
}
