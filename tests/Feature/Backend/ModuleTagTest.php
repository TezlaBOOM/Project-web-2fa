<?php

namespace Tests\Feature\Backend;

use App\Models\PAccess;
use App\Models\PModul;
use App\Models\POperacje;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_edit_module_with_tags(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('modules.store'), [
            'nazwa' => 'System ERP',
            'tags' => 'Finanse, ERP, erp, Krytyczny',
        ]);

        $response->assertRedirect(route('modules.index'));
        $module = PModul::where('nazwa', 'System ERP')->first();
        $this->assertNotNull($module);
        $this->assertCount(3, $module->tags);
        $this->assertDatabaseHas('tags', ['name' => 'Finanse']);
        $this->assertDatabaseHas('tags', ['name' => 'ERP']);
        $this->assertDatabaseHas('tags', ['name' => 'Krytyczny']);

        // Edit tags (remove Krytyczny, add Księgowość)
        $updateResponse = $this->actingAs($admin)->put(route('modules.update', $module->id), [
            'nazwa' => 'System ERP',
            'tags' => 'ERP, Księgowość',
        ]);
        $updateResponse->assertRedirect(route('modules.index'));

        $module->refresh();
        $this->assertEqualsCanonicalizing(['ERP', 'Księgowość'], $module->tags->pluck('name')->all());
    }

    public function test_modules_index_filters_by_tag_and_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $modA = PModul::create(['nazwa' => 'Moduł Płace']);
        $modA->syncTagsFromString('Kadry, Wewnętrzny');

        $modB = PModul::create(['nazwa' => 'Moduł Magazyn']);
        $modB->syncTagsFromString('Logistyka');

        $kadryTag = Tag::where('name', 'Kadry')->first();

        $byTag = $this->actingAs($admin)->get(route('modules.index', ['tag_id' => $kadryTag->id]));
        $byTag->assertStatus(200);
        $byTag->assertSee('Moduł Płace');
        $byTag->assertSee('#Kadry');
        $byTag->assertDontSee('Moduł Magazyn');

        $bySearch = $this->actingAs($admin)->get(route('modules.index', ['search' => 'Logistyka']));
        $bySearch->assertStatus(200);
        $bySearch->assertSee('Moduł Magazyn');
        $bySearch->assertDontSee('Moduł Płace');
    }

    public function test_access_index_filters_users_and_accesses_by_module_tag(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $mod1 = PModul::create(['nazwa' => 'Portal HR']);
        $mod1->syncTagsFromString('HRSystem');

        $mod2 = PModul::create(['nazwa' => 'Portal CRM']);
        $mod2->syncTagsFromString('SalesSystem');

        $op = POperacje::create(['nazwa' => 'Odczyt']);

        $user1 = User::factory()->create(['name' => 'Kadrowa Anna', 'role' => 'user', 'is_active' => true]);
        PAccess::create(['user_id' => $user1->id, 'p_modul_id' => $mod1->id, 'p_operacje_id' => $op->id]);

        $user2 = User::factory()->create(['name' => 'Handlowiec Piotr', 'role' => 'user', 'is_active' => true]);
        PAccess::create(['user_id' => $user2->id, 'p_modul_id' => $mod2->id, 'p_operacje_id' => $op->id]);

        $hrTag = Tag::where('name', 'HRSystem')->first();

        $response = $this->actingAs($admin)->get(route('access.index', ['modul_tag_id' => $hrTag->id, 'user_id' => $user1->id]));
        $response->assertStatus(200);
        $response->assertSee('Kadrowa Anna');
        $response->assertSee('Portal HR');
        $response->assertSee('#HRSystem');
        $response->assertDontSee('Handlowiec Piotr');
    }

    public function test_non_admin_roles_cannot_modify_modules_or_tags(): void
    {
        $mod = PModul::create(['nazwa' => 'Moduł Chroniony']);

        foreach (['view', 'mod', 'support', 'user'] as $role) {
            $actor = User::factory()->create(['role' => $role, 'is_active' => true]);

            $this->actingAs($actor)->post(route('modules.store'), [
                'nazwa' => 'Nieuprawniony',
                'tags' => 'TagX',
            ])->assertStatus(403);

            $this->actingAs($actor)->put(route('modules.update', $mod->id), [
                'nazwa' => 'Zmieniony',
                'tags' => 'TagY',
            ])->assertStatus(403);

            $this->actingAs($actor)->delete(route('modules.destroy', $mod->id))
                ->assertStatus(403);
        }
    }
}
