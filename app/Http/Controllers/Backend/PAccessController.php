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

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        
        if (!in_array($sortBy, ['name', 'email'])) {
            $sortBy = 'name';
        }
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

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
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('changeLogs', function($cq) use ($search) {
                      $cq->whereIn('field_name', ['name', 'email'])
                         ->where('old_value', 'like', "%{$search}%");
                  });
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

        $users = $baseQuery->orderBy($sortBy, $sortDir)->paginate(20)->withQueryString();

        return view('Backend.admin.permissions.access.index', compact(
            'users', 'role', 'search', 'selectedUser', 'selectedAccesses', 'userId', 'departments', 'deptId', 'sortBy', 'sortDir'
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
            'login' => 'nullable|string|max:255',
            'uwagi' => 'nullable|string',
        ]);

        // Move any expired duplicate to history and delete it before checking existence
        $expiredAccess = PAccess::where('user_id', $validated['user_id'])
            ->where('p_modul_id', $validated['p_modul_id'])
            ->where('p_operacje_id', $validated['p_operacje_id'])
            ->get()
            ->filter(fn($acc) => !$acc->isValid());

        foreach ($expiredAccess as $acc) {
            \App\Models\PAccessHistory::create([
                'user_id' => $acc->user_id,
                'p_modul_id' => $acc->p_modul_id,
                'p_operacje_id' => $acc->p_operacje_id,
                'valid_from' => $acc->valid_from,
                'valid_to' => $acc->valid_to,
                'login' => $acc->login,
                'uwagi' => $acc->uwagi,
                'action' => 'wygasło',
            ]);
            $acc->delete();
        }

        // Check if active access already exists
        $exists = PAccess::where('user_id', $validated['user_id'])
            ->where('p_modul_id', $validated['p_modul_id'])
            ->where('p_operacje_id', $validated['p_operacje_id'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => 'Ten użytkownik posiada już takie aktywne uprawnienie.'])->withInput();
        }

        $access = PAccess::create($validated);

        // Save to history
        \App\Models\PAccessHistory::create([
            'user_id' => $access->user_id,
            'p_modul_id' => $access->p_modul_id,
            'p_operacje_id' => $access->p_operacje_id,
            'valid_from' => $access->valid_from,
            'valid_to' => $access->valid_to,
            'login' => $access->login,
            'uwagi' => $access->uwagi,
            'action' => 'nadano',
        ]);

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
            'login' => 'nullable|string|max:255',
            'uwagi' => 'nullable|string',
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

        // Save to history
        \App\Models\PAccessHistory::create([
            'user_id' => $access->user_id,
            'p_modul_id' => $access->p_modul_id,
            'p_operacje_id' => $access->p_operacje_id,
            'valid_from' => $access->valid_from,
            'valid_to' => $access->valid_to,
            'login' => $access->login,
            'uwagi' => $access->uwagi,
            'action' => 'zaktualizowano',
        ]);

        UserActivity::log('update_access', "Zaktualizowano uprawnienie dla użytkownika ID: {$access->user_id}");

        return redirect()->route('access.index', ['user_id' => $access->user_id])->with('success', 'Uprawnienie zostało zaktualizowane.');
    }

    public function destroy(PAccess $access)
    {
        $this->authorizeAdmin();
        
        $userId = $access->user_id;

        // Save to history before deleting
        \App\Models\PAccessHistory::create([
            'user_id' => $access->user_id,
            'p_modul_id' => $access->p_modul_id,
            'p_operacje_id' => $access->p_operacje_id,
            'valid_from' => $access->valid_from,
            'valid_to' => $access->valid_to,
            'login' => $access->login,
            'uwagi' => $access->uwagi,
            'action' => 'odebrano',
        ]);

        $access->delete();
        
        UserActivity::log('delete_access', "Usunięto uprawnienie dla użytkownika ID: {$userId}");

        return redirect()->route('access.index', ['user_id' => $userId])->with('success', 'Uprawnienie zostało usunięte.');
    }

    public function getHistory(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'p_modul_id' => 'required|exists:P_modul,id',
            'p_operacje_id' => 'required|exists:P_operacje,id',
        ]);

        $history = \App\Models\PAccessHistory::with(['modul', 'operacja'])
            ->where('user_id', $validated['user_id'])
            ->where('p_modul_id', $validated['p_modul_id'])
            ->where('p_operacje_id', $validated['p_operacje_id'])
            ->orderBy('created_at', 'desc')
            ->get();

        $active = PAccess::with(['modul', 'operacja'])
            ->where('user_id', $validated['user_id'])
            ->where('p_modul_id', $validated['p_modul_id'])
            ->where('p_operacje_id', $validated['p_operacje_id'])
            ->first();

        return response()->json([
            'success' => true,
            'active' => $active ? [
                'valid_from' => $active->valid_from ? $active->valid_from->format('Y-m-d') : 'Brak',
                'valid_to' => $active->valid_to ? $active->valid_to->format('Y-m-d') : 'Brak',
                'login' => $active->login ?? 'Brak',
                'uwagi' => $active->uwagi ?? 'Brak',
                'status' => $active->isValid() ? 'Aktywny' : 'Nieaktywny/Wygasł',
            ] : null,
            'history' => $history->map(function($item) {
                return [
                    'action' => ucfirst($item->action),
                    'valid_from' => $item->valid_from ? $item->valid_from->format('Y-m-d') : 'Brak',
                    'valid_to' => $item->valid_to ? $item->valid_to->format('Y-m-d') : 'Brak',
                    'login' => $item->login ?? 'Brak',
                    'uwagi' => $item->uwagi ?? 'Brak',
                    'date' => $item->created_at->format('Y-m-d H:i'),
                ];
            })
        ]);
    }

    private function authorizeAdmin()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }
    }

    public function csvView()
    {
        $this->authorizeAdmin();
        return view('Backend.admin.permissions.access.csv');
    }

    public function csvPattern()
    {
        $this->authorizeAdmin();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="wzor_uprawnienia.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['email_użytkownika', 'nazwa_modułu', 'nazwa_operacji', 'ważne_od', 'ważne_do', 'login', 'uwagi']);
            fputcsv($file, ['jan.kowalski@example.com', 'Administracja / Użytkownicy', 'Podgląd', '2026-08-01', '2026-12-31', 'jkowalski', 'Dostęp testowy']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function csvExport()
    {
        $this->authorizeAdmin();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="uprawnienia.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $accesses = PAccess::with(['user', 'modul', 'operacja'])->get();

        $callback = function() use ($accesses) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['email_użytkownika', 'nazwa_modułu', 'nazwa_operacji', 'ważne_od', 'ważne_do', 'login', 'uwagi']);

            foreach ($accesses as $access) {
                if (!$access->user || !$access->modul || !$access->operacja) continue;
                fputcsv($file, [
                    $access->user->email,
                    $this->getModuleFullPath($access->modul),
                    $access->operacja->nazwa,
                    $access->valid_from ? $access->valid_from->format('Y-m-d') : '',
                    $access->valid_to ? $access->valid_to->format('Y-m-d') : '',
                    $access->login,
                    $access->uwagi
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function csvImport(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate([
            'csv_file' => 'required|file|max:2048',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        if (!$handle) {
            return back()->with('error', 'Nie można otworzyć pliku CSV.');
        }

        // Read BOM if exists
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($handle);
        }

        $header = fgetcsv($handle, 1000, ',');
        if (!$header || count($header) < 3) {
            fclose($handle);
            return back()->with('error', 'Niepoprawny format nagłówków CSV. Wymagane kolumny: email_użytkownika, nazwa_modułu, nazwa_operacji.');
        }

        // Standardize column names (remove spaces and lowercase)
        $header = array_map(function($h) {
            return trim(strtolower($h));
        }, $header);

        $emailIdx = array_search('email_użytkownika', $header);
        if ($emailIdx === false) $emailIdx = array_search('email_uzytkownika', $header);
        if ($emailIdx === false) $emailIdx = array_search('email', $header);

        $modIdx = array_search('nazwa_modułu', $header);
        if ($modIdx === false) $modIdx = array_search('nazwa_modulu', $header);
        if ($modIdx === false) $modIdx = array_search('moduł', $header);
        if ($modIdx === false) $modIdx = array_search('modul', $header);

        $opIdx = array_search('nazwa_operacji', $header);
        if ($opIdx === false) $opIdx = array_search('operacja', $header);

        $fromIdx = array_search('ważne_od', $header);
        if ($fromIdx === false) $fromIdx = array_search('wazne_od', $header);

        $toIdx = array_search('ważne_do', $header);
        if ($toIdx === false) $toIdx = array_search('wazne_do', $header);

        $loginIdx = array_search('login', $header);
        $uwagiIdx = array_search('uwagi', $header);

        if ($emailIdx === false || $modIdx === false || $opIdx === false) {
            fclose($handle);
            return back()->with('error', 'Nie znaleziono wymaganych kolumn w CSV (email_użytkownika, nazwa_modułu, nazwa_operacji).');
        }

        $importedCount = 0;
        $errors = [];
        $lineNum = 1;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $lineNum++;
            if (empty(array_filter($row))) continue; // Skip empty rows

            $email = trim($row[$emailIdx] ?? '');
            $modName = trim($row[$modIdx] ?? '');
            $opName = trim($row[$opIdx] ?? '');
            $validFrom = $fromIdx !== false ? trim($row[$fromIdx] ?? '') : '';
            $validTo = $toIdx !== false ? trim($row[$toIdx] ?? '') : '';
            $login = $loginIdx !== false ? trim($row[$loginIdx] ?? '') : '';
            $uwagi = $uwagiIdx !== false ? trim($row[$uwagiIdx] ?? '') : '';

            if (!$email || !$modName || !$opName) {
                $errors[] = "Wiersz {$lineNum}: Brak email, modułu lub operacji.";
                continue;
            }

            // Find user
            $user = User::where('email', $email)->first();
            if (!$user) {
                $errors[] = "Wiersz {$lineNum}: Użytkownik o adresie email {$email} nie istnieje.";
                continue;
            }

            // Find or create module (supports nested categories separated by /)
            $segments = array_map('trim', explode('/', $modName));
            $segments = array_filter($segments); // Remove empty segments
            
            if (empty($segments)) {
                $errors[] = "Wiersz {$lineNum}: Nazwa modułu jest niepoprawna.";
                continue;
            }

            if (count($segments) > 5) {
                $errors[] = "Wiersz {$lineNum}: Przekroczono maksymalny poziom zagnieżdżenia modułów (maksymalnie 5).";
                continue;
            }

            $parentId = null;
            $modul = null;
            foreach ($segments as $segment) {
                $modul = PModul::firstOrCreate([
                    'nazwa' => $segment,
                    'parent_id' => $parentId
                ]);
                $parentId = $modul->id;
            }

            // Find or create operation
            $operacja = POperacje::firstOrCreate(
                ['nazwa' => $opName]
            );

            // Parse dates
            $dateFrom = $validFrom ? date('Y-m-d', strtotime($validFrom)) : null;
            $dateTo = $validTo ? date('Y-m-d', strtotime($validTo)) : null;

            // Move any expired duplicate to history and delete it before checking existence
            $expiredAccess = PAccess::where('user_id', $user->id)
                ->where('p_modul_id', $modul->id)
                ->where('p_operacje_id', $operacja->id)
                ->get()
                ->filter(fn($acc) => !$acc->isValid());

            foreach ($expiredAccess as $acc) {
                \App\Models\PAccessHistory::create([
                    'user_id' => $acc->user_id,
                    'p_modul_id' => $acc->p_modul_id,
                    'p_operacje_id' => $acc->p_operacje_id,
                    'valid_from' => $acc->valid_from,
                    'valid_to' => $acc->valid_to,
                    'login' => $acc->login,
                    'uwagi' => $acc->uwagi,
                    'action' => 'wygasło',
                ]);
                $acc->delete();
            }

            // Check if active access already exists
            $exists = PAccess::where('user_id', $user->id)
                ->where('p_modul_id', $modul->id)
                ->where('p_operacje_id', $operacja->id)
                ->first();

            if ($exists) {
                // Update the existing active permission
                $exists->update([
                    'valid_from' => $dateFrom,
                    'valid_to' => $dateTo,
                    'login' => $login ?: $exists->login,
                    'uwagi' => $uwagi ?: $exists->uwagi,
                ]);

                \App\Models\PAccessHistory::create([
                    'user_id' => $user->id,
                    'p_modul_id' => $modul->id,
                    'p_operacje_id' => $operacja->id,
                    'valid_from' => $dateFrom,
                    'valid_to' => $dateTo,
                    'login' => $login ?: $exists->login,
                    'uwagi' => $uwagi ?: $exists->uwagi,
                    'action' => 'zaktualizowano',
                ]);
            } else {
                // Create a new permission
                $access = PAccess::create([
                    'user_id' => $user->id,
                    'p_modul_id' => $modul->id,
                    'p_operacje_id' => $operacja->id,
                    'valid_from' => $dateFrom,
                    'valid_to' => $dateTo,
                    'login' => $login,
                    'uwagi' => $uwagi,
                ]);

                \App\Models\PAccessHistory::create([
                    'user_id' => $user->id,
                    'p_modul_id' => $modul->id,
                    'p_operacje_id' => $operacja->id,
                    'valid_from' => $dateFrom,
                    'valid_to' => $dateTo,
                    'login' => $login,
                    'uwagi' => $uwagi,
                    'action' => 'nadano',
                ]);
            }

            UserActivity::log('import_access', "Zaimportowano uprawnienie dla {$email}: {$modName} - {$opName}");
            $importedCount++;
        }

        fclose($handle);

        $msg = "Pomyślnie zaimportowano {$importedCount} uprawnień.";
        if (!empty($errors)) {
            return back()->with('success', $msg)->with('warnings', $errors);
        }

        return back()->with('success', $msg);
    }

    private function getModuleFullPath($modul)
    {
        $path = [$modul->nazwa];
        $parent = $modul->parent;
        while ($parent) {
            array_unshift($path, $parent->nazwa);
            $parent = $parent->parent;
        }
        return implode(' / ', $path);
    }
}
