<?php

namespace Tests\Feature\Backend;

use App\Models\Departament;
use App\Models\Document;
use App\Models\PAccess;
use App\Models\PModul;
use App\Models\POperacje;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportRoleTest extends TestCase
{
    use RefreshDatabase;

    private function setupSupportScenario(): array
    {
        $deptAllowed = Departament::create(['Nazwa' => 'Wydział Wspierany']);
        $deptOther = Departament::create(['Nazwa' => 'Wydział Obcy']);

        $modA = PModul::create(['nazwa' => 'System A']);
        $modB = PModul::create(['nazwa' => 'System B']);
        $opRead = POperacje::create(['nazwa' => 'Odczyt']);
        $opWrite = POperacje::create(['nazwa' => 'Edycja']);

        $support = User::factory()->create([
            'name' => 'Support Agent',
            'email' => 'support.agent@test.pl',
            'role' => 'support',
            'is_active' => true,
        ]);
        $support->departments()->attach($deptAllowed->ID_Departament, [
            'od' => now()->subDays(10)->toDateString(),
            'do' => now()->addDays(30)->toDateString(),
        ]);

        // Support ma parę (modA, opRead) oraz (modB, opWrite) - NIE ma (modA, opWrite) ani (modB, opRead)
        PAccess::create([
            'user_id' => $support->id,
            'p_modul_id' => $modA->id,
            'p_operacje_id' => $opRead->id,
        ]);
        PAccess::create([
            'user_id' => $support->id,
            'p_modul_id' => $modB->id,
            'p_operacje_id' => $opWrite->id,
        ]);

        $targetUser = User::factory()->create([
            'name' => 'Pracownik Wspierany',
            'email' => 'pracownik@test.pl',
            'role' => 'user',
            'is_active' => true,
        ]);
        $targetUser->departments()->attach($deptAllowed->ID_Departament);

        $otherUser = User::factory()->create([
            'name' => 'Pracownik Obcy',
            'email' => 'obcy@test.pl',
            'role' => 'user',
            'is_active' => true,
        ]);
        $otherUser->departments()->attach($deptOther->ID_Departament);

        return compact(
            'support', 'targetUser', 'otherUser',
            'deptAllowed', 'deptOther',
            'modA', 'modB', 'opRead', 'opWrite'
        );
    }

    public function test_seeder_creates_support_account(): void
    {
        $this->seed(DatabaseSeeder::class);

        $support = User::where('email', 'support@admin.com')->first();
        $this->assertNotNull($support);
        $this->assertSame('support', $support->role);
        $this->assertTrue(Hash::check('support', $support->password));
    }

    public function test_admin_can_create_and_update_user_with_support_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $createRes = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Nowy Support',
            'email' => 'nowy.support@test.pl',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'support',
        ]);
        $createRes->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'nowy.support@test.pl',
            'role' => 'support',
        ]);

        $regular = User::factory()->create(['role' => 'user']);
        $updateRes = $this->actingAs($admin)->put(route('users.update', $regular->id), [
            'name' => $regular->name,
            'email' => $regular->email,
            'role' => 'support',
        ]);
        $updateRes->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $regular->id,
            'role' => 'support',
        ]);
    }

    public function test_support_can_access_dashboard_documents_and_settings_only_within_limits(): void
    {
        $data = $this->setupSupportScenario();
        $support = $data['support'];

        $dash = $this->actingAs($support)->get(route('dashboard'));
        $dash->assertStatus(200);
        $dash->assertSee('Wsparcie (Support)');
        $dash->assertSee(route('access.index'));

        Storage::fake('local');
        Storage::disk('local')->put('documents/test.pdf', 'pdf-content');
        $doc = Document::create([
            'nazwa' => 'Instrukcja',
            'file_path' => 'documents/test.pdf',
            'original_filename' => 'test.pdf',
            'p_modul_id' => $data['modA']->id,
        ]);

        $this->actingAs($support)->get(route('documents.index'))->assertStatus(200)->assertSee('Instrukcja');
        $this->actingAs($support)->get(route('documents.download', $doc))->assertStatus(200);
        $this->actingAs($support)->get(route('documents.create'))->assertStatus(403);

        $this->actingAs($support)->get(route('settings'))->assertStatus(200);
        $this->actingAs($support)->get(route('settings.logon'))->assertStatus(403);
        $this->actingAs($support)->get(route('settings.database.export'))->assertStatus(403);
    }

    public function test_support_cannot_modify_users_departments_modules_operations_or_csv(): void
    {
        $data = $this->setupSupportScenario();
        $support = $data['support'];
        $targetUser = $data['targetUser'];

        // User modification forbidden
        $this->actingAs($support)->get(route('users.create'))->assertStatus(403);
        $this->actingAs($support)->post(route('users.store'), [
            'name' => 'Test',
            'email' => 'x@test.pl',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ])->assertStatus(403);
        $this->actingAs($support)->get(route('users.edit', $targetUser->id))->assertStatus(403);
        $this->actingAs($support)->put(route('users.update', $targetUser->id), [
            'name' => 'Zmienione',
            'email' => $targetUser->email,
            'role' => 'user',
        ])->assertStatus(403);
        $this->actingAs($support)->delete(route('users.destroy', $targetUser->id))->assertStatus(403);
        $this->actingAs($support)->get(route('users.csv'))->assertStatus(403);

        // Departments, modules, operations, access CSV forbidden
        $this->actingAs($support)->post(route('departments.store'), ['Nazwa' => 'X'])->assertStatus(403);
        $this->actingAs($support)->get(route('modules.index'))->assertStatus(403);
        $this->actingAs($support)->get(route('operations.index'))->assertStatus(403);
        $this->actingAs($support)->get(route('access.csv'))->assertStatus(403);
    }

    public function test_support_sees_only_department_users_and_only_assigned_permission_pairs(): void
    {
        $data = $this->setupSupportScenario();
        $support = $data['support'];
        $targetUser = $data['targetUser'];
        $otherUser = $data['otherUser'];

        // Allowed pair on targetUser
        PAccess::create([
            'user_id' => $targetUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opRead']->id,
        ]);

        // Cross-mixed / disallowed pair on targetUser (must be hidden from support)
        $modHidden = PModul::create(['nazwa' => 'Ukryty Moduł']);
        $opHidden = POperacje::create(['nazwa' => 'Ukryta Operacja']);
        PAccess::create([
            'user_id' => $targetUser->id,
            'p_modul_id' => $modHidden->id,
            'p_operacje_id' => $opHidden->id,
        ]);
        PAccess::create([
            'user_id' => $targetUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opWrite']->id, // Support ma modA+opRead, nie modA+opWrite
        ]);

        // Users index shows only targetUser (and support), hides otherUser and edit buttons
        $usersRes = $this->actingAs($support)->get(route('users.index'));
        $usersRes->assertStatus(200);
        $usersRes->assertSee('Pracownik Wspierany');
        $usersRes->assertDontSee('Pracownik Obcy');
        $usersRes->assertDontSee('+ Dodaj użytkownika');
        $usersRes->assertDontSee('✎ Edytuj');

        // Access index shows only targetUser and only (modA, opRead)
        $accessRes = $this->actingAs($support)->get(route('access.index', ['user_id' => $targetUser->id]));
        $accessRes->assertStatus(200);
        $accessRes->assertSee('Pracownik Wspierany');
        $accessRes->assertDontSee('Pracownik Obcy');
        $accessRes->assertSee('System A');
        $accessRes->assertSee('Odczyt');
        $accessRes->assertDontSee('Ukryty Moduł');
        $accessRes->assertDontSee('Edycja');

        // users.permissions also scoped
        $permsRes = $this->actingAs($support)->get(route('users.permissions', $targetUser->id));
        $permsRes->assertStatus(200);
        $permsRes->assertSee('System A');
        $permsRes->assertDontSee('Ukryty Moduł');
        $permsRes->assertDontSee('Edycja');

        // Cannot open otherUser access or permissions
        $this->actingAs($support)->get(route('access.index', ['user_id' => $otherUser->id]))->assertStatus(403);
        $this->actingAs($support)->get(route('users.permissions', $otherUser->id))->assertStatus(403);
    }

    public function test_support_can_create_edit_delete_view_history_and_export_pdf_for_allowed_scope(): void
    {
        $data = $this->setupSupportScenario();
        $support = $data['support'];
        $targetUser = $data['targetUser'];

        // Create form accessible for targetUser
        $this->actingAs($support)->get(route('access.create', ['user_id' => $targetUser->id]))
            ->assertStatus(200)
            ->assertSee('System A')
            ->assertSee('System B');

        // Store allowed pair (modA, opRead)
        $storeRes = $this->actingAs($support)->post(route('access.store'), [
            'user_id' => $targetUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opRead']->id,
            'valid_from' => '2026-01-01',
            'valid_to' => '2026-12-31',
            'login' => 'sup_created',
        ]);
        $storeRes->assertRedirect(route('access.index', ['user_id' => $targetUser->id]));

        $access = PAccess::where('user_id', $targetUser->id)
            ->where('p_modul_id', $data['modA']->id)
            ->where('p_operacje_id', $data['opRead']->id)
            ->first();
        $this->assertNotNull($access);

        // Edit & update to another allowed pair (modB, opWrite)
        $this->actingAs($support)->get(route('access.edit', $access->id))->assertStatus(200);
        $updateRes = $this->actingAs($support)->put(route('access.update', $access->id), [
            'user_id' => $targetUser->id,
            'p_modul_id' => $data['modB']->id,
            'p_operacje_id' => $data['opWrite']->id,
            'valid_from' => '2026-02-01',
            'valid_to' => '2026-11-30',
            'login' => 'sup_updated',
        ]);
        $updateRes->assertRedirect(route('access.index', ['user_id' => $targetUser->id]));
        $this->assertDatabaseHas('P_access', [
            'id' => $access->id,
            'p_modul_id' => $data['modB']->id,
            'p_operacje_id' => $data['opWrite']->id,
            'login' => 'sup_updated',
        ]);

        // History endpoint allowed for managed pair
        $historyRes = $this->actingAs($support)->get(route('access.history', [
            'user_id' => $targetUser->id,
            'p_modul_id' => $data['modB']->id,
            'p_operacje_id' => $data['opWrite']->id,
            'access_id' => $access->id,
        ]));
        $historyRes->assertStatus(200)->assertJson(['success' => true]);

        // PDF export allowed
        $pdfRes = $this->actingAs($support)->get(route('access.pdf'));
        $pdfRes->assertStatus(200);
        $this->assertStringStartsWith('%PDF-', $pdfRes->getContent());

        // Delete allowed
        $delRes = $this->actingAs($support)->delete(route('access.destroy', $access->id));
        $delRes->assertRedirect(route('access.index', ['user_id' => $targetUser->id]));
        $this->assertDatabaseMissing('P_access', ['id' => $access->id]);
    }

    public function test_support_cannot_exceed_department_or_exact_permission_pair_scope(): void
    {
        $data = $this->setupSupportScenario();
        $support = $data['support'];
        $targetUser = $data['targetUser'];
        $otherUser = $data['otherUser'];

        // 1. Cannot grant permission to user in another department
        $this->actingAs($support)->post(route('access.store'), [
            'user_id' => $otherUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opRead']->id,
        ])->assertStatus(403);

        // 2. Cannot cross-mix pairs: support has (modA, opRead) & (modB, opWrite), NOT (modA, opWrite)
        $this->actingAs($support)->post(route('access.store'), [
            'user_id' => $targetUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opWrite']->id,
        ])->assertStatus(403);

        // 3. Cannot edit/delete existing permission outside support's exact pair or outside department
        $disallowedAccess = PAccess::create([
            'user_id' => $targetUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opWrite']->id,
        ]);
        $this->actingAs($support)->get(route('access.edit', $disallowedAccess->id))->assertStatus(403);
        $this->actingAs($support)->put(route('access.update', $disallowedAccess->id), [
            'user_id' => $targetUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opRead']->id,
        ])->assertStatus(403);
        $this->actingAs($support)->delete(route('access.destroy', $disallowedAccess->id))->assertStatus(403);

        $otherUserAccess = PAccess::create([
            'user_id' => $otherUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opRead']->id,
        ]);
        $this->actingAs($support)->delete(route('access.destroy', $otherUserAccess->id))->assertStatus(403);

        // 4. Cannot escalate via update from an allowed access to a disallowed pair/user
        $allowedAccess = PAccess::create([
            'user_id' => $targetUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opRead']->id,
        ]);
        $this->actingAs($support)->put(route('access.update', $allowedAccess->id), [
            'user_id' => $targetUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opWrite']->id,
        ])->assertStatus(403);
        $this->actingAs($support)->put(route('access.update', $allowedAccess->id), [
            'user_id' => $otherUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opRead']->id,
        ])->assertStatus(403);

        // 5. History endpoint forbidden for disallowed pair or user
        $this->actingAs($support)->get(route('access.history', [
            'user_id' => $targetUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opWrite']->id,
        ]))->assertStatus(403);
        $this->actingAs($support)->get(route('access.history', [
            'user_id' => $otherUser->id,
            'p_modul_id' => $data['modA']->id,
            'p_operacje_id' => $data['opRead']->id,
        ]))->assertStatus(403);
    }

    public function test_support_with_expired_access_or_expired_department_cannot_manage(): void
    {
        $dept = Departament::create(['Nazwa' => 'Wydział Wygasły']);
        $mod = PModul::create(['nazwa' => 'System X']);
        $op = POperacje::create(['nazwa' => 'Odczyt']);

        $support = User::factory()->create(['role' => 'support', 'is_active' => true]);
        $support->departments()->attach($dept->ID_Departament, [
            'od' => now()->subDays(30)->toDateString(),
            'do' => now()->addDays(10)->toDateString(),
        ]);

        // Expired PAccess on support
        PAccess::create([
            'user_id' => $support->id,
            'p_modul_id' => $mod->id,
            'p_operacje_id' => $op->id,
            'valid_from' => now()->subDays(20)->toDateString(),
            'valid_to' => now()->subDay()->toDateString(),
        ]);

        $targetUser = User::factory()->create(['role' => 'user']);
        $targetUser->departments()->attach($dept->ID_Departament);

        $this->actingAs($support)->post(route('access.store'), [
            'user_id' => $targetUser->id,
            'p_modul_id' => $mod->id,
            'p_operacje_id' => $op->id,
        ])->assertStatus(403);

        // Now give active access, but expire department membership
        PAccess::create([
            'user_id' => $support->id,
            'p_modul_id' => $mod->id,
            'p_operacje_id' => $op->id,
        ]);
        $support->departments()->updateExistingPivot($dept->ID_Departament, [
            'do' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($support)->post(route('access.store'), [
            'user_id' => $targetUser->id,
            'p_modul_id' => $mod->id,
            'p_operacje_id' => $op->id,
        ])->assertStatus(403);
    }
}
