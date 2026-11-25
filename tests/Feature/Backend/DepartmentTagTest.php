<?php

namespace Tests\Feature\Backend;

use App\Models\Departament;
use App\Models\PAccess;
use App\Models\PModul;
use App\Models\POperacje;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_edit_department_with_tags(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->post(route('departments.store'), [
            'Nazwa' => 'Wydział Techniczny',
            'Description' => 'Dział wsparcia technicznego',
            'tags' => 'IT, Centrala, it, Sprzęt',
        ]);

        $response->assertRedirect(route('departments.index'));
        $dept = Departament::where('Nazwa', 'Wydział Techniczny')->first();
        $this->assertNotNull($dept);
        $this->assertCount(3, $dept->tags);
        $this->assertDatabaseHas('tags', ['name' => 'IT']);
        $this->assertDatabaseHas('tags', ['name' => 'Centrala']);
        $this->assertDatabaseHas('tags', ['name' => 'Sprzęt']);

        // Edit tags (remove Sprzęt, add Sieć)
        $updateResponse = $this->actingAs($admin)->put(route('departments.update', $dept->ID_Departament), [
            'Nazwa' => 'Wydział Techniczny',
            'Description' => 'Dział wsparcia technicznego',
            'tags' => 'IT, Sieć',
        ]);
        $updateResponse->assertRedirect(route('departments.index'));

        $dept->refresh();
        $this->assertEqualsCanonicalizing(['IT', 'Sieć'], $dept->tags->pluck('name')->all());
    }

    public function test_departments_index_filters_by_tag_and_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $deptA = Departament::create(['Nazwa' => 'Wydział Alpha']);
        $deptA->syncTagsFromString('Finanse, Centrala');

        $deptB = Departament::create(['Nazwa' => 'Wydział Beta']);
        $deptB->syncTagsFromString('Produkcja');

        $finanseTag = Tag::where('name', 'Finanse')->first();

        $byTag = $this->actingAs($admin)->get(route('departments.index', ['tag_id' => $finanseTag->id]));
        $byTag->assertStatus(200);
        $byTag->assertSee('Wydział Alpha');
        $byTag->assertDontSee('Wydział Beta');

        $bySearch = $this->actingAs($admin)->get(route('departments.index', ['search' => 'Produkcja']));
        $bySearch->assertStatus(200);
        $bySearch->assertSee('Wydział Beta');
        $bySearch->assertDontSee('Wydział Alpha');
    }

    public function test_access_index_filters_users_by_department_tag(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $dept1 = Departament::create(['Nazwa' => 'Dział Rozwoju']);
        $dept1->syncTagsFromString('DevTeam');

        $dept2 = Departament::create(['Nazwa' => 'Dział Prawny']);
        $dept2->syncTagsFromString('LegalTeam');

        $user1 = User::factory()->create(['name' => 'Programista Marek', 'role' => 'user']);
        $user1->departments()->attach($dept1->ID_Departament);

        $user2 = User::factory()->create(['name' => 'Prawnik Ewa', 'role' => 'user']);
        $user2->departments()->attach($dept2->ID_Departament);

        $devTag = Tag::where('name', 'DevTeam')->first();

        $response = $this->actingAs($admin)->get(route('access.index', ['dept_tag_id' => $devTag->id]));
        $response->assertStatus(200);
        $response->assertSee('Programista Marek');
        $response->assertDontSee('Prawnik Ewa');
    }

    public function test_non_admin_roles_cannot_modify_departments_or_tags(): void
    {
        $dept = Departament::create(['Nazwa' => 'Wydział Chroniony']);

        foreach (['view', 'mod', 'support', 'user'] as $role) {
            $actor = User::factory()->create(['role' => $role, 'is_active' => true]);

            $this->actingAs($actor)->post(route('departments.store'), [
                'Nazwa' => 'Nieuprawniony',
                'tags' => 'TagX',
            ])->assertStatus(403);

            $this->actingAs($actor)->put(route('departments.update', $dept->ID_Departament), [
                'Nazwa' => 'Zmieniony',
                'tags' => 'TagY',
            ])->assertStatus(403);

            $this->actingAs($actor)->delete(route('departments.destroy', $dept->ID_Departament))
                ->assertStatus(403);
        }
    }
}
