<?php

namespace RTippin\Messenger\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\Traits\Messageable;
use RTippin\Messenger\Traits\Search;

class UserModel extends User implements MessengerProvider, Searchable
{
    use Messageable;
    use Search;
    use HasFactory;

    protected $table = 'users';

    protected $guarded = [];

    protected static function newFactory(): Factory
    {
        return UserModelFactory::new();
    }
}
