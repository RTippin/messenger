<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Ownerable;
use RTippin\Messenger\Database\Factories\FriendFactory;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Traits\HasOwner;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * @mixin Model|\Eloquent
 *
 * @property string $id
 * @property string|int $party_id
 * @property string $party_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MessengerProvider $party
 *
 * @method static FriendFactory factory(...$parameters)
 */
class Friend extends Model implements Ownerable
{
    use HasFactory,
        HasOwner,
        ScopesProvider,
        Uuids;

    /**
     * @var string
     */
    protected $table = 'friends';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    public $keyType = 'string';

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @return MorphTo|MessengerProvider
     */
    public function party(): MorphTo
    {
        return $this->morphTo()->withDefault(fn () => Messenger::getGhostProvider());
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return FriendFactory::new();
    }
}
