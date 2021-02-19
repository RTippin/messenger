<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
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
 * @mixin Model|\Eloquent
 * @property-read MessengerProvider $owner
 * @property-read MessengerProvider $party
 */
class Friend extends Model
{
    use Uuids;

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
    public function owner()
    {
        return $this->morphTo()->withDefault(function () {
            return Messenger::getGhostProvider();
        });
    }

    /**
     * @return MorphTo|MessengerProvider
     */
    public function party()
    {
        return $this->morphTo()->withDefault(function () {
            return Messenger::getGhostProvider();
        });
    }
}
