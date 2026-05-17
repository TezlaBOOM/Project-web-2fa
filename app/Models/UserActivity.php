<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserActivity extends Model
{
    protected $fillable = ['user_id', 'action', 'description', 'ip_address'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function log($action, $description = null)
    {
        self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);

        if (rand(1, 20) === 1) {
            self::prune();
        }
    }

    public static function prune()
    {
        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
        $days = (int) ($settings['activity_log_retention_days'] ?? 14);
        if ($days < 1) $days = 14;

        self::where('created_at', '<', now()->subDays($days))->delete();
    }
}
