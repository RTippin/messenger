<?php

namespace RTippin\Messenger\Tests\stubs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\Traits\Messageable;
use RTippin\Messenger\Traits\Uuids;

class CompanyModelUuid extends User implements MessengerProvider, Searchable
{
    use Messageable;
    use Uuids;

    protected $table = 'companies';

    protected $guarded = [];

    public $incrementing = false;

    public $keyType = 'string';

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
}
