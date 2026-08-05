<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserChangeLog extends Model
{
    protected $table = 'user_change_logs';

    protected $fillable = [
        'user_id',
        'editor_id',
        'field_name',
        'old_value',
        'new_value'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'editor_id');
    }

    /**
     * Zwraca przyjazną polską nazwę dla edytowanego pola.
     */
    public function getFriendlyFieldName()
    {
        $translations = [
            'name'               => 'Imię i nazwisko',
            'email'              => 'Adres e-mail',
            'role'               => 'Rola',
            'is_active'          => 'Stan konta (aktywne)',
            'two_factor_enabled' => 'Dwuetapowe uwierzytelnianie (2FA)',
            'password'           => 'Hasło logowania',
            'departments'        => 'Przypisane wydziały',
        ];

        return $translations[$this->field_name] ?? $this->field_name;
    }
}
