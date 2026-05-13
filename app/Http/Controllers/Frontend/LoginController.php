<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use App\Mail\TwoFactorCodeMail;
use App\Models\Setting;
use App\Models\User;

class LoginController extends Controller
{
    /**
     * Obsługa żądania logowania.
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::validate($credentials)) {
            $user = Auth::getProvider()->retrieveByCredentials($credentials);

            $settings = Setting::pluck('value', 'key')->toArray();
            $global2faEnabled = isset($settings['enable_2fa']) ? (bool) $settings['enable_2fa'] : false;

            if ($global2faEnabled && $user->two_factor_enabled) {
                // Generate 2FA code
                $code = rand(100000, 999999);
                $expirationTime = isset($settings['two_factor_expiration_time']) ? (int) $settings['two_factor_expiration_time'] : 5;
                
                $user->two_factor_code = $code;
                $user->two_factor_expires_at = now()->addMinutes($expirationTime);
                $user->save();

                // Setup SMTP dynamically if available
                if (!empty($settings['smtp_host'])) {
                    Config::set('mail.default', 'smtp');
                    Config::set('mail.mailers.smtp.host', $settings['smtp_host']);
                    Config::set('mail.mailers.smtp.port', $settings['smtp_port']);
                    Config::set('mail.mailers.smtp.username', $settings['smtp_username']);
                    Config::set('mail.mailers.smtp.password', $settings['smtp_password']);
                    Config::set('mail.mailers.smtp.encryption', $settings['smtp_encryption']);
                    Config::set('mail.from.address', $settings['smtp_from_address']);
                    Config::set('mail.from.name', $settings['smtp_from_name']);
                }

                try {
                    Mail::to($user->email)->send(new TwoFactorCodeMail($code));
                } catch (\Exception $e) {
                    // Log error or handle failure
                    return back()->withErrors(['email' => 'Błąd podczas wysyłania kodu 2FA. Skontaktuj się z administratorem.']);
                }

                $request->session()->put('2fa:user:id', $user->id);

                return redirect()->route('login.2fa')->with('success', 'Wysłaliśmy kod weryfikacyjny na Twój adres e-mail.');
            }

            // Normal login if 2FA not enabled
            Auth::login($user);
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        return back()->withErrors([
            'email' => 'Podane dane logowania są nieprawidłowe.',
        ])->onlyInput('email');
    }

    /**
     * Wyświetla formularz 2FA.
     */
    public function show2faForm(Request $request)
    {
        if (!$request->session()->has('2fa:user:id')) {
            return redirect()->route('login');
        }

        return view('Frontend.2fa');
    }

    /**
     * Weryfikuje kod 2FA.
     */
    public function verify2fa(Request $request)
    {
        $request->validate([
            'two_factor_code' => 'required|numeric',
        ]);

        $userId = $request->session()->get('2fa:user:id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user || $user->two_factor_code !== $request->two_factor_code) {
            return back()->with('error', 'Wprowadzony kod jest nieprawidłowy.');
        }

        if (now()->greaterThan($user->two_factor_expires_at)) {
            // Kod wygasł, zresetuj
            $user->two_factor_code = null;
            $user->two_factor_expires_at = null;
            $user->save();
            $request->session()->forget('2fa:user:id');

            return redirect()->route('login')->withErrors(['email' => 'Kod weryfikacyjny wygasł. Zaloguj się ponownie.']);
        }

        // Sukces
        $user->two_factor_code = null;
        $user->two_factor_expires_at = null;
        $user->save();

        Auth::login($user);
        $request->session()->forget('2fa:user:id');
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    /**
     * Wylogowanie użytkownika.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
