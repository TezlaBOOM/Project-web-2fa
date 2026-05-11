<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departament extends Model
{
    protected $table = 'Departament';
    protected $primaryKey = 'ID_Departament';

    public function getRouteKeyName(): string
    {
        return 'ID_Departament';
    }

    protected $fillable = [
        'Nazwa',
        'Description',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'DepartamentUsers', 'ID_Departament', 'ID_Users');
    }
}
