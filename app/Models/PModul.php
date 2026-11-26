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

    public function getAllDescendantIds()
    {
        $ids = [$this->id];
        $children = $this->children()->get();
        foreach ($children as $child) {
            $ids = array_merge($ids, $child->getAllDescendantIds());
        }
        return array_unique($ids);
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

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'p_modul_tag', 'p_modul_id', 'tag_id')
            ->withTimestamps();
    }

    /**
     * Synchronize module tags from a comma/semicolon separated string.
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

    public function getFullPathAttribute()
    {
        $path = [$this->nazwa];
        $parent = $this->parent;
        while ($parent) {
            array_unshift($path, $parent->nazwa);
            $parent = $parent->parent;
        }
        return implode(' > ', $path);
    }
}
