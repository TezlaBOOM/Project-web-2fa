<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Departament;
use App\Models\PAccess;
use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

#[Fillable(['name', 'email', 'password', 'role', 'two_factor_enabled', 'two_factor_code', 'two_factor_expires_at', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function departments()
    {
        return $this->belongsToMany(Departament::class, 'DepartamentUsers', 'ID_Users', 'ID_Departament');
    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function pAccesses()
    {
        return $this->hasMany(PAccess::class, 'user_id');
    }

    public function hasActiveAccess($moduleName, $operationName)
    {
        if ($this->role === 'admin') {
            return true;
        }

        $accesses = $this->pAccesses()
            ->whereHas('modul', function ($q) use ($moduleName) {
                $q->where('nazwa', $moduleName);
            })
            ->whereHas('operacja', function ($q) use ($operationName) {
                $q->where('nazwa', $operationName);
            })
            ->get();

        foreach ($accesses as $access) {
            if ($access->isValid()) {
                return true;
            }
        }

        return false;
    }
    public function sendPasswordResetNotification($token): void
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

        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $this->email,
        ], false));

        Mail::send('emails.password_reset', ['resetUrl' => $resetUrl], function ($message) {
            $message->to($this->email)
                    ->subject('Resetowanie hasła');
        });
    }
}
