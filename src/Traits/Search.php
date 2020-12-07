<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Search
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
                                                        array $searchItems): Builder
    {
        return $query->where(function(Builder $query) use ($search, $searchItems){
            foreach($searchItems as $item){
                $query->orWhere('name', 'LIKE', "%{$item}%");
            }
        })->orWhere('email', '=', $search);
    }
}