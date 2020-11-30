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
     * @param Builder $query
     * @param string $search
     * @param array $searchItems
     * @return Builder
     */
    public static function getProviderSearchableBuilder(Builder $query,
                                                        string $search,
                                                        array $searchItems);
}