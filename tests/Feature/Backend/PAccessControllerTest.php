<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\PModul;
use App\Models\POperacje;
use App\Models\PAccess;
use App\Models\Departament;

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
        $response->assertRedirect(route('access.index'));
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

        $response->assertSessionHasErrors(['error' => 'Ten użytkownik posiada już takie uprawnienie.']);
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
}
