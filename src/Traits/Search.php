<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Search
{
    /**
     * Typical use case for searching users through the name or email columns.
     *
     * @param  Builder  $query
     * @param  string  $search
     * @param  array  $searchItems
     * @return void
     */
    public static function getProviderSearchableBuilder(Builder $query,
                                                        string $search,
                                                        array $searchItems): void
    {
        $query->where(function (Builder $query) use ($searchItems) {
            foreach ($searchItems as $item) {
                $query->orWhere('name', 'LIKE', "%$item%");
            }
        })->orWhere('email', '=', $search);
    }
}
