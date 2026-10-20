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
        return $this->belongsToMany(Departament::class, 'DepartamentUsers', 'ID_Users', 'ID_Departament')
                    ->withPivot('od', 'do')
                    ->withTimestamps();
    }

    /**
     * Zwraca wydziały użytkownika posortowane:
     * najpierw wydział najświeższy (aktywny / z najnowszą datą),
     * a następnie pozostałe w kolejności od najmłodszego do najstarszego.
     */
    public function sortedDepartments()
    {
        $today = date('Y-m-d');

        return $this->departments->sort(function ($a, $b) use ($today) {
            $isExpiredA = $a->pivot->do && $a->pivot->do < $today;
            $isExpiredB = $b->pivot->do && $b->pivot->do < $today;

            $isFutureA = $a->pivot->od && $a->pivot->od > $today;
            $isFutureB = $b->pivot->od && $b->pivot->od > $today;

            // 1. Aktywne (nie wygasłe i nie przyszłe) mają pierwszeństwo
            $isActiveA = !$isExpiredA && !$isFutureA;
            $isActiveB = !$isExpiredB && !$isFutureB;

            if ($isActiveA && !$isActiveB) {
                return -1;
            }
            if (!$isActiveA && $isActiveB) {
                return 1;
            }

            // 2. Jeśli żaden nie jest aktywny, ale jeden jest w przyszłości, a drugi wygasły
            if ($isFutureA && $isExpiredB) {
                return -1;
            }
            if ($isExpiredA && $isFutureB) {
                return 1;
            }

            // 3. Data rozpoczęcia (od) - najmłodszy / najnowszy pierwszy (DESC)
            $odA = $a->pivot->od ?? '';
            $odB = $b->pivot->od ?? '';
            if ($odA !== $odB) {
                return strcmp($odB, $odA);
            }

            // 4. Data zakończenia (do) - najdłużej ważny / najpóźniejszy koniec pierwszy (DESC)
            $doA = $a->pivot->do ?? '9999-12-31';
            $doB = $b->pivot->do ?? '9999-12-31';
            if ($doA !== $doB) {
                return strcmp($doB, $doA);
            }

            // 5. Data utworzenia wpisu (nowo przypisany pierwszy -> DESC)
            $createdA = $a->pivot->created_at ? $a->pivot->created_at->timestamp : 0;
            $createdB = $b->pivot->created_at ? $b->pivot->created_at->timestamp : 0;
            if ($createdA !== $createdB) {
                return $createdB <=> $createdA;
            }

            return $b->ID_Departament <=> $a->ID_Departament;
        })->values();
    }

    public function getSortedDepartmentsAttribute()
    {
        return $this->sortedDepartments();
    }

    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    public function pAccesses()
    {
        return $this->hasMany(PAccess::class, 'user_id');
    }

    public function changeLogs()
    {
        return $this->hasMany(UserChangeLog::class, 'user_id');
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
