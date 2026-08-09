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

        $users = $query->orderBy('name')->get();
        
        return view("Backend.{$role}.users.index", compact('users', 'search'));
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
            'departments' => 'nullable|array',
            'departments.*' => 'exists:Departament,ID_Departament',
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

        // Capture changes before saving
        $changesToLog = [];
        $oldDepts = $user->departments->pluck('Nazwa')->sort()->implode(', ');

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

        if (isset($validated['departments'])) {
            $user->departments()->sync($validated['departments']);
        } else {
            $user->departments()->detach();
        }

        // Compare departments after sync
        $user->load('departments');
        $newDepts = $user->departments->pluck('Nazwa')->sort()->implode(', ');
        if ($oldDepts !== $newDepts) {
            $changesToLog[] = [
                'field_name' => 'departments',
                'old_value' => $oldDepts ?: 'Brak',
                'new_value' => $newDepts ?: 'Brak',
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
            
            fputcsv($file, ['id', 'imię_i_nazwisko', 'email', 'rola', 'status', 'wydziały']);
            fputcsv($file, ['', 'Jan Kowalski', 'jan.kowalski@example.com', 'user', 'aktywny', 'Wydział IT, Wydział Finansów']);
            
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
            
            fputcsv($file, ['id', 'imię_i_nazwisko', 'email', 'rola', 'status', 'wydziały']);

            foreach ($users as $user) {
                $status = $user->is_active ? 'aktywny' : 'nieaktywny';
                $depts = $user->departments->pluck('Nazwa')->implode(', ');
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role,
                    $status,
                    $depts
                ]);
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
            return back()->with('error', 'Niepoprawny format nagłówków CSV. Wymagane kolumny: imię_i_nazwisko, email, rola, status, wydziały.');
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
        $deptsIdx = array_search('wydziały', $header);
        if ($deptsIdx === false) $deptsIdx = array_search('wydzialy', $header);

        if ($nameIdx === false || $emailIdx === false || $roleIdx === false || $statusIdx === false) {
            fclose($handle);
            return back()->with('error', 'Nie znaleziono wymaganych kolumn w CSV (imię_i_nazwisko, email, rola, status).');
        }

        $importedCount = 0;
        $errors = [];
        $lineNum = 1;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $lineNum++;
            if (empty(array_filter($row))) continue; // Skip empty rows

            $id = $idIdx !== false ? trim($row[$idIdx] ?? '') : '';
            $name = trim($row[$nameIdx] ?? '');
            $email = trim($row[$emailIdx] ?? '');
            $userRole = trim(strtolower($row[$roleIdx] ?? 'user'));
            $statusStr = trim(strtolower($row[$statusIdx] ?? 'aktywny'));
            $deptsStr = $deptsIdx !== false ? trim($row[$deptsIdx] ?? '') : '';

            if (!$name || !$email) {
                $errors[] = "Wiersz {$lineNum}: Brak imienia i nazwiska lub adresu email.";
                continue;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Wiersz {$lineNum}: Niepoprawny format email ({$email}).";
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

            if ($user) {
                // Update existing user
                if ($user->email !== $email) {
                    if (User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
                        $errors[] = "Wiersz {$lineNum}: Adres email {$email} jest już zajęty przez innego użytkownika.";
                        continue;
                    }
                }

                $changesToLog = [];
                $oldDepts = $user->departments->pluck('Nazwa')->sort()->implode(', ');

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

                // Assign departments
                $deptIds = [];
                if ($deptsStr) {
                    $deptNames = array_map('trim', explode(',', $deptsStr));
                    foreach ($deptNames as $dName) {
                        if (!$dName) continue;
                        $dept = Departament::firstOrCreate(
                            ['Nazwa' => $dName],
                            ['Description' => 'Wydział utworzony automatycznie podczas importu CSV.']
                        );
                        $deptIds[] = $dept->ID_Departament;
                    }
                }
                $user->departments()->sync($deptIds);

                $user->load('departments');
                $newDepts = $user->departments->pluck('Nazwa')->sort()->implode(', ');
                if ($oldDepts !== $newDepts) {
                    $changesToLog[] = [
                        'field_name' => 'departments',
                        'old_value' => $oldDepts ?: 'Brak',
                        'new_value' => $newDepts ?: 'Brak',
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
                if ($deptsStr) {
                    $deptNames = array_map('trim', explode(',', $deptsStr));
                    $deptIds = [];
                    foreach ($deptNames as $dName) {
                        if (!$dName) continue;
                        $dept = Departament::firstOrCreate(
                            ['Nazwa' => $dName],
                            ['Description' => 'Wydział utworzony automatycznie podczas importu CSV.']
                        );
                        $deptIds[] = $dept->ID_Departament;
                    }
                    if (!empty($deptIds)) {
                        $newUser->departments()->sync($deptIds);
                    }
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
