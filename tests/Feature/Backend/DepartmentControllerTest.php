<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Departament;

class DepartmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin()
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_admin_can_view_departments_index()
    {
        $admin = $this->createAdmin();
        $dept = Departament::create(['Nazwa' => 'Wydział Testowy']);

        $response = $this->actingAs($admin)->get(route('departments.index'));
        $response->assertStatus(200);
        $response->assertSee('Wydział Testowy');
    }

    public function test_departments_sorting()
    {
        $admin = $this->createAdmin();
        $deptB = Departament::create(['Nazwa' => 'Wydział B']);
        $deptA = Departament::create(['Nazwa' => 'Wydział A']);

        // 1. Sort by name asc
        $response = $this->actingAs($admin)->get(route('departments.index', ['sort_by' => 'Nazwa', 'sort_dir' => 'asc']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $posA = strpos($html, 'Wydział A');
        $posB = strpos($html, 'Wydział B');
        $this->assertTrue($posA < $posB, "Wydział A should appear before Wydział B in asc sorting");

        // 2. Sort by name desc
        $response = $this->actingAs($admin)->get(route('departments.index', ['sort_by' => 'Nazwa', 'sort_dir' => 'desc']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $posA = strpos($html, 'Wydział A');
        $posB = strpos($html, 'Wydział B');
        $this->assertTrue($posB < $posA, "Wydział B should appear before Wydział A in desc sorting");
    }
}
