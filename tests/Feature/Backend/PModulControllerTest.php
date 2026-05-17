<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\PModul;

class PModulControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin()
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function createUser()
    {
        return User::factory()->create(['role' => 'user', 'is_active' => true]);
    }

    public function test_admin_can_manage_modules()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('modules.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->post(route('modules.store'), [
            'nazwa' => 'Test Module',
        ]);
        $response->assertRedirect(route('modules.index'));
        $this->assertDatabaseHas('P_modul', ['nazwa' => 'Test Module']);

        $module = PModul::first();

        $response = $this->actingAs($admin)->put(route('modules.update', $module->id), [
            'nazwa' => 'Updated Module',
        ]);
        $response->assertRedirect(route('modules.index'));
        $this->assertDatabaseHas('P_modul', ['nazwa' => 'Updated Module']);

        $response = $this->actingAs($admin)->delete(route('modules.destroy', $module->id));
        $response->assertRedirect(route('modules.index'));
        $this->assertDatabaseMissing('P_modul', ['id' => $module->id]);
    }

    public function test_non_admin_cannot_access_modules()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get(route('modules.index'));
        $response->assertStatus(403);
    }

    public function test_module_cannot_be_its_own_parent()
    {
        $admin = $this->createAdmin();
        $module = PModul::create(['nazwa' => 'Root']);

        $response = $this->actingAs($admin)->put(route('modules.update', $module->id), [
            'nazwa' => 'Root Updated',
            'parent_id' => $module->id,
        ]);

        $response->assertSessionHasErrors(['parent_id' => 'Moduł nie może być swoim własnym rodzicem.']);
    }

    public function test_module_nesting_is_limited_to_5_levels()
    {
        $admin = $this->createAdmin();
        
        $m1 = PModul::create(['nazwa' => 'Level 1']);
        $m2 = PModul::create(['nazwa' => 'Level 2', 'parent_id' => $m1->id]);
        $m3 = PModul::create(['nazwa' => 'Level 3', 'parent_id' => $m2->id]);
        $m4 = PModul::create(['nazwa' => 'Level 4', 'parent_id' => $m3->id]);
        $m5 = PModul::create(['nazwa' => 'Level 5', 'parent_id' => $m4->id]); // Depth 4 (since 0 is root)

        // Try creating Level 6
        $response = $this->actingAs($admin)->post(route('modules.store'), [
            'nazwa' => 'Level 6',
            'parent_id' => $m5->id,
        ]);

        $response->assertSessionHasErrors(['parent_id' => 'Zbyt głębokie zagnieżdżenie. Maksymalny limit to 5 poziomów.']);
    }
}
