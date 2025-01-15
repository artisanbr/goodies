<?php

namespace ArtisanBR\Goodies\Observers\BelongsTo;

use Illuminate\Database\Eloquent\Model;

class BelongsToUserObserver
{
    public function creating(Model $model): void
    {
        $model->setAttribute($model::$userRelationKey ?? config('goodies.user_relation_key', 'user_id'), auth()->user()->id);
    }
}
