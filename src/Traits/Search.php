<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Search
{
    /**
     * @param Builder $query
     * @param string $search
     * @param array $searchItems
     * @return Builder
     */
    public static function getProviderSearchableBuilder(Builder $query,
                                                        string $search,
                                                        array $searchItems)
    {
        return $query->where(function(Builder $query) use ($search, $searchItems){
            foreach($searchItems as $item){
                $query->orWhere('name', 'LIKE', "%{$item}%");
            }
        })->orWhere('email', '=', $search);
    }
}