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
}
