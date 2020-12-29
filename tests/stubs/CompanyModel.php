<?php

namespace RTippin\Messenger\Tests\stubs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\Traits\Messageable;

class CompanyModel extends User implements MessengerProvider, Searchable
{
    use Messageable;

    protected $table = 'companies';

    protected $guarded = [];

    /**
     * Format and return your provider name here.
     * ex: $this->first . ' ' . $this->last.
     *
     * @return string
     */
    public function name(): string
    {
        return strip_tags(ucwords($this->company_name));
    }

    /**
     * The column name your providers avatar is stored in the database as.
     *
     * @return string
     */
    public function getAvatarColumn(): string
    {
        return 'avatar';
    }

    /**
     * Search for companies
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
        return $query->where(function (Builder $query) use ($searchItems) {
            foreach ($searchItems as $item) {
                $query->orWhere('company_name', 'LIKE', "%{$item}%");
            }
        })->orWhere('company_email', '=', $search);
    }
}