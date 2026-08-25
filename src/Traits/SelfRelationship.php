<?php

namespace ArtisanBR\Goodies\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait SelfRelationship
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    // region Scopes
    #[Scope]
    protected function whereParent(Builder $query, $parentId = null): Builder
    {
        return $query->where('parent_id', $parentId);
    }

    #[Scope]
    protected function whereIsParent(Builder $query): Builder
    {
        return $query->where('parent_id', null);
    }
    // endregion
}
