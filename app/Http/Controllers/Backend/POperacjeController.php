<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\POperacje;
use App\Models\UserActivity;

class POperacjeController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAdmin();
        $search = $request->get('search');
        
        $sortBy = $request->get('sort_by', 'nazwa');
        $sortDir = $request->get('sort_dir', 'asc');
        
        if (!in_array($sortBy, ['id', 'nazwa'])) {
            $sortBy = 'nazwa';
        }
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

        $query = POperacje::orderBy($sortBy, $sortDir);
        if ($search) {
            $query->where('nazwa', 'like', "%{$search}%");
        }
        $operations = $query->get();
        return view('Backend.admin.permissions.operations.index', compact('operations', 'search', 'sortBy', 'sortDir'));
    }

    public function create()
    {
        $this->authorizeAdmin();
        return view('Backend.admin.permissions.operations.create');
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        
        $validated = $request->validate([
            'nazwa' => 'required|string|max:255|unique:P_operacje,nazwa',
        ]);

        $operacja = POperacje::create($validated);
        UserActivity::log('create_operation', "Utworzono operację: {$operacja->nazwa}");

        return redirect()->route('operations.index')->with('success', 'Operacja została utworzona.');
    }

    public function edit(POperacje $operation)
    {
        $this->authorizeAdmin();
        return view('Backend.admin.permissions.operations.edit', compact('operation'));
    }

    public function update(Request $request, POperacje $operation)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'nazwa' => 'required|string|max:255|unique:P_operacje,nazwa,' . $operation->id,
        ]);

        $operation->update($validated);
        UserActivity::log('update_operation', "Zaktualizowano operację: {$operation->nazwa}");

        return redirect()->route('operations.index')->with('success', 'Operacja została zaktualizowana.');
    }

    public function destroy(POperacje $operation)
    {
        $this->authorizeAdmin();

        if ($operation->pAccesses()->exists()) {
            $count = $operation->pAccesses()->count();
            return redirect()->route('operations.index')
                ->with('error', "Nie można usunąć operacji \u201e{$operation->nazwa}\u201c, ponieważ jest używana w {$count} przypisaniach uprawnień. Najpierw usuń te przypisania.");
        }

        $nazwa = $operation->nazwa;
        $operation->delete();

        UserActivity::log('delete_operation', "Usunięto operację: {$nazwa}");

        return redirect()->route('operations.index')->with('success', 'Operacja została usunięta.');
    }

    private function authorizeAdmin()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }
    }
}
