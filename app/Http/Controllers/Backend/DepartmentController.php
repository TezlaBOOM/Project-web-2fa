<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Departament;
use App\Models\Tag;
use App\Models\UserActivity;

class DepartmentController extends Controller
{
    private function checkAccess()
    {
        $role = auth()->user()->role ?? 'none';
        if (!in_array($role, ['admin', 'mod', 'support', 'view'])) {
            abort(403, 'Brak dostępu.');
        }
        return $role;
    }

    public function index(Request $request)
    {
        $this->checkAccess();

        $search = trim((string) $request->get('search', ''));
        $tagId = $request->get('tag_id');
        $sortBy = $request->get('sort_by', 'Nazwa');
        $sortDir = $request->get('sort_dir', 'asc');
        
        if (!in_array($sortBy, ['ID_Departament', 'Nazwa', 'created_at'])) {
            $sortBy = 'Nazwa';
        }
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

        $query = Departament::with('tags');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('Nazwa', 'like', "%{$search}%")
                  ->orWhere('Description', 'like', "%{$search}%")
                  ->orWhereHas('tags', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($tagId) {
            $query->whereHas('tags', function ($tq) use ($tagId) {
                $tq->where('tags.id', $tagId);
            });
        }

        $departments = $query->orderBy($sortBy, $sortDir)->get();
        $tags = Tag::orderBy('name')->get();

        return view('Backend.admin.departments.index', compact('departments', 'sortBy', 'sortDir', 'tags', 'tagId', 'search'));
    }

    public function create()
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }
        $allTags = Tag::orderBy('name')->get();
        return view('Backend.admin.departments.create', compact('allTags'));
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
            'tags'        => 'nullable|string|max:1000',
        ], [
            'Nazwa.required' => 'Nazwa wydziału jest wymagana.',
            'Nazwa.max'      => 'Nazwa wydziału może mieć maksymalnie 255 znaków.',
        ]);

        $department = Departament::create([
            'Nazwa'       => $validated['Nazwa'],
            'Description' => $validated['Description'] ?? null,
        ]);

        $department->syncTagsFromString($validated['tags'] ?? '');

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
        $department->load('tags');
        $allTags = Tag::orderBy('name')->get();
        return view('Backend.admin.departments.edit', compact('department', 'allTags'));
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
            'tags'        => 'nullable|string|max:1000',
        ], [
            'Nazwa.required' => 'Nazwa wydziału jest wymagana.',
            'Nazwa.max'      => 'Nazwa wydziału może mieć maksymalnie 255 znaków.',
        ]);

        $department->Nazwa       = $validated['Nazwa'];
        $department->Description = $validated['Description'] ?? null;
        $department->save();

        $department->syncTagsFromString($validated['tags'] ?? '');

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
