<?php

namespace RTippin\Messenger\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\Traits\Messageable;

class CompanyModel extends User implements MessengerProvider, Searchable
{
    use Messageable;
    use HasFactory;

    protected $table = 'companies';

    protected $guarded = [];

    public function name(): string
    {
        return strip_tags(ucwords($this->company_name));
    }

    public function getAvatarColumn(): string
    {
        return 'avatar';
    }

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

    protected static function newFactory(): Factory
    {
        return CompanyModelFactory::new();
    }
}
