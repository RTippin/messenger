<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Database\Factories\ParticipantFactory;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Support\Helpers;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * @property string $id
 * @property string $thread_id
 * @property string $owner_type
 * @property string|int $owner_id
 * @property bool $admin
 * @property bool $muted
 * @property bool $pending
 * @property bool $start_calls
 * @property bool $send_knocks
 * @property bool $send_messages
 * @property bool $add_participants
 * @property bool $manage_invites
 * @property \Illuminate\Support\Carbon|null $last_read
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\RTippin\Messenger\Models\Message[] $messages
 * @property-read int|null $messages_count
 * @property-read Model|MessengerProvider $owner
 * @property-read \RTippin\Messenger\Models\Thread $thread
 * @method static \Illuminate\Database\Query\Builder|Participant onlyTrashed()
 * @method static \Illuminate\Database\Query\Builder|Participant withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Participant withoutTrashed()
 * @mixin Model|\Eloquent
 * @method static Builder|Participant admins()
 * @method static Builder|Participant validProviders()
 * @method static Builder|Participant notMuted()
 * @method static Builder|Participant notPending()
 */
class Participant extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use ScopesProvider;

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
    public function thread()
    {
        return $this->belongsTo(
            Thread::class,
            'thread_id',
            'id'
        );
    }

    /**
     * @return HasMany|Collection
     */
    public function messages()
    {
        return $this->hasMany(
            Message::class,
            'thread_id',
            'thread_id'
        )
            ->forProviderWithModel($this)
            ->latest();
    }

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
     * @return Message|null
     */
    public function getLastSeenMessage(): ?Message
    {
        if (is_null($this->last_read)) {
            return null;
        }

        return Message::where('thread_id', '=', $this->thread_id)
            ->where('created_at', '<=', Helpers::PrecisionTime($this->last_read))
            ->latest()
            ->first();
    }

    /**
     * Scope for participants that are admins.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeAdmins(Builder $query): Builder
    {
        return $query->where('admin', '=', 1);
    }

    /**
     * Scope for participants that are not muted.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeNotMuted(Builder $query): Builder
    {
        return $query->where('muted', '=', 0);
    }

    /**
     * Scope for participants that are not pending.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeNotPending(Builder $query): Builder
    {
        return $query->where('pending', '=', 0);
    }

    /**
     * Scope for participants that are valid providers.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeValidProviders(Builder $query): Builder
    {
        return $query->whereIn(
            'owner_type',
            Messenger::getAllMessengerProviders()
        );
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
