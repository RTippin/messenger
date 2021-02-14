<?php

namespace RTippin\Messenger\Tests\Fixtures;

use Illuminate\Foundation\Auth\User;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\Traits\Messageable;
use RTippin\Messenger\Traits\Search;
use RTippin\Messenger\Traits\Uuids;

class UserModelUuid extends User implements MessengerProvider, Searchable
{
    use Messageable;
    use Search;
    use Uuids;

    protected $table = 'users';

    protected $guarded = [];

    public $incrementing = false;

    public $keyType = 'string';
}
