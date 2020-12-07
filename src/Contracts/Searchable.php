<?php

namespace RTippin\Messenger\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Contracts\Searchable
 *
 * @mixin Model
 */
interface Searchable
{
    /**
     * Add this public static method on your providers to allow them to be searched
     * through a whereHasMorph from messengers table. We inject the builder, single
     * full search query string, and an array of the string exploded.
     *
     * @param Builder $query
     * @param string $search
     * @param array $searchItems
     * @return Builder
     */
    public static function getProviderSearchableBuilder(Builder $query,
                                                        string $search,
                                                        array $searchItems): Builder;
}