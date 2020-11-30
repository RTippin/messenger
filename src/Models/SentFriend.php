<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * App\Models\Friend\SentFriend
 *
 * @property string $id
 * @property string $sender_id
 * @property string $sender_type
 * @property string $recipient_id
 * @property string $recipient_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\SentFriend newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\SentFriend newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\SentFriend query()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\SentFriend whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\SentFriend whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\SentFriend whereRecipientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\SentFriend whereRecipientType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\SentFriend whereSenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\SentFriend whereSenderType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\SentFriend whereUpdatedAt($value)
 * @mixin Model|\Eloquent
 * @property-read MessengerProvider $recipient
 * @property-read MessengerProvider $sender
 */
class SentFriend extends Model
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
        return $this->morphTo()->withDefault(function(){
            return messenger()->getGhostProvider();
        });
    }

    /**
     * @return MorphTo|MessengerProvider
     */
    public function recipient()
    {
        return $this->morphTo()->withDefault(function(){
            return messenger()->getGhostProvider();
        });
    }
}