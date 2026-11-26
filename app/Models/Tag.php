<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [
        'name',
    ];

    public function departments()
    {
        return $this->belongsToMany(Departament::class, 'departament_tag', 'tag_id', 'ID_Departament')
            ->withTimestamps();
    }

    public function modules()
    {
        return $this->belongsToMany(PModul::class, 'p_modul_tag', 'tag_id', 'p_modul_id')
            ->withTimestamps();
    }
}
