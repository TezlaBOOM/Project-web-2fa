<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use App\Models\Setting;
use App\Models\User;

class PasswordResetController extends Controller
{
    /**
     * Wyświetla formularz żądania resetu hasła.
     */
    public function showForgotForm()
    {
        return view('Frontend.forgot-password');
    }

    /**
     * Wysyła link resetujący hasło na podany adres e-mail.
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Adres e-mail jest wymagany.',
            'email.email'    => 'Podaj prawidłowy adres e-mail.',
        ]);

        $this->applySmtpFromSettings();

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Link do resetowania hasła został wysłany na podany adres e-mail.');
        }

        return back()->withErrors([
            'email' => 'Nie znaleziono konta z tym adresem e-mail.',
        ])->onlyInput('email');
    }

    /**
     * Wyświetla formularz ustawienia nowego hasła.
     */
    public function showResetForm(Request $request, string $token)
    {
        return view('Frontend.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Resetuje hasło użytkownika.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required',
            'email'                 => ['required', 'email'],
            'password'              => ['required', 'min:8', 'confirmed'],
        ], [
            'token.required'            => 'Token resetowania jest nieprawidłowy.',
            'email.required'            => 'Adres e-mail jest wymagany.',
            'email.email'               => 'Podaj prawidłowy adres e-mail.',
            'password.required'         => 'Nowe hasło jest wymagane.',
            'password.min'              => 'Hasło musi mieć co najmniej 8 znaków.',
            'password.confirmed'        => 'Potwierdzenie hasła nie zgadza się.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('success', 'Hasło zostało pomyślnie zmienione. Możesz się teraz zalogować.');
        }

        return back()->withErrors([
            'email' => 'Link resetujący jest nieprawidłowy lub wygasł. Spróbuj ponownie.',
        ])->onlyInput('email');
    }

    /**
     * Konfiguruje SMTP dynamicznie z ustawień bazy danych (tak jak w LoginController).
     */
    private function applySmtpFromSettings(): void
    {
        $settings = Setting::pluck('value', 'key')->toArray();

        if (!empty($settings['smtp_host'])) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host',       $settings['smtp_host']);
            Config::set('mail.mailers.smtp.port',       $settings['smtp_port'] ?? 587);
            Config::set('mail.mailers.smtp.username',   $settings['smtp_username'] ?? null);
            Config::set('mail.mailers.smtp.password',   $settings['smtp_password'] ?? null);
            Config::set('mail.mailers.smtp.encryption', $settings['smtp_encryption'] ?? 'tls');
            Config::set('mail.from.address',            $settings['smtp_from_address'] ?? 'no-reply@example.com');
            Config::set('mail.from.name',               $settings['smtp_from_name'] ?? config('app.name'));
        }
    }
}
