<?php

namespace ArtisanBR\Goodies\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ?string $userRelationKey
 */
trait BelongsToUser
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), self::$userRelationKey ?? config('goodies.user_relation_key', 'user_id'));
    }

    public function owner(): BelongsTo
    {
        return $this->user();
    }
}
