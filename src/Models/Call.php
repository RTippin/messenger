<?php

namespace RTippin\Messenger\Models;

use RTippin\Messenger\Models\Contracts\MessengerProvider;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Traits\Uuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Cache;

/**
 * App\Models\Messages\Call
 *
 * @property string $id
 * @property string $thread_id
 * @property string $owner_id
 * @property string $owner_type
 * @property int $type
 * @property int $mode
 * @property int|null $room_id
 * @property string|null $room_pin
 * @property string|null $room_secret
 * @property string|null $call_ended
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\RTippin\Messenger\Models\CallParticipant[] $participants
 * @property-read int|null $participants_count
 * @property-read \RTippin\Messenger\Models\Thread $thread
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call query()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call whereCallEnded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call whereMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call whereOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call whereRoomId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call whereRoomPin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call whereRoomSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call whereThreadId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\Call whereUpdatedAt($value)
 * @mixin Model
 * @property-read Model|MessengerProvider $owner
 * @method static Builder|Call videoCall()
 * @method static Builder|Call active()
 * @property string|null $payload
 * @method static Builder|Call wherePayload($value)
 * @property int $setup_complete
 * @method static Builder|Call whereSetupComplete($value)
 */
class Call extends Model
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
     * The attributes that can be set with Mass Assignment.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $casts = [
        'room_id' => 'integer',
        'setup_complete' => 'boolean',
        'kicked' => 'boolean'
    ];

    /**
     * @var null|CallParticipant
     */
    private $currentParticipantCache = null;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['call_ended'];

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
     * @return BelongsTo|Thread
     */
    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * @return HasMany|CallParticipant|Collection
     */
    public function participants()
    {
        return $this->hasMany(
            CallParticipant::class,
            'call_id'
        );
    }

    /**
     * @return string
     */
    public function getTypeVerbose(): string
    {
        return Definitions::Call[$this->type];
    }

    /**
     * Scope a query for only video calls.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeVideoCall(Builder $query)
    {
        return $query->where('type', '=', 1);
    }

    /**
     * Scope a query for only video calls.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query)
    {
        return $query->whereNull('call_ended');
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return is_null($this->call_ended);
    }

    /**
     * @return bool
     */
    public function isSetup(): bool
    {
        return $this->setup_complete;
    }

    /**
     * @return bool
     */
    public function hasEnded()
    {
        return ! is_null($this->call_ended);
    }

    /**
     * @return bool
     */
    public function isVideoCall()
    {
        return $this->type === 1;
    }

    /**
     * @param Thread|null $thread
     * @return bool
     */
    public function isGroupCall(Thread $thread = null)
    {
        return $thread
            ? $thread->isGroup()
            : $this->thread->isGroup();
    }

    /**
     * @param Thread|null $thread
     * @return string|null
     */
    public function name(Thread $thread = null)
    {
        return $thread
            ? $thread->name()
            : $this->thread->name();
    }

    /**
     * @param Thread|null $thread
     * @return array
     */
    public function avatar(Thread $thread = null)
    {
        return $thread
            ? $thread->threadAvatar()
            : $this->thread->threadAvatar();
    }

    /**
     * @return CallParticipant|mixed|null
     */
    public function currentCallParticipant()
    {
        if( ! messenger()->isProviderSet()
            || $this->currentParticipantCache)
        {
            return $this->currentParticipantCache;
        }

        return $this->currentParticipantCache = $this->participants
            ->where('owner_id', messenger()->getProviderId())
            ->where('owner_type', '=', messenger()->getProviderClass())
            ->first();
    }

    /**
     * @param Thread|null $thread
     * @return bool
     */
    public function isCallAdmin(Thread $thread = null)
    {
        if($this->hasEnded()
            || ! $this->currentCallParticipant())
        {
            return false;
        }

        if(messenger()->getProviderId() === $this->owner_id
            && messenger()->getProviderClass() === $this->owner_type)
        {
            return true;
        }

        return $thread
                ? $thread->isAdmin()
                : $this->thread->isAdmin();
    }

    /**
     * @return bool
     */
    public function hasJoinedCall()
    {
        return $this->currentCallParticipant()
            ? true
            : false;
    }

    /**
     * @return bool
     */
    public function wasKicked()
    {
        return $this->currentCallParticipant()
            && $this->currentCallParticipant()->kicked;
    }

    /**
     * @return bool
     */
    public function isInCall()
    {
        if($this->hasEnded()
            || ! $this->currentCallParticipant())
        {
            return false;
        }

        return is_null($this->currentCallParticipant()->left_call);
    }

    /**
     * @return bool
     */
    public function hasLeftCall()
    {
        if($this->hasEnded()
            || ! $this->currentCallParticipant())
        {
            return false;
        }

        return ! is_null($this->currentCallParticipant()->left_call);
    }
}
