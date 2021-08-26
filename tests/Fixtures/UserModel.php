<?php

namespace RTippin\Messenger\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Messageable;
use RTippin\Messenger\Traits\Search;

class UserModel extends User implements MessengerProvider
{
    use Messageable;
    use Search;
    use HasFactory;

    public static string $alias = 'user';
    public static bool $searchable = true;
    public static bool $friendable = true;
    public static bool $devices = true;
    public static array $cantMessage = [];
    public static array $cantSearch = [];
    public static array $cantFriend = [];

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
        'email_verified_at',
        'remember_token',
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

    public static function getProviderSettings(): array
    {
        return [
            'alias' => self::$alias,
            'searchable' => self::$searchable,
            'friendable' => self::$friendable,
            'devices' => self::$devices,
            'default_avatar' => '/path/to/user.png',
            'cant_message_first' => self::$cantMessage,
            'cant_search' => self::$cantSearch,
            'cant_friend' => self::$cantFriend,
        ];
    }

    public static function reset(): void
    {
        self::$alias = 'user';
        self::$searchable = true;
        self::$friendable = true;
        self::$devices = true;
        self::$cantMessage = [];
        self::$cantSearch = [];
        self::$cantFriend = [];
    }

    protected static function newFactory(): Factory
    {
        return UserModelFactory::new();
    }
}
