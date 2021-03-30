<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use RTippin\Messenger\Contracts\MessengerProvider;

trait ScopesProvider
{
    /**
     * Scope a query for belonging to the given provider.
     *
     * @param Builder $query
     * @param MessengerProvider $provider
     * @param string $morph
     * @param bool $not
     * @return Builder
     */
    public function scopeForProvider(Builder $query,
                                     MessengerProvider $provider,
                                     string $morph = 'owner',
                                     bool $not = false): Builder
    {
        return $query->where($this->concatBuilder($morph), $not ? '!=' : '=', get_class($provider).$provider->getKey());
    }

    /**
     * Scope a query not belonging to the given provider.
     *
     * @param Builder $query
     * @param MessengerProvider $provider
     * @param string $morph
     * @return Builder
     */
    public function scopeNotProvider(Builder $query,
                                     MessengerProvider $provider,
                                     string $morph = 'owner'): Builder
    {
        return $this->scopeForProvider($query, $provider, $morph, true);
    }

    /**
     * Scope a query for belonging to the given provider.
     *
     * @param Builder $query
     * @param string $relation
     * @param MessengerProvider $provider
     * @param string $morph
     * @return Builder
     */
    public function scopeHasProvider(Builder $query,
                                     string $relation,
                                     MessengerProvider $provider,
                                     string $morph = 'owner'): Builder
    {
        return $query->whereHas($relation, fn (Builder $query) => $this->scopeForProvider($query, $provider, $morph));
    }

    /**
     * @param string $morph
     * @return Expression
     */
    private function concatBuilder(string $morph): Expression
    {
        $query = "CONCAT({$morph}_type, {$morph}_id)";

        if (DB::getDriverName() === 'sqlite') {
            $query = "{$morph}_type || {$morph}_id";
        }

        return new Expression($query);
    }
}
