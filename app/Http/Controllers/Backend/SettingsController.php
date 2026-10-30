<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\UserActivity;

class SettingsController extends Controller
{
    public function index()
    {
        $role = auth()->user()->role ?? 'none';
        
        $allowedRoles = ['admin', 'mod', 'view', 'user', 'none'];
        if (!in_array($role, $allowedRoles)) {
            $role = 'none';
        }
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        
        $viewRole = in_array($role, ['admin', 'view']) ? 'admin' : $role;
        return view("Backend.{$viewRole}.settings.index", compact('settings'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Obecne hasło jest nieprawidłowe.']);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        UserActivity::log('update_password', 'Zmieniono hasło do konta');

        return back()->with('success', 'Hasło zostało pomyślnie zmienione.');
    }

    public function toggle2fa(Request $request)
    {
        $user = Auth::user();
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $force2fa = isset($settings['force_2fa_mod_user']) ? (bool) $settings['force_2fa_mod_user'] : false;
        
        if ($force2fa && in_array($user->role, ['mod', 'user'])) {
            return back()->withErrors(['Uwierzytelnianie dwuskładnikowe jest wymuszone przez administratora i nie może zostać wyłączone.']);
        }

        $user->two_factor_enabled = $request->has('two_factor_enabled');
        $user->save();

        $status = $user->two_factor_enabled ? 'włączone' : 'wyłączone';
        UserActivity::log('toggle_2fa', "Uwierzytelnianie dwuskładnikowe zostało $status");
        return back()->with('success', "Uwierzytelnianie dwuskładnikowe zostało $status.");
    }

    public function logon()
    {
        $role = auth()->user()->role ?? 'none';
        
        if ($role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        return view('Backend.admin.settings.logon', compact('settings'));
    }

    public function updateLogon(Request $request)
    {
        $role = auth()->user()->role ?? 'none';
        
        if ($role !== 'admin') {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'min_password_length' => 'required|integer|min:4',
            'max_password_length' => 'required|integer|gte:min_password_length',
            'require_special_character' => 'nullable|boolean',
            'enable_2fa' => 'nullable|boolean',
            'force_2fa_mod_user' => 'nullable|boolean',
            'smtp_host' => 'nullable|string',
            'smtp_port' => 'nullable|string',
            'smtp_username' => 'nullable|string',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|string',
            'smtp_from_address' => 'nullable|email',
            'smtp_from_name' => 'nullable|string',
            'two_factor_expiration_time' => 'nullable|integer|min:1',
            'activity_log_retention_days' => 'nullable|integer|min:1',
        ]);

        // Convert checkboxes to boolean values (0 or 1)
        $validated['require_special_character'] = $request->has('require_special_character') ? 1 : 0;
        $validated['enable_2fa'] = $request->has('enable_2fa') ? 1 : 0;
        $validated['force_2fa_mod_user'] = $request->has('force_2fa_mod_user') ? 1 : 0;

        foreach ($validated as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        // Jeśli opcja wymuszenia 2FA jest włączona, zaktualizuj wszystkich modów i userów w bazie
        if ($validated['force_2fa_mod_user']) {
            \App\Models\User::whereIn('role', ['mod', 'user'])->update(['two_factor_enabled' => 1]);
        }

        UserActivity::log('update_settings', 'Zaktualizowano ustawienia logowania i 2FA');

        return back()->with('success', 'Ustawienia logowania i 2FA zostały zaktualizowane.');
    }

    public function exportDatabase()
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $conn = \Illuminate\Support\Facades\DB::connection();
        $driver = $conn->getDriverName();
        $dbName = $conn->getDatabaseName();
        $pdo = $conn->getPdo();

        $cleanDbName = preg_replace('/[^a-zA-Z0-9_-]/', '_', basename($dbName));
        $filename = 'backup_' . ($cleanDbName ?: 'database') . '_' . date('Y-m-d_H-i-s') . '.sql';

        $headers = [
            'Content-Type' => 'application/sql; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        UserActivity::log('export_database', 'Wyeksportowano pełną kopię bazy danych');

        return response()->stream(function() use ($conn, $driver, $dbName, $pdo) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "-- ========================================================\n");
            fwrite($handle, "-- Kopia zapasowa bazy danych: {$dbName}\n");
            fwrite($handle, "-- Data utworzenia: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Silnik bazy danych: {$driver}\n");
            fwrite($handle, "-- ========================================================\n\n");

            if ($driver === 'mysql') {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
                fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
                fwrite($handle, "SET NAMES utf8mb4;\n\n");
            } elseif ($driver === 'sqlite') {
                fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");
            }

            if ($driver === 'mysql') {
                $tablesRaw = $conn->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
                $key = 'Tables_in_' . $dbName;
                $tables = array_map(function($t) use ($key) {
                    return ((array) $t)[$key] ?? array_values((array) $t)[0];
                }, $tablesRaw);
            } elseif ($driver === 'sqlite') {
                $tablesRaw = $conn->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'sqlite_sequence'");
                $tables = array_map(fn($t) => $t->name, $tablesRaw);
            } else {
                $tables = \Illuminate\Support\Facades\Schema::getTableListing();
            }

            foreach ($tables as $table) {
                $createSql = '';
                if ($driver === 'mysql') {
                    $createRaw = $conn->selectOne('SHOW CREATE TABLE `' . $table . '`');
                    $createSql = ((array) $createRaw)['Create Table'] ?? '';
                } elseif ($driver === 'sqlite') {
                    $createRaw = $conn->selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
                    $createSql = $createRaw->sql ?? '';
                }

                if ($createSql) {
                    fwrite($handle, "-- --------------------------------------------------------\n");
                    fwrite($handle, "-- Struktura tabeli `{$table}`\n");
                    fwrite($handle, "-- --------------------------------------------------------\n");
                    fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                    fwrite($handle, $createSql . ";\n\n");
                }

                $first = true;
                $columns = [];
                $valuesList = [];
                $colsSql = '';

                foreach ($conn->table($table)->cursor() as $row) {
                    $rowArr = (array) $row;
                    if ($first) {
                        $columns = array_keys($rowArr);
                        $escapedCols = array_map(fn($c) => "`{$c}`", $columns);
                        $colsSql = implode(', ', $escapedCols);
                        fwrite($handle, "-- Zrzut danych dla tabeli `{$table}`\n");
                        if ($driver === 'mysql') {
                            fwrite($handle, "LOCK TABLES `{$table}` WRITE;\n");
                        }
                        $first = false;
                    }

                    $rowValues = [];
                    foreach ($columns as $col) {
                        $v = $rowArr[$col] ?? null;
                        if ($v === null) {
                            $rowValues[] = 'NULL';
                        } elseif (is_int($v) || is_float($v)) {
                            $rowValues[] = $v;
                        } elseif (is_bool($v)) {
                            $rowValues[] = $v ? '1' : '0';
                        } else {
                            $rowValues[] = $pdo->quote($v);
                        }
                    }
                    $valuesList[] = '(' . implode(', ', $rowValues) . ')';

                    if (count($valuesList) >= 100) {
                        fwrite($handle, "INSERT INTO `{$table}` ({$colsSql}) VALUES\n" . implode(",\n", $valuesList) . ";\n");
                        $valuesList = [];
                    }
                }

                if (!empty($valuesList)) {
                    fwrite($handle, "INSERT INTO `{$table}` ({$colsSql}) VALUES\n" . implode(",\n", $valuesList) . ";\n");
                }

                if (!$first && $driver === 'mysql') {
                    fwrite($handle, "UNLOCK TABLES;\n");
                }

                if (!$first) {
                    fwrite($handle, "\n");
                }
            }

            if ($driver === 'mysql') {
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            } elseif ($driver === 'sqlite') {
                fwrite($handle, "PRAGMA foreign_keys = ON;\n");
            }

            fwrite($handle, "\n-- Zrzut bazy danych zakończony pomyślnie.\n");
            fclose($handle);
        }, 200, $headers);
    }

    public function importDatabase(Request $request)
    {
        $role = auth()->user()->role ?? 'none';
        if ($role !== 'admin') {
            abort(403, 'Brak dostępu.');
        }

        $request->validate([
            'database_file' => [
                'required',
                'file',
                'max:102400', // max 100MB
                function ($attribute, $value, $fail) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if ($ext !== 'sql') {
                        $fail('Plik musi posiadać rozszerzenie .sql.');
                    }
                },
            ],
        ]);

        $file = $request->file('database_file');
        if (!$file || !$file->isValid()) {
            return back()->withErrors(['database_file' => 'Wystąpił problem podczas przesyłania pliku bazy danych.']);
        }

        $content = file_get_contents($file->getRealPath());
        if (trim($content) === '') {
            return back()->withErrors(['database_file' => 'Przesłany plik SQL jest pusty.']);
        }

        $conn = \Illuminate\Support\Facades\DB::connection();
        $driver = $conn->getDriverName();
        $adminId = auth()->id();
        $sessionId = session()->getId();
        $sessionData = session()->all();

        try {
            if ($driver === 'mysql') {
                \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            } elseif ($driver === 'sqlite') {
                \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = OFF;');
            }

            \Illuminate\Support\Facades\DB::unprepared($content);

            // Re-sync session in database if database session handler is active and table exists
            if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                \Illuminate\Support\Facades\DB::table('sessions')->updateOrInsert(
                    ['id' => $sessionId],
                    [
                        'payload' => base64_encode(serialize($sessionData)),
                        'last_activity' => time(),
                        'user_id' => $adminId,
                        'ip_address' => request()->ip(),
                        'user_agent' => substr((string) request()->userAgent(), 0, 500),
                    ]
                );
            }

            UserActivity::log('import_database', 'Zaimportowano całą bazę danych z pliku: ' . $file->getClientOriginalName());

            return back()->with('success', 'Baza danych została pomyślnie zaimportowana.');
        } catch (\Throwable $e) {
            return back()->withErrors(['database_file' => 'Błąd podczas importu bazy danych: ' . $e->getMessage()]);
        } finally {
            if ($driver === 'mysql') {
                \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($driver === 'sqlite') {
                \Illuminate\Support\Facades\DB::statement('PRAGMA foreign_keys = ON;');
            }
        }
    }
}
