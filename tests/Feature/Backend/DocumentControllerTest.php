<?php

namespace Tests\Feature\Backend;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\PModul;
use App\Models\Document;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin()
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function createMod()
    {
        return User::factory()->create(['role' => 'mod', 'is_active' => true]);
    }

    private function createUser()
    {
        return User::factory()->create(['role' => 'user', 'is_active' => true]);
    }

    public function test_admin_can_manage_documents()
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $module = PModul::create(['nazwa' => 'Moduł Testowy']);

        // 1. Test view index
        $response = $this->actingAs($admin)->get(route('documents.index'));
        $response->assertStatus(200);
        $response->assertSee('Lista Dokumentów');

        // 2. Test view create
        $response = $this->actingAs($admin)->get(route('documents.create'));
        $response->assertStatus(200);

        // 3. Test store document
        $file = UploadedFile::fake()->create('instrukcja.pdf', 500); // 500 KB
        $response = $this->actingAs($admin)->post(route('documents.store'), [
            'nazwa' => 'Instrukcja BHP',
            'p_modul_id' => $module->id,
            'file' => $file,
        ]);

        $response->assertRedirect(route('documents.index'));
        $response->assertSessionHas('success');

        $document = Document::first();
        $this->assertNotNull($document);
        $this->assertEquals('Instrukcja BHP', $document->nazwa);
        $this->assertEquals($module->id, $document->p_modul_id);
        $this->assertEquals('instrukcja.pdf', $document->original_filename);

        Storage::disk('local')->assertExists($document->file_path);

        // 4. Test download document
        $response = $this->actingAs($admin)->get(route('documents.download', $document));
        $response->assertStatus(200);
        $response->assertHeader('Content-Disposition', 'attachment; filename="Instrukcja BHP.pdf"');

        // 5. Test view edit
        $response = $this->actingAs($admin)->get(route('documents.edit', $document));
        $response->assertStatus(200);
        $response->assertSee('Edytuj dokument');

        // 6. Test update document (without file change)
        $response = $this->actingAs($admin)->put(route('documents.update', $document), [
            'nazwa' => 'Zaktualizowana Instrukcja BHP',
            'p_modul_id' => $module->id,
        ]);

        $response->assertRedirect(route('documents.index'));
        $document->refresh();
        $this->assertEquals('Zaktualizowana Instrukcja BHP', $document->nazwa);

        // 7. Test delete document
        $filePath = $document->file_path;
        $response = $this->actingAs($admin)->delete(route('documents.destroy', $document));
        $response->assertRedirect(route('documents.index'));
        
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($filePath);
    }

    public function test_mod_and_user_can_only_view_and_download()
    {
        Storage::fake('local');
        $mod = $this->createMod();
        $user = $this->createUser();
        $module = PModul::create(['nazwa' => 'Moduł Testowy']);

        // Create a document using factory or manual insert
        $file = UploadedFile::fake()->create('instrukcja.pdf', 500);
        $path = $file->store('documents', 'local');
        $document = Document::create([
            'nazwa' => 'Instrukcja Użytkownika',
            'file_path' => $path,
            'original_filename' => 'instrukcja.pdf',
            'p_modul_id' => $module->id,
        ]);

        // --- Test Moderator ---
        // Can view
        $response = $this->actingAs($mod)->get(route('documents.index'));
        $response->assertStatus(200);
        $response->assertSee('Instrukcja Użytkownika');

        // Can download
        $response = $this->actingAs($mod)->get(route('documents.download', $document));
        $response->assertStatus(200);

        // Cannot create
        $response = $this->actingAs($mod)->get(route('documents.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($mod)->post(route('documents.store'), []);
        $response->assertStatus(403);

        // Cannot edit/delete
        $response = $this->actingAs($mod)->get(route('documents.edit', $document));
        $response->assertStatus(403);

        $response = $this->actingAs($mod)->put(route('documents.update', $document), []);
        $response->assertStatus(403);

        $response = $this->actingAs($mod)->delete(route('documents.destroy', $document));
        $response->assertStatus(403);

        // --- Test Regular User ---
        // Can view
        $response = $this->actingAs($user)->get(route('documents.index'));
        $response->assertStatus(200);
        $response->assertSee('Instrukcja Użytkownika');

        // Can download
        $response = $this->actingAs($user)->get(route('documents.download', $document));
        $response->assertStatus(200);

        // Cannot manage
        $response = $this->actingAs($user)->get(route('documents.create'));
        $response->assertStatus(403);

        $response = $this->actingAs($user)->post(route('documents.store'), []);
        $response->assertStatus(403);

        $response = $this->actingAs($user)->delete(route('documents.destroy', $document));
        $response->assertStatus(403);
    }

    public function test_cannot_delete_module_with_assigned_documents()
    {
        Storage::fake('local');
        $admin = $this->createAdmin();
        $module = PModul::create(['nazwa' => 'Moduł z Dokumentem']);
        
        $document = Document::create([
            'nazwa' => 'Dokument Modułowy',
            'file_path' => 'documents/test.pdf',
            'original_filename' => 'test.pdf',
            'p_modul_id' => $module->id,
        ]);

        // Attempt to delete module
        $response = $this->actingAs($admin)->delete(route('modules.destroy', $module->id));
        
        $response->assertRedirect(route('modules.index'));
        $response->assertSessionHas('error');
        
        // Assert the module still exists
        $this->assertDatabaseHas('P_modul', ['id' => $module->id]);
    }
}
