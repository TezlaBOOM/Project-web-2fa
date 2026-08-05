<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PAccessHistory extends Model
{
    protected $table = 'p_access_history';

    protected $fillable = [
        'user_id',
        'p_modul_id',
        'p_operacje_id',
        'valid_from',
        'valid_to',
        'login',
        'uwagi',
        'action'
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

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
