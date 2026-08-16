<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\POperacje;

class POperacjeControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin()
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_admin_can_search_operations()
    {
        $admin = $this->createAdmin();

        POperacje::create(['nazwa' => 'Odczyt Danych']);
        POperacje::create(['nazwa' => 'Edycja Profilu']);
        POperacje::create(['nazwa' => 'Kasowanie']);

        $response = $this->actingAs($admin)->get(route('operations.index', ['search' => 'Profil']));
        $response->assertStatus(200);
        $response->assertSee('Edycja Profilu');
        $response->assertDontSee('Odczyt Danych');
        $response->assertDontSee('Kasowanie');
    }

    public function test_operations_sorting()
    {
        $admin = $this->createAdmin();
        $opB = POperacje::create(['nazwa' => 'Operacja B']);
        $opA = POperacje::create(['nazwa' => 'Operacja A']);

        // 1. Sort by name asc
        $response = $this->actingAs($admin)->get(route('operations.index', ['sort_by' => 'nazwa', 'sort_dir' => 'asc']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $posA = strpos($html, 'Operacja A');
        $posB = strpos($html, 'Operacja B');
        $this->assertTrue($posA < $posB, "Operacja A should appear before Operacja B in asc sorting");

        // 2. Sort by name desc
        $response = $this->actingAs($admin)->get(route('operations.index', ['sort_by' => 'nazwa', 'sort_dir' => 'desc']));
        $response->assertStatus(200);
        $html = $response->getContent();
        $posA = strpos($html, 'Operacja A');
        $posB = strpos($html, 'Operacja B');
        $this->assertTrue($posB < $posA, "Operacja B should appear before Operacja A in desc sorting");
    }
}
