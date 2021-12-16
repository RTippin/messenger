<?php

namespace RTippin\Messenger\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Traits\Messageable;

/**
 * @mixin Model|\Eloquent
 *
 * @method static CompanyModelFactory factory(...$parameters)
 */
class CompanyModel extends User implements MessengerProvider
{
    use HasFactory,
        Messageable;

    public static ?string $alias = 'company';
    public static bool $searchable = true;
    public static bool $friendable = true;
    public static bool $devices = true;
    public static array $cantMessage = [];
    public static array $cantSearch = [];
    public static array $cantFriend = [];

    public function __construct(array $attributes = [])
    {
        $this->setKeyType(Messenger::shouldUseUuids() ? 'string' : 'int');

        $this->setIncrementing(! Messenger::shouldUseUuids());

        parent::__construct($attributes);
    }

    protected $table = 'companies';

    protected $guarded = [];

    protected $hidden = [
        'password',
        'company_email',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (Messenger::shouldUseUuids()) {
                $model->{$model->getKeyName()} = Str::orderedUuid()->toString();
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
            'default_avatar' => '/path/to/company.png',
            'cant_message_first' => self::$cantMessage,
            'cant_search' => self::$cantSearch,
            'cant_friend' => self::$cantFriend,
        ];
    }

    public static function reset(): void
    {
        self::$alias = 'company';
        self::$searchable = true;
        self::$friendable = true;
        self::$devices = true;
        self::$cantMessage = [];
        self::$cantSearch = [];
        self::$cantFriend = [];
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
