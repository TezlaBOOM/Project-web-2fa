<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['nazwa', 'file_path', 'original_filename', 'p_modul_id'];

    public function module()
    {
        return $this->belongsTo(PModul::class, 'p_modul_id');
    }
}
