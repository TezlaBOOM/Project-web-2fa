<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PAccess extends Model
{
    protected $table = 'P_access';
    protected $fillable = ['user_id', 'p_modul_id', 'p_operacje_id'];

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
