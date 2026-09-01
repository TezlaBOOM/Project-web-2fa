<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PAccess extends Model
{
    protected $table = 'P_access';
    protected $fillable = ['user_id', 'p_modul_id', 'p_operacje_id', 'valid_from', 'valid_to', 'login', 'uwagi'];

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

    /**
     * Zwraca wydziały pracownika, których okres zatrudnienia pokrywa się z okresem ważności uprawnienia.
     */
    public function getMatchingDepartments($user = null)
    {
        $user = $user ?? $this->user;
        if (!$user || !$user->departments) {
            return collect();
        }

        return $user->departments->filter(function ($dept) {
            $deptOd = $dept->pivot->od ? \Carbon\Carbon::parse($dept->pivot->od)->startOfDay() : null;
            $deptDo = $dept->pivot->do ? \Carbon\Carbon::parse($dept->pivot->do)->endOfDay() : null;

            $accessFrom = $this->valid_from ? $this->valid_from->startOfDay() : null;
            $accessTo   = $this->valid_to ? $this->valid_to->endOfDay() : null;

            $startCondition = (!$accessFrom || !$deptDo || $accessFrom->lte($deptDo));
            $endCondition   = (!$accessTo || !$deptOd || $accessTo->gte($deptOd));

            return $startCondition && $endCondition;
        });
    }
}
