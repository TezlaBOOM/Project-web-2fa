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

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'departament_tag', 'ID_Departament', 'tag_id')
            ->withTimestamps();
    }

    /**
     * Synchronize department tags from a comma/semicolon separated string.
     */
    public function syncTagsFromString(?string $tagsString): void
    {
        $rawParts = preg_split('/[,;]+/', (string) $tagsString) ?: [];
        $uniqueNames = [];

        foreach ($rawParts as $part) {
            $trimmed = mb_substr(trim($part), 0, 100);
            if ($trimmed === '') {
                continue;
            }

            $lower = mb_strtolower($trimmed);
            if (!array_key_exists($lower, $uniqueNames)) {
                $uniqueNames[$lower] = $trimmed;
            }
        }

        $tagIds = [];
        foreach ($uniqueNames as $name) {
            $tag = Tag::firstOrCreate(['name' => $name]);
            $tagIds[] = $tag->id;
        }

        $this->tags()->sync($tagIds);
    }
}
