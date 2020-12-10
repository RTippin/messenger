<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * App\Models\Friend\Friend.
 *
 * @property string $id
 * @property string $owner_id
 * @property string $owner_type
 * @property string $party_id
 * @property string $party_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Friend newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Friend newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Friend query()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Friend whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Friend whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Friend whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Friend whereOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Friend wherePartyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Friend wherePartyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Friend whereUpdatedAt($value)
 * @mixin Model|\Eloquent
 * @property-read MessengerProvider $owner
 * @property-read MessengerProvider $party
 */
class Friend extends Model
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
    protected $table = 'friends';

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @return MorphTo|MessengerProvider
     */
    public function owner()
    {
        return $this->morphTo()->withDefault(function () {
            return messenger()->getGhostProvider();
        });
    }

    /**
     * @return MorphTo|MessengerProvider
     */
    public function party()
    {
        return $this->morphTo()->withDefault(function () {
            return messenger()->getGhostProvider();
        });
    }
}
