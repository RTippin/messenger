<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * App\Models\Friend\PendingFriend.
 *
 * @property string $id
 * @property string $sender_id
 * @property string $sender_type
 * @property string $recipient_id
 * @property string $recipient_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\PendingFriend newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\PendingFriend newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\PendingFriend query()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\PendingFriend whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\PendingFriend whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\PendingFriend whereRecipientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\PendingFriend whereRecipientType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\PendingFriend whereSenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\PendingFriend whereSenderType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\PendingFriend whereUpdatedAt($value)
 * @mixin Model|\Eloquent
 * @property-read MessengerProvider $recipient
 * @property-read MessengerProvider $sender
 */
class PendingFriend extends Model
{
    use Uuids;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    public $keyType = 'string';

    /**
     * @var string
     */
    protected $table = 'pending_friends';

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
            return messenger()->getGhostProvider();
        });
    }

    /**
     * @return MorphTo|MessengerProvider
     */
    public function recipient()
    {
        return $this->morphTo()->withDefault(function () {
            return messenger()->getGhostProvider();
        });
    }
}
