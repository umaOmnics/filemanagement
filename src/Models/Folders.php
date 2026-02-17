<?php

namespace Omnics\FileManagement\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folders extends Model
{
    use SoftDeletes;
    protected $table = 'folders';

    // -----------------------------
    // Relationships
    // -----------------------------

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    // -----------------------------
    // Scopes
    // -----------------------------

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeChildrenOf(Builder $query, int $parentId): Builder
    {
        return $query->where('parent_id', $parentId);
    }
}
