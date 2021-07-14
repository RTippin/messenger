<?php

namespace RTippin\Messenger\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\Traits\Messageable;
use RTippin\Messenger\Traits\Search;

class UserModel extends User implements MessengerProvider, Searchable
{
    use Messageable;
    use Search;
    use HasFactory;

    public function __construct(array $attributes = [])
    {
        $this->setKeyType(config('messenger.provider_uuids') ? 'string' : 'int');

        $this->setIncrementing(! config('messenger.provider_uuids'));

        parent::__construct($attributes);
    }

    protected $table = 'users';

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

    protected static function newFactory(): Factory
    {
        return UserModelFactory::new();
    }
}
