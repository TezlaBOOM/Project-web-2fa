<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PAccess extends Model
{
    protected $table = 'P_access';
    protected $fillable = ['user_id', 'p_modul_id', 'p_operacje_id', 'valid_from', 'valid_to'];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function isValid()
    {
        $today = now()->startOfDay();

        if ($this->valid_from && $today->lt($this->valid_from->startOfDay())) {
            return false;
        }
        if ($this->valid_to && $today->gt($this->valid_to->endOfDay())) {
            return false;
        }
        return true;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function modul()
    {
        return $this->belongsTo(PModul::class, 'p_modul_id');
    }

    public function operacja()
    {
        return $this->belongsTo(POperacje::class, 'p_operacje_id');
    }
}
