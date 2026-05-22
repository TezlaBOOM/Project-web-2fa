<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PModul;
use App\Models\UserActivity;

class PModulController extends Controller
{
    public function index()
    {
        $this->authorizeAdmin();
        $search = request('search');
        if ($search) {
            $modules = PModul::where('nazwa', 'like', "%{$search}%")->with('parent')->orderBy('nazwa')->get();
        } else {
            $modules = PModul::whereNull('parent_id')->with('children')->orderBy('nazwa')->get();
        }
        return view('Backend.admin.permissions.modules.index', compact('modules', 'search'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        $allModules = PModul::whereNull('parent_id')->with('children')->orderBy('nazwa')->get();
        $parent_id = request('parent_id');
        return view('Backend.admin.permissions.modules.create', compact('allModules', 'parent_id'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        
        $validated = $request->validate([
            'nazwa' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:P_modul,id',
        ]);

        if (!empty($validated['parent_id'])) {
            $parent = PModul::find($validated['parent_id']);
            if ($parent->getDepth() >= 4) { // depth 0 is root, up to depth 4 (5 levels)
                return back()->withErrors(['parent_id' => 'Zbyt głębokie zagnieżdżenie. Maksymalny limit to 5 poziomów.'])->withInput();
            }
        }

        $modul = PModul::create($validated);
        UserActivity::log('create_module', "Utworzono moduł: {$modul->nazwa}");

        return redirect()->route('modules.index')->with('success', 'Moduł został utworzony.');
    }

    public function edit(PModul $module)
    {
        $this->authorizeAdmin();
        $allModules = PModul::whereNull('parent_id')->with('children')->orderBy('nazwa')->get();
        return view('Backend.admin.permissions.modules.edit', compact('module', 'allModules'));
    }

    public function update(Request $request, PModul $module)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'nazwa' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:P_modul,id',
        ]);

        if (!empty($validated['parent_id'])) {
            if ($validated['parent_id'] == $module->id) {
                return back()->withErrors(['parent_id' => 'Moduł nie może być swoim własnym rodzicem.'])->withInput();
            }

            $parent = PModul::find($validated['parent_id']);
            if ($parent->getDepth() >= 4) {
                return back()->withErrors(['parent_id' => 'Zbyt głębokie zagnieżdżenie. Maksymalny limit to 5 poziomów.'])->withInput();
            }
        }

        $module->update($validated);
        UserActivity::log('update_module', "Zaktualizowano moduł: {$module->nazwa}");

        return redirect()->route('modules.index')->with('success', 'Moduł został zaktualizowany.');
    }

    public function destroy(PModul $module)
    {
        $this->authorizeAdmin();

        if ($module->children()->exists()) {
            return redirect()->route('modules.index')
                ->with('error', "Nie można usunąć modułu \u201e{$module->nazwa}\u201c, ponieważ posiada podkategorie. Najpierw usuń lub przesuń podkategorie.");
        }

        if ($module->pAccesses()->exists()) {
            $count = $module->pAccesses()->count();
            return redirect()->route('modules.index')
                ->with('error', "Nie można usunąć modułu \u201e{$module->nazwa}\u201c, ponieważ jest używany w {$count} przypisaniach uprawnień. Najpierw usuń te przypisania.");
        }

        if ($module->documents()->exists()) {
            $count = $module->documents()->count();
            return redirect()->route('modules.index')
                ->with('error', "Nie można usunąć modułu \u201e{$module->nazwa}\u201c, ponieważ posiada {$count} przypisanych dokumentów. Najpierw usuń te dokumenty.");
        }

        $nazwa = $module->nazwa;
        $module->delete();

        UserActivity::log('delete_module', "Usunięto moduł: {$nazwa}");

        return redirect()->route('modules.index')->with('success', 'Moduł został usunięty.');
    }

    private function authorizeAdmin()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }
    }
}
