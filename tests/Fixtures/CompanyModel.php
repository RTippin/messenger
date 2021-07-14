<?php

namespace RTippin\Messenger\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\Traits\Messageable;

class CompanyModel extends User implements MessengerProvider, Searchable
{
    use Messageable;
    use HasFactory;

    public function __construct(array $attributes = [])
    {
        $this->setKeyType(config('messenger.provider_uuids') ? 'string' : 'int');

        $this->setIncrementing(! config('messenger.provider_uuids'));

        parent::__construct($attributes);
    }

    protected $table = 'companies';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'email',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (config('messenger.provider_uuids')) {
                $model->id = Str::orderedUuid()->toString();
            }
        });
    }

    public function getProviderName(): string
    {
        return strip_tags(ucwords($this->company_name));
    }

    public function getProviderAvatarColumn(): string
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
