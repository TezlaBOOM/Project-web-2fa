<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class POperacje extends Model
{
    protected $table = 'P_operacje';
    protected $fillable = ['nazwa'];

    public function pAccesses()
    {
        return $this->hasMany(PAccess::class, 'p_operacje_id');
    }
}
