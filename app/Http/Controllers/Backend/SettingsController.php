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
        
        $allowedRoles = ['admin', 'mod', 'user', 'none'];
        if (!in_array($role, $allowedRoles)) {
            $role = 'none';
        }
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        
        return view("Backend.{$role}.settings.index", compact('settings'));
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
}
