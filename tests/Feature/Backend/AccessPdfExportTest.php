<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Departament;
use App\Models\PModul;
use App\Models\POperacje;
use App\Models\PAccess;

class AccessPdfExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_index_contains_pdf_download_link_with_filters(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $dept = Departament::create(['Nazwa' => 'Wydział IT']);

        $response = $this->actingAs($admin)->get(route('access.index', [
            'search' => 'Kowalski',
            'dept_id' => $dept->ID_Departament,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Pobierz PDF');
        $response->assertSee(route('access.pdf', [
            'search' => 'Kowalski',
            'dept_id' => $dept->ID_Departament,
        ]));
    }

    public function test_pdf_export_returns_valid_pdf_with_filtered_users_permissions_and_dates(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Admin Test', 'is_active' => true]);
        $deptIt = Departament::create(['Nazwa' => 'Wydział Informatyki']);
        $deptHr = Departament::create(['Nazwa' => 'Wydział Kadr']);

        $userMatched = User::factory()->create([
            'name' => 'Jan Kowalski',
            'email' => 'jan.kowalski@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);
        $userMatched->departments()->attach($deptIt->ID_Departament, [
            'od' => '2026-01-01',
            'do' => '2026-12-31',
        ]);

        $userOther = User::factory()->create([
            'name' => 'Anna Nowak',
            'email' => 'anna.nowak@example.com',
            'role' => 'user',
            'is_active' => true,
        ]);
        $userOther->departments()->attach($deptHr->ID_Departament, [
            'od' => '2026-01-01',
            'do' => '2026-12-31',
        ]);

        $parentMod = PModul::create(['nazwa' => 'System ERP']);
        $childMod = PModul::create(['nazwa' => 'Faktury', 'parent_id' => $parentMod->id]);
        $operation = POperacje::create(['nazwa' => 'Edycja']);

        PAccess::create([
            'user_id' => $userMatched->id,
            'p_modul_id' => $childMod->id,
            'p_operacje_id' => $operation->id,
            'valid_from' => '2026-02-01',
            'valid_to' => '2026-11-30',
            'login' => 'jkowalski_erp',
            'uwagi' => 'Uprawnienie okresowe',
        ]);

        $response = $this->actingAs($admin)->get(route('access.pdf', [
            'search' => 'Kowalski',
            'dept_id' => $deptIt->ID_Departament,
            'modul_id' => $parentMod->id,
            'module_from' => '2026-01-01',
            'module_to' => '2026-12-31',
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_regular_user_cannot_export_access_pdf(): void
    {
        $user = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $response = $this->actingAs($user)->get(route('access.pdf'));

        $response->assertStatus(403);
    }
}
