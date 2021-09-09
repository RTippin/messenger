<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RTippin\Messenger\Contracts\HasPresenceChannel;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Ownerable;
use RTippin\Messenger\Database\Factories\CallFactory;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Traits\HasOwner;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * @property string $id
 * @property string $thread_id
 * @property int $type
 * @property int|null $room_id
 * @property string|null $room_pin
 * @property string|null $room_secret
 * @property string|null $call_ended
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\RTippin\Messenger\Models\CallParticipant[] $participants
 * @property-read int|null $participants_count
 * @property-read \RTippin\Messenger\Models\Thread $thread
 * @mixin Model|\Eloquent
 * @method static Builder|Call videoCall()
 * @method static Builder|Call active()
 * @method static Builder|Call hasProvider(MessengerProvider $provider)
 * @property string|null $payload
 * @property bool $setup_complete
 * @property bool $teardown_complete
 */
class Call extends Model implements HasPresenceChannel, Ownerable
{
    use HasFactory,
        HasOwner,
        ScopesProvider,
        Uuids;

    const VIDEO = 1;
    const TYPE = [
        1 => 'VIDEO',
    ];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'calls';

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
        'call_ended' => 'datetime',
        'room_id' => 'integer',
        'setup_complete' => 'boolean',
        'teardown_complete' => 'boolean',
        'type' => 'integer',
    ];

    /**
     * @var null|CallParticipant
     */
    private ?CallParticipant $currentParticipantCache = null;

    /**
     * @return BelongsTo|Thread
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * @return HasMany|CallParticipant|Collection
     */
    public function participants(): HasMany
    {
        return $this->hasMany(
            CallParticipant::class,
            'call_id'
        );
    }

    /**
     * @inheritDoc
     */
    public function getPresenceChannel(): string
    {
        return "call.$this->id.thread.$this->thread_id";
    }

    /**
     * @return string
     */
    public function getTypeVerbose(): string
    {
        return self::TYPE[$this->type];
    }

    /**
     * Scope a query for only video calls.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeVideoCall(Builder $query): Builder
    {
        return $query->where('type', '=', self::VIDEO);
    }

    /**
     * Scope a query for only video calls.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('call_ended');
    }

    /**
     * @param  Builder  $query
     * @param  MessengerProvider  $provider
     * @return Builder
     */
    public function scopeHasProvider(Builder $query, MessengerProvider $provider): Builder
    {
        return $query->select('calls.*')
            ->join('call_participants', 'calls.id', '=', 'call_participants.call_id')
            ->where('call_participants.owner_id', '=', $provider->getKey())
            ->where('call_participants.owner_type', '=', $provider->getMorphClass());
    }

    /**
     * @return bool
     */
    public function isActive(): bool
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
    public function isTornDown(): bool
    {
        return $this->teardown_complete;
    }

    /**
     * @return bool
     */
    public function hasEnded(): bool
    {
        return ! is_null($this->call_ended);
    }

    /**
     * @return bool
     */
    public function isVideoCall(): bool
    {
        return $this->type === self::VIDEO;
    }

    /**
     * @param  Thread|null  $thread
     * @return bool
     */
    public function isGroupCall(?Thread $thread = null): bool
    {
        return $thread
            ? $thread->isGroup()
            : $this->thread->isGroup();
    }

    /**
     * @param  Thread|null  $thread
     * @return string|null
     */
    public function name(?Thread $thread = null): ?string
    {
        return $thread
            ? $thread->name()
            : $this->thread->name();
    }

    /**
     * @return CallParticipant|mixed|null
     */
    public function currentCallParticipant(): ?CallParticipant
    {
        if (! Messenger::isProviderSet()
            || $this->currentParticipantCache) {
            return $this->currentParticipantCache;
        }

        return $this->currentParticipantCache = $this->participants->forProvider(Messenger::getProvider())->first();
    }

    /**
     * @param  Thread|null  $thread
     * @return bool
     */
    public function isCallAdmin(?Thread $thread = null): bool
    {
        if ($this->hasEnded()
            || ! $this->currentCallParticipant()) {
            return false;
        }

        if ((string) Messenger::getProvider()->getKey() === (string) $this->owner_id
            && Messenger::getProvider()->getMorphClass() === $this->owner_type) {
            return true;
        }

        if (! is_null($thread)) {
            return $thread->isAdmin() || $thread->isPrivate();
        }

        return $this->thread->isAdmin() || $this->thread->isPrivate();
    }

    /**
     * @return bool
     */
    public function hasJoinedCall(): bool
    {
        return (bool) $this->currentCallParticipant();
    }

    /**
     * @return bool
     */
    public function wasKicked(): bool
    {
        return $this->currentCallParticipant()
            && $this->currentCallParticipant()->kicked;
    }

    /**
     * @return bool
     */
    public function isInCall(): bool
    {
        if ($this->hasEnded()
            || ! $this->currentCallParticipant()) {
            return false;
        }

        return is_null($this->currentCallParticipant()->left_call);
    }

    /**
     * @return bool
     */
    public function hasLeftCall(): bool
    {
        if ($this->hasEnded()
            || ! $this->currentCallParticipant()) {
            return false;
        }

        return ! is_null($this->currentCallParticipant()->left_call);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return CallFactory::new();
    }
}
