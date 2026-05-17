<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Departament;
use App\Models\UserActivity;

class DepartmentController extends Controller
{
    private function checkAccess()
    {
        $role = auth()->user()->role ?? 'none';
        if (!in_array($role, ['admin', 'mod'])) {
            abort(403, 'Brak dostępu.');
        }
        return $role;
    }

    public function index()
    {
        $this->checkAccess();
        $departments = Departament::orderBy('ID_Departament', 'asc')->get();
        return view('Backend.admin.departments.index', compact('departments'));
    }

    public function create()
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }
        return view('Backend.admin.departments.create');
    }

    public function store(Request $request)
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $validated = $request->validate([
            'Nazwa'       => 'required|string|max:255',
            'Description' => 'nullable|string',
        ], [
            'Nazwa.required' => 'Nazwa wydziału jest wymagana.',
            'Nazwa.max'      => 'Nazwa wydziału może mieć maksymalnie 255 znaków.',
        ]);

        Departament::create($validated);

        UserActivity::log('create_department', "Utworzono wydział: {$validated['Nazwa']}");

        return redirect()->route('departments.index')
            ->with('success', 'Wydział został pomyślnie utworzony.');
    }

    public function edit(Departament $department)
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }
        return view('Backend.admin.departments.edit', compact('department'));
    }

    public function update(Request $request, Departament $department)
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $validated = $request->validate([
            'Nazwa'       => 'required|string|max:255',
            'Description' => 'nullable|string',
        ], [
            'Nazwa.required' => 'Nazwa wydziału jest wymagana.',
            'Nazwa.max'      => 'Nazwa wydziału może mieć maksymalnie 255 znaków.',
        ]);

        $department->Nazwa       = $validated['Nazwa'];
        $department->Description = $validated['Description'] ?? null;
        $department->save();

        UserActivity::log('update_department', "Zaktualizowano wydział: {$department->Nazwa}");

        return redirect()->route('departments.index')
            ->with('success', 'Dane wydziału zostały zaktualizowane.');
    }

    public function destroy(Departament $department)
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        if (strtolower($department->Nazwa) === 'all') {
            return redirect()->route('departments.index')
                ->with('error', 'Nie można usunąć głównego departamentu "all".');
        }

        if ($department->users()->exists()) {
            return redirect()->route('departments.index')
                ->with('error', 'Nie można usunąć wydziału, ponieważ są do niego przypisani użytkownicy.');
        }

        $departmentName = $department->Nazwa;
        $department->delete();

        UserActivity::log('delete_department', "Usunięto wydział: {$departmentName}");

        return redirect()->route('departments.index')
            ->with('success', 'Wydział został usunięty.');
    }
}
