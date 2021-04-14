<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Database\Factories\PendingFriendFactory;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * App\Models\Friend\PendingFriend.
 *
 * @property string $id
 * @property string|int $sender_id
 * @property string $sender_type
 * @property string|int $recipient_id
 * @property string $recipient_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @mixin Model|\Eloquent
 * @property-read MessengerProvider $recipient
 * @property-read MessengerProvider $sender
 * @method static Builder|PendingFriend hasProvider(string $relation, MessengerProvider $provider)
 * @method static Builder|PendingFriend forProvider(MessengerProvider $provider, string $morph = 'owner')
 * @method static Builder|PendingFriend notProvider(MessengerProvider $provider, string $morph = 'owner')
 */
class PendingFriend extends Model
{
    use HasFactory;
    use Uuids;
    use ScopesProvider;

    /**
     * @var string
     */
    protected $table = 'pending_friends';

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
    public function sender()
    {
        return $this->morphTo()->withDefault(function () {
            return Messenger::getGhostProvider();
        });
    }

    /**
     * @return MorphTo|MessengerProvider
     */
    public function recipient()
    {
        return $this->morphTo()->withDefault(function () {
            return Messenger::getGhostProvider();
        });
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return PendingFriendFactory::new();
    }
}
