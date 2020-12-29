<?php

namespace RTippin\Messenger\Tests\stubs;

use Illuminate\Foundation\Auth\User;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\Traits\Messageable;
use RTippin\Messenger\Traits\Search;

class UserModel extends User implements MessengerProvider, Searchable
{
    use Messageable;
    use Search;

    protected $table = 'users';

    protected $guarded = [];
}
