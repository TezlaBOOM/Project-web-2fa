<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PAccess;
use App\Models\User;
use App\Models\PModul;
use App\Models\POperacje;
use App\Models\UserActivity;
use App\Models\Departament;

class PAccessController extends Controller
{
    public function index(Request $request)
    {
        $role = auth()->user()->role ?? 'none';
        if (!in_array($role, ['admin', 'mod'])) {
            abort(403, 'Brak dostępu.');
        }

        $search   = $request->get('search', '');
        $userId   = $request->get('user_id');
        $deptId   = $request->get('dept_id');

        $baseQuery = User::withCount('pAccesses')
            ->with(['pAccesses.modul.parent', 'pAccesses.operacja']);

        if ($role === 'mod') {
            $departmentIds = auth()->user()->departments->pluck('ID_Departament');
            $baseQuery->whereHas('departments', function($q) use ($departmentIds) {
                $q->whereIn('Departament.ID_Departament', $departmentIds);
            });
            $departments = Departament::whereIn('ID_Departament', $departmentIds)->orderBy('Nazwa')->get();
        } else {
            $departments = Departament::orderBy('Nazwa')->get();
        }

        if ($search) {
            $baseQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($deptId) {
            $baseQuery->whereHas('departments', function($q) use ($deptId) {
                $q->where('Departament.ID_Departament', $deptId);
            });
        }

        $selectedUser    = null;
        $selectedAccesses = null;
        if ($userId) {
            $selectedUser = User::with('departments')->find($userId);
            if ($selectedUser) {
                $selectedAccesses = PAccess::with(['modul.parent', 'operacja'])
                    ->where('user_id', $userId)
                    ->get();
            }
        }

        $users = $baseQuery->orderBy('name')->paginate(20)->withQueryString();

        return view('Backend.admin.permissions.access.index', compact(
            'users', 'role', 'search', 'selectedUser', 'selectedAccesses', 'userId', 'departments', 'deptId'
        ));
    }

    public function create()
    {
        $this->authorizeAdmin();
        $preselectedUser = request('user_id') ? User::find(request('user_id')) : null;
        $users = $preselectedUser ? collect() : User::orderBy('name')->get();
        $modules = PModul::whereNull('parent_id')->with('children')->orderBy('nazwa')->get();
        $operations = POperacje::orderBy('nazwa')->get();

        return view('Backend.admin.permissions.access.create', compact('users', 'modules', 'operations', 'preselectedUser'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'p_modul_id' => 'required|exists:P_modul,id',
            'p_operacje_id' => 'required|exists:P_operacje,id',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ]);

        // Check if access already exists
        $exists = PAccess::where('user_id', $validated['user_id'])
            ->where('p_modul_id', $validated['p_modul_id'])
            ->where('p_operacje_id', $validated['p_operacje_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'Ten użytkownik posiada już takie uprawnienie.'])->withInput();
        }

        $access = PAccess::create($validated);
        UserActivity::log('create_access', "Przydzielono uprawnienie dla użytkownika ID: {$access->user_id}");

        return redirect()->route('access.index', ['user_id' => $access->user_id])->with('success', 'Uprawnienie zostało dodane.');
    }

    public function edit(PAccess $access)
    {
        $this->authorizeAdmin();
        $users = User::orderBy('name')->get();
        $modules = PModul::whereNull('parent_id')->with('children')->orderBy('nazwa')->get();
        $operations = POperacje::orderBy('nazwa')->get();
        
        return view('Backend.admin.permissions.access.edit', compact('access', 'users', 'modules', 'operations'));
    }

    public function update(Request $request, PAccess $access)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'p_modul_id' => 'required|exists:P_modul,id',
            'p_operacje_id' => 'required|exists:P_operacje,id',
            'valid_from' => 'nullable|date',
            'valid_to' => 'nullable|date|after_or_equal:valid_from',
        ]);

        $exists = PAccess::where('user_id', $validated['user_id'])
            ->where('p_modul_id', $validated['p_modul_id'])
            ->where('p_operacje_id', $validated['p_operacje_id'])
            ->where('id', '!=', $access->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'Ten użytkownik posiada już takie uprawnienie.'])->withInput();
        }

        $access->update($validated);
        UserActivity::log('update_access', "Zaktualizowano uprawnienie dla użytkownika ID: {$access->user_id}");

        return redirect()->route('access.index', ['user_id' => $access->user_id])->with('success', 'Uprawnienie zostało zaktualizowane.');
    }

    public function destroy(PAccess $access)
    {
        $this->authorizeAdmin();
        
        $userId = $access->user_id;
        $access->delete();
        
        UserActivity::log('delete_access', "Usunięto uprawnienie dla użytkownika ID: {$userId}");

        return redirect()->route('access.index')->with('success', 'Uprawnienie zostało usunięte.');
    }

    private function authorizeAdmin()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }
    }
}
