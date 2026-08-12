<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Departament;
use App\Models\UserActivity;
use App\Models\PAccess;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = auth()->user()->role ?? 'none';
        
        if (!in_array($role, ['admin', 'mod'])) {
            abort(403, 'Brak dostępu.');
        }

        $search = $request->get('search', '');

        $query = User::with('departments');

        if ($role === 'mod') {
            $departmentIds = auth()->user()->departments->pluck('ID_Departament');
            $query->whereHas('departments', function($q) use ($departmentIds) {
                $q->whereIn('Departament.ID_Departament', $departmentIds);
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('changeLogs', function($cq) use ($search) {
                      $cq->whereIn('field_name', ['name', 'email'])
                         ->where('old_value', 'like', "%{$search}%");
                  });
            });
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');

        if (!in_array($sortBy, ['id', 'name', 'email'])) {
            $sortBy = 'name';
        }
        if (!in_array($sortDir, ['asc', 'desc'])) {
            $sortDir = 'asc';
        }

        $users = $query->orderBy($sortBy, $sortDir)->get();
        
        return view("Backend.{$role}.users.index", compact('users', 'search', 'sortBy', 'sortDir'));
    }

    public function create()
    {
        $role = auth()->user()->role ?? 'none';

        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $departments = Departament::orderBy('Nazwa')->get();

        return view('Backend.admin.users.create', compact('departments'));
    }

    public function store(Request $request)
    {
        $role = auth()->user()->role ?? 'none';

        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8|confirmed',
            'role'        => 'required|in:admin,mod,user,none',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:Departament,ID_Departament',
        ], [
            'name.required'      => 'Imię i nazwisko jest wymagane.',
            'email.required'     => 'Adres e-mail jest wymagany.',
            'email.email'        => 'Podaj prawidłowy adres e-mail.',
            'email.unique'       => 'Użytkownik z tym adresem e-mail już istnieje.',
            'password.required'  => 'Hasło jest wymagane.',
            'password.min'       => 'Hasło musi mieć co najmniej 8 znaków.',
            'password.confirmed' => 'Potwierdzenie hasła nie zgadza się.',
            'role.required'      => 'Rola jest wymagana.',
            'role.in'            => 'Wybierz prawidłową rolę.',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
        ]);

        if (!empty($validated['departments'])) {
            $user->departments()->attach($validated['departments']);
        }

        UserActivity::log('create_user', "Utworzono użytkownika: {$user->email}");

        return redirect()->route('users.index')
            ->with('success', 'Użytkownik został pomyślnie utworzony.');
    }

    public function edit(User $user)
    {
        $role = auth()->user()->role ?? 'none';

        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $departments = Departament::orderBy('Nazwa')->get();

        $changeLogs = \App\Models\UserChangeLog::with('editor')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $historyNameEmail = $changeLogs->whereIn('field_name', ['name', 'email', 'departments']);
        $historyOther = $changeLogs->whereNotIn('field_name', ['name', 'email', 'departments']);

        return view('Backend.admin.users.edit', compact('user', 'departments', 'historyNameEmail', 'historyOther'));
    }

    public function update(Request $request, User $user)
    {
        $role = auth()->user()->role ?? 'none';

        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'role'        => 'required|in:admin,mod,user,none',
            'password'    => 'nullable|string|min:8|confirmed',
            'assignments' => 'nullable|array',
            'assignments.*.department_id' => 'required|exists:Departament,ID_Departament',
            'assignments.*.od' => 'nullable|date',
            'assignments.*.do' => 'nullable|date',
            'two_factor_enabled' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ], [
            'name.required'      => 'Imię i nazwisko jest wymagane.',
            'email.required'     => 'Adres e-mail jest wymagany.',
            'email.email'        => 'Podaj prawidłowy adres e-mail.',
            'email.unique'       => 'Użytkownik z tym adresem e-mail już istnieje.',
            'password.min'       => 'Hasło musi mieć co najmniej 8 znaków.',
            'password.confirmed' => 'Potwierdzenie hasła nie zgadza się.',
            'role.required'      => 'Rola jest wymagana.',
            'role.in'            => 'Wybierz prawidłową rolę.',
        ]);

        $formatDeptsWithDates = function($departments) {
            if ($departments->isEmpty()) {
                return 'Brak';
            }
            return $departments->map(function($dept) {
                $odStr = $dept->pivot->od ?: 'brak';
                $doStr = $dept->pivot->do ?: 'aktualnie';
                return "{$dept->Nazwa} (od: {$odStr}, do: {$doStr})";
            })->sort()->implode(', ');
        };

        // Capture changes before saving
        $changesToLog = [];
        $oldDepts = $formatDeptsWithDates($user->departments);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        // Blokada: administrator nie może sam sobie odebrać uprawnień
        if ($user->id === auth()->id()) {
            $user->role = $user->getOriginal('role');
        } else {
            $user->role = $validated['role'];
        }

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->two_factor_enabled = $request->has('two_factor_enabled');
        
        if ($user->id !== auth()->id()) {
            $user->is_active = $request->has('is_active');
        }

        // Compare model attributes
        $fieldsToCompare = ['name', 'email', 'role', 'two_factor_enabled', 'is_active'];
        foreach ($fieldsToCompare as $field) {
            if ($user->isDirty($field)) {
                $oldVal = $user->getOriginal($field);
                $newVal = $user->getAttribute($field);

                if ($field === 'two_factor_enabled' || $field === 'is_active') {
                    $oldVal = $oldVal ? 'aktywne/włączone' : 'nieaktywne/wyłączone';
                    $newVal = $newVal ? 'aktywne/włączone' : 'nieaktywne/wyłączone';
                }

                $changesToLog[] = [
                    'field_name' => $field,
                    'old_value' => $oldVal,
                    'new_value' => $newVal,
                ];
            }
        }

        if (!empty($validated['password'])) {
            $changesToLog[] = [
                'field_name' => 'password',
                'old_value' => '—',
                'new_value' => 'Zmieniono hasło',
            ];
        }

        $user->save();

        $user->departments()->detach();
        if (!empty($validated['assignments'])) {
            foreach ($validated['assignments'] as $assign) {
                if (empty($assign['department_id'])) continue;
                $user->departments()->attach($assign['department_id'], [
                    'od' => !empty($assign['od']) ? $assign['od'] : null,
                    'do' => !empty($assign['do']) ? $assign['do'] : null,
                ]);
            }
        }

        // Compare departments after sync
        $user->load('departments');
        $newDepts = $formatDeptsWithDates($user->departments);
        if ($oldDepts !== $newDepts) {
            $changesToLog[] = [
                'field_name' => 'departments',
                'old_value' => $oldDepts,
                'new_value' => $newDepts,
            ];
        }

        // Save logs to database
        foreach ($changesToLog as $log) {
            \App\Models\UserChangeLog::create([
                'user_id' => $user->id,
                'editor_id' => auth()->id(),
                'field_name' => $log['field_name'],
                'old_value' => $log['old_value'],
                'new_value' => $log['new_value'],
            ]);
        }

        UserActivity::log('update_user', "Zaktualizowano dane użytkownika: {$user->email}");

        return redirect()->route('users.index')
            ->with('success', 'Dane użytkownika zostały zaktualizowane.');
    }

    public function showPermissions(User $user)
    {
        $role = auth()->user()->role ?? 'none';

        if (!in_array($role, ['admin', 'mod'])) {
            abort(403, 'Brak dostępu.');
        }

        // Moderator może widzieć tylko swoich użytkowników
        if ($role === 'mod') {
            $departmentIds = auth()->user()->departments->pluck('ID_Departament');
            $hasAccess = $user->departments->pluck('ID_Departament')
                ->intersect($departmentIds)->isNotEmpty();

            if (!$hasAccess) {
                abort(403, 'Brak dostępu do tego użytkownika.');
            }
        }

        $accesses = PAccess::with(['modul', 'operacja'])
            ->where('user_id', $user->id)
            ->get();

        return view('Backend.mod.users.permissions', [
            'targetUser' => $user,
            'accesses' => $accesses,
        ]);
    }

    public function destroy(User $user)
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'Nie można usunąć własnego konta.');
        }

        if ($user->pAccesses()->exists()) {
            $count = $user->pAccesses()->count();
            return redirect()->route('users.index')
                ->with('error', "Nie można usunąć użytkownika \u201e{$user->name}\u201c, ponieważ posiada {$count} przypisanych uprawnień. Najpierw usuń jego uprawnienia.");
        }

        $name = $user->name;
        $user->departments()->detach();
        $user->delete();

        UserActivity::log('delete_user', "Usunięto użytkownika: {$name}");

        return redirect()->route('users.index')->with('success', "Użytkownik {$name} został usunięty.");
    }

    public function csvView()
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        return view('Backend.admin.users.csv');
    }

    public function csvPattern()
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="wzor_uzytkownicy.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['id', 'imię_i_nazwisko', 'email', 'rola', 'status', 'wydział', 'data_od', 'data_do']);
            fputcsv($file, ['', 'Jan Kowalski', 'jan.kowalski@example.com', 'user', 'aktywny', 'Wydział IT', '2026-01-01', '']);
            fputcsv($file, ['', 'Jan Kowalski', 'jan.kowalski@example.com', 'user', 'aktywny', 'Wydział Finansów', '2026-02-01', '2026-06-30']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function csvExport()
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="uzytkownicy.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $users = User::with('departments')->get();

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            // Write UTF-8 BOM
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['id', 'imię_i_nazwisko', 'email', 'rola', 'status', 'wydział', 'data_od', 'data_do']);

            foreach ($users as $user) {
                $status = $user->is_active ? 'aktywny' : 'nieaktywny';
                
                if ($user->departments->isEmpty()) {
                    fputcsv($file, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->role,
                        $status,
                        '',
                        '',
                        ''
                    ]);
                } else {
                    foreach ($user->departments as $dept) {
                        fputcsv($file, [
                            $user->id,
                            $user->name,
                            $user->email,
                            $user->role,
                            $status,
                            $dept->Nazwa,
                            $dept->pivot->od ?: '',
                            $dept->pivot->do ?: ''
                        ]);
                    }
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function csvImport(Request $request)
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $formatDeptsWithDates = function($departments) {
            if ($departments->isEmpty()) {
                return 'Brak';
            }
            return $departments->map(function($dept) {
                $odStr = $dept->pivot->od ?: 'brak';
                $doStr = $dept->pivot->do ?: 'aktualnie';
                return "{$dept->Nazwa} (od: {$odStr}, do: {$doStr})";
            })->sort()->implode(', ');
        };

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

        // Read header
        $header = fgetcsv($handle, 1000, ',');
        
        if (!$header || count($header) < 4) {
            fclose($handle);
            return back()->with('error', 'Niepoprawny format nagłówków CSV. Wymagane kolumny: imię_i_nazwisko, email, rola, status.');
        }

        // Standardize column names (remove spaces and lowercase)
        $header = array_map(function($h) {
            return trim(strtolower($h));
        }, $header);

        $idIdx = array_search('id', $header);
        $nameIdx = array_search('imię_i_nazwisko', $header);
        if ($nameIdx === false) $nameIdx = array_search('imie_i_nazwisko', $header);
        $emailIdx = array_search('email', $header);
        $roleIdx = array_search('rola', $header);
        $statusIdx = array_search('status', $header);
        
        $deptIdx = array_search('wydział', $header);
        if ($deptIdx === false) $deptIdx = array_search('wydzial', $header);
        if ($deptIdx === false) $deptIdx = array_search('wydziały', $header);
        if ($deptIdx === false) $deptIdx = array_search('wydzialy', $header);

        $odIdx = array_search('data_od', $header);
        if ($odIdx === false) $odIdx = array_search('od', $header);

        $doIdx = array_search('data_do', $header);
        if ($doIdx === false) $doIdx = array_search('do', $header);

        if ($nameIdx === false || $emailIdx === false || $roleIdx === false || $statusIdx === false) {
            fclose($handle);
            return back()->with('error', 'Nie znaleziono wymaganych kolumn w CSV (imię_i_nazwisko, email, rola, status).');
        }

        $importedCount = 0;
        $errors = [];
        
        // Group rows in memory by email to support multiple membership periods
        $groupedRows = [];
        $lineNum = 1;
        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $lineNum++;
            if (empty(array_filter($row))) continue; // Skip empty rows

            $email = trim($row[$emailIdx] ?? '');
            if (!$email) {
                $errors[] = "Wiersz {$lineNum}: Brak adresu email.";
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Wiersz {$lineNum}: Niepoprawny format email ({$email}).";
                continue;
            }

            $groupedRows[$email][] = [
                'row' => $row,
                'lineNum' => $lineNum
            ];
        }

        foreach ($groupedRows as $email => $items) {
            $firstItem = $items[0];
            $firstRow = $firstItem['row'];
            $lineNum = $firstItem['lineNum'];

            $id = $idIdx !== false ? trim($firstRow[$idIdx] ?? '') : '';
            $name = trim($firstRow[$nameIdx] ?? '');
            $userRole = trim(strtolower($firstRow[$roleIdx] ?? 'user'));
            $statusStr = trim(strtolower($firstRow[$statusIdx] ?? 'aktywny'));

            if (!$name) {
                $errors[] = "Wiersz {$lineNum}: Brak imienia i nazwiska.";
                continue;
            }

            if (!in_array($userRole, ['admin', 'mod', 'user', 'none'])) {
                $userRole = 'user';
            }

            $isActive = in_array($statusStr, ['aktywny', 'active', '1', 'tak']);

            // Find user by ID or by Email
            $user = null;
            if (!empty($id)) {
                $user = User::find($id);
                if (!$user) {
                    $errors[] = "Wiersz {$lineNum}: Użytkownik o ID {$id} nie istnieje w bazie.";
                    continue;
                }
            } else {
                $user = User::where('email', $email)->first();
            }

            // Gather all assignments for this user from grouped rows
            $assignments = [];
            foreach ($items as $item) {
                $r = $item['row'];
                $deptName = $deptIdx !== false ? trim($r[$deptIdx] ?? '') : '';
                $od = $odIdx !== false ? trim($r[$odIdx] ?? '') : '';
                $do = $doIdx !== false ? trim($r[$doIdx] ?? '') : '';

                if ($deptName) {
                    $dept = Departament::firstOrCreate(
                        ['Nazwa' => $deptName],
                        ['Description' => 'Wydział utworzony automatycznie podczas importu CSV.']
                    );
                    $assignments[] = [
                        'ID_Departament' => $dept->ID_Departament,
                        'od' => !empty($od) ? $od : null,
                        'do' => !empty($do) ? $do : null,
                    ];
                }
            }

            if ($user) {
                // Update existing user
                if ($user->email !== $email) {
                    if (User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
                        $errors[] = "Wiersz {$lineNum}: Adres email {$email} jest już zajęty przez innego użytkownika.";
                        continue;
                    }
                }

                $changesToLog = [];
                $oldDepts = $formatDeptsWithDates($user->departments);

                $fieldsToCompare = [
                    'name' => $name,
                    'email' => $email,
                    'role' => $userRole,
                    'is_active' => $isActive
                ];

                foreach ($fieldsToCompare as $field => $newVal) {
                    $oldVal = $user->getAttribute($field);
                    if ($field === 'is_active') {
                        $oldValBool = (bool)$oldVal;
                        $newValBool = (bool)$newVal;
                        if ($oldValBool !== $newValBool) {
                            $changesToLog[] = [
                                'field_name' => $field,
                                'old_value' => $oldValBool ? 'aktywne/włączone' : 'nieaktywne/wyłączone',
                                'new_value' => $newValBool ? 'aktywne/włączone' : 'nieaktywne/wyłączone',
                            ];
                        }
                    } else {
                        if ($oldVal !== $newVal) {
                            $changesToLog[] = [
                                'field_name' => $field,
                                'old_value' => $oldVal,
                                'new_value' => $newVal,
                            ];
                        }
                    }
                }

                $user->name = $name;
                $user->email = $email;
                if ($user->id !== auth()->id()) {
                    $user->role = $userRole;
                    $user->is_active = $isActive;
                }
                $user->save();

                // Re-assign departments
                $user->departments()->detach();
                foreach ($assignments as $assign) {
                    $user->departments()->attach($assign['ID_Departament'], [
                        'od' => $assign['od'],
                        'do' => $assign['do'],
                    ]);
                }

                $user->load('departments');
                $newDepts = $formatDeptsWithDates($user->departments);
                if ($oldDepts !== $newDepts) {
                    $changesToLog[] = [
                        'field_name' => 'departments',
                        'old_value' => $oldDepts,
                        'new_value' => $newDepts,
                    ];
                }

                foreach ($changesToLog as $log) {
                    \App\Models\UserChangeLog::create([
                        'user_id' => $user->id,
                        'editor_id' => auth()->id(),
                        'field_name' => $log['field_name'],
                        'old_value' => $log['old_value'],
                        'new_value' => $log['new_value'],
                    ]);
                }

                UserActivity::log('update_user', "Zaimportowano (aktualizacja): zaktualizowano dane użytkownika {$user->email} przez import CSV.");
                $importedCount++;

            } else {
                // Check if email already exists
                if (User::where('email', $email)->exists()) {
                    $errors[] = "Wiersz {$lineNum}: Użytkownik o adresie email {$email} już istnieje.";
                    continue;
                }

                // Create user
                $rawPassword = \Illuminate\Support\Str::random(12);
                $newUser = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($rawPassword),
                    'role' => $userRole,
                    'is_active' => $isActive,
                ]);

                // Assign departments
                foreach ($assignments as $assign) {
                    $newUser->departments()->attach($assign['ID_Departament'], [
                        'od' => $assign['od'],
                        'do' => $assign['do'],
                    ]);
                }

                // Log import activity
                UserActivity::log('import_user', "Zaimportowano (nowy): Utworzono użytkownika {$name} ({$email}) z wygenerowanym hasłem: {$rawPassword}");
                $importedCount++;
            }
        }

        fclose($handle);

        $msg = "Pomyślnie zaimportowano {$importedCount} użytkowników.";
        if (!empty($errors)) {
            return back()->with('success', $msg)->with('warnings', $errors);
        }

        return back()->with('success', $msg);
    }
}
