<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Departament;

class UserVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_mod_sees_only_users_in_their_department()
    {
        $mod = User::factory()->create(['role' => 'mod', 'is_active' => true]);
        $dept = Departament::create(['Nazwa' => 'Mod Dept']);
        $mod->departments()->attach($dept->ID_Departament);

        $userInDept = User::factory()->create(['name' => 'User In Dept', 'role' => 'user']);
        $userInDept->departments()->attach($dept->ID_Departament);

        $userOutDept = User::factory()->create(['name' => 'User Out Dept', 'role' => 'user']);
        $otherDept = Departament::create(['Nazwa' => 'Other Dept']);
        $userOutDept->departments()->attach($otherDept->ID_Departament);

        $response = $this->actingAs($mod)->get(route('users.index'));
        
        $response->assertStatus(200);
        $response->assertSee('User In Dept');
        $response->assertDontSee('User Out Dept');
    }

    public function test_admin_sees_all_users()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        
        $user1 = User::factory()->create(['name' => 'User One']);
        $user2 = User::factory()->create(['name' => 'User Two']);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertStatus(200);
        $response->assertSee('User One');
        $response->assertSee('User Two');
    }
}
