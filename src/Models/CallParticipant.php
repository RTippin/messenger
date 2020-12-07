<?php

namespace RTippin\Messenger\Models;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Uuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * App\Models\Messages\CallParticipant
 *
 * @property string $id
 * @property string $call_id
 * @property string $owner_id
 * @property string $owner_type
 * @property string|null $left_call
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \RTippin\Messenger\Models\Call $call
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\CallParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\CallParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\CallParticipant query()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\CallParticipant whereCallId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\CallParticipant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\CallParticipant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\CallParticipant whereLeftCall($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\CallParticipant whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\CallParticipant whereOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\CallParticipant whereUpdatedAt($value)
 * @mixin Model|\Eloquent
 * @property-read Model|MessengerProvider $owner
 * @property int $kicked
 * @method static \Illuminate\Database\Eloquent\Builder|CallParticipant whereKicked($value)
 * @method static Builder|Call inCall()
 */
class CallParticipant extends Model
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
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['left_call'];

    /**
     * @return MorphTo|MessengerProvider
     */
    public function owner()
    {
        return $this->morphTo()->withDefault(function(){
            return messenger()->getGhostProvider();
        });
    }

    /**
     * @return BelongsTo|Call
     */
    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Scope a query for only video calls.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInCall(Builder $query): Builder
    {
        return $query->whereNull('left_call');
    }
}
