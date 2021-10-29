<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Contracts\Ownerable;
use RTippin\Messenger\Database\Factories\ParticipantFactory;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Support\Helpers;
use RTippin\Messenger\Traits\HasOwner;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * @mixin Model|\Eloquent
 *
 * @property string $id
 * @property string $thread_id
 * @property bool $admin
 * @property bool $muted
 * @property bool $pending
 * @property bool $start_calls
 * @property bool $send_knocks
 * @property bool $send_messages
 * @property bool $add_participants
 * @property bool $manage_invites
 * @property bool $manage_bots
 * @property Carbon|null $last_read
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Collection|Message[] $messages
 * @property-read int|null $messages_count
 * @property-read Thread $thread
 *
 * @method static \Illuminate\Database\Query\Builder|Participant onlyTrashed()
 * @method static \Illuminate\Database\Query\Builder|Participant withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Participant withoutTrashed()
 * @method static Builder|Participant admins()
 * @method static Builder|Participant validProviders()
 * @method static Builder|Participant notMuted()
 * @method static Builder|Participant notPending()
 * @method static ParticipantFactory factory(...$parameters)
 */
class Participant extends Model implements Ownerable
{
    use HasFactory,
        HasOwner,
        ScopesProvider,
        SoftDeletes,
        Uuids;

    const DefaultPermissions = [
        'add_participants' => false,
        'manage_bots' => false,
        'manage_invites' => false,
        'admin' => false,
        'deleted_at' => null,
        'pending' => false,
        'start_calls' => false,
        'send_knocks' => false,
        'send_messages' => true,
    ];
    const AdminPermissions = [
        'add_participants' => true,
        'manage_bots' => true,
        'manage_invites' => true,
        'admin' => true,
        'deleted_at' => null,
        'pending' => false,
        'start_calls' => true,
        'send_knocks' => true,
        'send_messages' => true,
    ];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'participants';

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s.u';

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
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'add_participants' => 'boolean',
        'admin' => 'boolean',
        'last_read' => 'datetime',
        'manage_bots' => 'boolean',
        'manage_invites' => 'boolean',
        'muted' => 'boolean',
        'pending' => 'boolean',
        'send_knocks' => 'boolean',
        'send_messages' => 'boolean',
        'start_calls' => 'boolean',
    ];

    /**
     * @return BelongsTo|Thread
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * @return Message|null
     */
    public function getLastSeenMessage(): ?Message
    {
        if (is_null($this->last_read)) {
            return null;
        }

        return Cache::remember(
            $this->getLastSeenMessageCacheKey(),
            now()->addWeek(),
            fn () => Message::where('thread_id', '=', $this->thread_id)
                ->where('created_at', '<=', Helpers::precisionTime($this->last_read))
                ->latest()
                ->first()
        );
    }

    /**
     * @return string
     */
    public function getLastSeenMessageCacheKey(): string
    {
        return "participant:$this->id:last:read:message";
    }

    /**
     * Scope for participants that are admins.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('admin', '=', 1);
    }

    /**
     * Scope for participants that are not muted.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeNotMuted(Builder $query): Builder
    {
        return $query->where('muted', '=', 0);
    }

    /**
     * Scope for participants that are not pending.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeNotPending(Builder $query): Builder
    {
        return $query->where('pending', '=', 0);
    }

    /**
     * Scope for participants that are valid providers.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeValidProviders(Builder $query): Builder
    {
        return $query->whereIn('owner_type', Messenger::getAllProviders());
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return ParticipantFactory::new();
    }
}
