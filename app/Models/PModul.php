<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PModul extends Model
{
    protected $table = 'P_modul';
    protected $fillable = ['nazwa', 'parent_id'];

    public function parent()
    {
        return $this->belongsTo(PModul::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PModul::class, 'parent_id');
    }

    public function getDepth()
    {
        $depth = 0;
        $parent = $this->parent;
        while ($parent) {
            $depth++;
            $parent = $parent->parent;
        }
        return $depth;
    }

    public function pAccesses()
    {
        return $this->hasMany(PAccess::class, 'p_modul_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'p_modul_id');
    }
}
