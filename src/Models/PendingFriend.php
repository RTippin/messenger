<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Database\Factories\PendingFriendFactory;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * @mixin Model|\Eloquent
 *
 * @property string $id
 * @property string|int $sender_id
 * @property string $sender_type
 * @property string|int $recipient_id
 * @property string $recipient_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MessengerProvider $recipient
 * @property-read MessengerProvider $sender
 *
 * @method static PendingFriendFactory factory(...$parameters)
 */
class PendingFriend extends Model
{
    use HasFactory,
        ScopesProvider,
        Uuids;

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
    public function sender(): MorphTo
    {
        return $this->morphTo()->withDefault(fn () => Messenger::getGhostProvider());
    }

    /**
     * @return MorphTo|MessengerProvider
     */
    public function recipient(): MorphTo
    {
        return $this->morphTo()->withDefault(fn () => Messenger::getGhostProvider());
    }

    /**
     * Compare the recipient relation to the
     * current provider to see if they match.
     *
     * @return bool
     */
    public function isRecipientCurrentProvider(): bool
    {
        if (! Messenger::isProviderSet()) {
            return false;
        }

        return (string) Messenger::getProvider()->getKey() === (string) $this->recipient_id
            && Messenger::getProvider()->getMorphClass() === $this->recipient_type;
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
