<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Document;
use App\Models\PModul;
use App\Models\UserActivity;

class DocumentController extends Controller
{
    /**
     * Display a listing of the documents.
     */
    public function index()
    {
        $role = auth()->user()->role ?? 'none';
        if (!in_array($role, ['admin', 'mod', 'user'])) {
            abort(403, 'Brak dostępu.');
        }

        $documents = Document::with('module')->orderBy('created_at', 'desc')->get();
        
        return view('Backend.documents.index', compact('documents', 'role'));
    }

    /**
     * Show the form for creating a new document.
     */
    public function create()
    {
        $this->authorizeAdmin();

        $modules = PModul::whereNull('parent_id')->with('children')->orderBy('nazwa')->get();
        
        return view('Backend.documents.create', compact('modules'));
    }

    /**
     * Store a newly created document in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'nazwa' => 'required|string|max:255',
            'p_modul_id' => 'required|exists:P_modul,id',
            'file' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,png,jpg,jpeg',
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('documents', 'local');

            $document = Document::create([
                'nazwa' => $validated['nazwa'],
                'file_path' => $path,
                'original_filename' => $file->getClientOriginalName(),
                'p_modul_id' => $validated['p_modul_id'],
            ]);

            UserActivity::log('create_document', "Utworzono dokument: {$document->nazwa} w module ID: {$document->p_modul_id}");

            return redirect()->route('documents.index')->with('success', 'Dokument został pomyślnie dodany.');
        }

        return back()->withErrors(['file' => 'Błąd podczas wgrywania pliku.'])->withInput();
    }

    /**
     * Show the form for editing the specified document.
     */
    public function edit(Document $document)
    {
        $this->authorizeAdmin();

        $modules = PModul::whereNull('parent_id')->with('children')->orderBy('nazwa')->get();
        
        return view('Backend.documents.edit', compact('document', 'modules'));
    }

    /**
     * Update the specified document in storage.
     */
    public function update(Request $request, Document $document)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'nazwa' => 'required|string|max:255',
            'p_modul_id' => 'required|exists:P_modul,id',
            'file' => 'nullable|file|max:10240', // max 10MB
        ]);

        $updateData = [
            'nazwa' => $validated['nazwa'],
            'p_modul_id' => $validated['p_modul_id'],
        ];

        if ($request->hasFile('file')) {
            // Delete old file
            if (Storage::disk('local')->exists($document->file_path)) {
                Storage::disk('local')->delete($document->file_path);
            }

            // Store new file
            $file = $request->file('file');
            $path = $file->store('documents', 'local');
            
            $updateData['file_path'] = $path;
            $updateData['original_filename'] = $file->getClientOriginalName();
        }

        $document->update($updateData);

        UserActivity::log('update_document', "Zaktualizowano dokument: {$document->nazwa} w module ID: {$document->p_modul_id}");

        return redirect()->route('documents.index')->with('success', 'Dokument został pomyślnie zaktualizowany.');
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Document $document)
    {
        $this->authorizeAdmin();

        $nazwa = $document->nazwa;

        // Delete physical file
        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        // Delete database record
        $document->delete();

        UserActivity::log('delete_document', "Usunięto dokument: {$nazwa}");

        return redirect()->route('documents.index')->with('success', 'Dokument został usunięty.');
    }

    /**
     * Download the specified document.
     */
    public function download(Document $document)
    {
        $role = auth()->user()->role ?? 'none';
        if (!in_array($role, ['admin', 'mod', 'user'])) {
            abort(403, 'Brak dostępu.');
        }

        if (!Storage::disk('local')->exists($document->file_path)) {
            abort(404, 'Plik nie istnieje na serwerze.');
        }

        UserActivity::log('download_document', "Pobrano dokument: {$document->nazwa}");

        $ext = pathinfo($document->original_filename ?: $document->file_path, PATHINFO_EXTENSION);
        $downloadName = $document->nazwa;
        
        // Append extension if it is not already present in the name
        if (!preg_match('/\\.' . preg_quote($ext, '/') . '$/i', $downloadName)) {
            $downloadName .= '.' . $ext;
        }

        return Storage::disk('local')->download($document->file_path, $downloadName);
    }

    /**
     * Authorize admin only.
     */
    private function authorizeAdmin()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }
    }
}
