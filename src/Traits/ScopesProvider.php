<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Builder;
use RTippin\Messenger\Contracts\MessengerProvider;

trait ScopesProvider
{
    /**
     * Scope a query for belonging to the given provider.
     *
     * @param Builder $query
     * @param MessengerProvider $provider
     * @return Builder
     */
    public function scopeForProvider(Builder $query, MessengerProvider $provider): Builder
    {
        return $query->where(function(Builder $q) use ($provider) {
            return $q->where('owner_id', '=', $provider->getKey())
                ->where('owner_type', '=', get_class($provider));
        });
    }
}
