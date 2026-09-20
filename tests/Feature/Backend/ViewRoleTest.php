<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Departament;
use App\Models\PModul;
use App\Models\POperacje;
use App\Models\PAccess;

class ViewRoleTest extends TestCase
{
    use RefreshDatabase;

    private function createViewUser()
    {
        return User::factory()->create(['role' => 'view', 'is_active' => true]);
    }

    private function createAdmin()
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_view_role_can_access_dashboard()
    {
        $viewer = $this->createViewUser();
        $response = $this->actingAs($viewer)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Podgląd (View)');
        $response->assertSee('Zarejestrowani Użytkownicy');
        $response->assertSee('Wydziały');
        $response->assertSee('Przypisane Uprawnienia');
    }

    public function test_view_role_can_view_users_and_their_departments()
    {
        $viewer = $this->createViewUser();
        $dept = Departament::create(['Nazwa' => 'Wydział Finansowy']);
        $user = User::factory()->create(['name' => 'Pracownik Testowy']);
        $user->departments()->attach($dept->ID_Departament, [
            'od' => '2026-01-01',
            'do' => '2026-12-31',
        ]);

        $response = $this->actingAs($viewer)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee('Pracownik Testowy');
        $response->assertSee('Wydział Finansowy');
        // Przycisk dodawania użytkownika oraz edycji/usunięcia nie powinien być widoczny dla roli view
        $response->assertDontSee('+ Dodaj użytkownika');
        $response->assertDontSee('✎ Edytuj');
        $response->assertSee('👁 Podgląd uprawnień');
    }

    public function test_view_role_cannot_modify_users()
    {
        $viewer = $this->createViewUser();
        $targetUser = User::factory()->create(['name' => 'Target User']);

        // Cannot access create form
        $response = $this->actingAs($viewer)->get(route('users.create'));
        $response->assertStatus(403);

        // Cannot store user
        $response = $this->actingAs($viewer)->post(route('users.store'), [
            'name' => 'Nowy User',
            'email' => 'nowy@test.pl',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);
        $response->assertStatus(403);

        // Cannot access edit form
        $response = $this->actingAs($viewer)->get(route('users.edit', $targetUser->id));
        $response->assertStatus(403);

        // Cannot update user
        $response = $this->actingAs($viewer)->put(route('users.update', $targetUser->id), [
            'name' => 'Zmieniony User',
            'email' => 'target@test.pl',
            'role' => 'user',
        ]);
        $response->assertStatus(403);

        // Cannot delete user
        $response = $this->actingAs($viewer)->delete(route('users.destroy', $targetUser->id));
        $response->assertStatus(403);
    }

    public function test_view_role_can_view_permissions_but_cannot_modify()
    {
        $viewer = $this->createViewUser();
        $user = User::factory()->create(['name' => 'User With Access']);
        $modul = PModul::create(['nazwa' => 'Moduł Magazyn']);
        $op = POperacje::create(['nazwa' => 'Odczyt']);
        $access = PAccess::create([
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $op->id,
            'valid_from' => '2026-01-01',
            'valid_to' => '2026-12-31',
        ]);

        // Can view access page with user permissions
        $response = $this->actingAs($viewer)->get(route('access.index', ['user_id' => $user->id]));
        $response->assertStatus(200);
        $response->assertSee('User With Access');
        $response->assertSee('Moduł Magazyn');
        $response->assertSee('Odczyt');
        $response->assertDontSee('+ Dodaj Uprawnienie');

        // Cannot create access
        $response = $this->actingAs($viewer)->get(route('access.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($viewer)->post(route('access.store'), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $op->id,
        ]);
        $response->assertStatus(403);

        // Cannot edit access
        $response = $this->actingAs($viewer)->get(route('access.edit', $access->id));
        $response->assertStatus(403);

        $response = $this->actingAs($viewer)->put(route('access.update', $access->id), [
            'user_id' => $user->id,
            'p_modul_id' => $modul->id,
            'p_operacje_id' => $op->id,
        ]);
        $response->assertStatus(403);

        // Cannot delete access
        $response = $this->actingAs($viewer)->delete(route('access.destroy', $access->id));
        $response->assertStatus(403);
    }

    public function test_view_role_can_view_departments_but_cannot_modify()
    {
        $viewer = $this->createViewUser();
        $dept = Departament::create(['Nazwa' => 'Wydział Sprzedaży']);

        // Can view departments list
        $response = $this->actingAs($viewer)->get(route('departments.index'));
        $response->assertStatus(200);
        $response->assertSee('Wydział Sprzedaży');
        $response->assertDontSee('+ Dodaj wydział');

        // Cannot create department
        $response = $this->actingAs($viewer)->post(route('departments.store'), [
            'Nazwa' => 'Nowy Wydział',
        ]);
        $response->assertStatus(403);

        // Cannot update department
        $response = $this->actingAs($viewer)->put(route('departments.update', $dept->ID_Departament), [
            'Nazwa' => 'Zmieniona Nazwa',
        ]);
        $response->assertStatus(403);

        // Cannot delete department
        $response = $this->actingAs($viewer)->delete(route('departments.destroy', $dept->ID_Departament));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_and_update_user_with_view_role()
    {
        $admin = $this->createAdmin();

        // 1. Create user with view role
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Nowy Przeglądający',
            'email' => 'viewer@test.pl',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'view',
        ]);
        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'viewer@test.pl',
            'role' => 'view',
        ]);

        $createdUser = User::where('email', 'viewer@test.pl')->first();

        // 2. Update user to view role
        $user = User::factory()->create(['role' => 'user']);
        $response = $this->actingAs($admin)->put(route('users.update', $user->id), [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'view',
        ]);
        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'role' => 'view',
        ]);
    }
}
