<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * App\Models\Messages\Participant.
 *
 * @property string $id
 * @property string $thread_id
 * @property string $owner_type
 * @property string $owner_id
 * @property bool $admin
 * @property bool $muted
 * @property bool $pending
 * @property bool $start_calls
 * @property bool $send_knocks
 * @property bool $send_messages
 * @property bool $add_participants
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
 * @property int $manage_invites
 */
class Participant extends Model
{
    use SoftDeletes;
    use Uuids;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'participants';

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
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['last_read'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'admin' => 'boolean',
        'muted' => 'boolean',
        'pending' => 'boolean',
        'send_messages' => 'boolean',
        'send_knocks' => 'boolean',
        'start_calls' => 'boolean',
        'add_participants' => 'boolean',
        'manage_invites' => 'boolean',
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
            ->where('owner_id', '=', $this->owner_id)
            ->where('owner_type', '=', $this->owner_type)
            ->latest();
    }

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
     * @return Message|null
     */
    public function getLastSeenMessage(): ?Message
    {
        if (is_null($this->last_read)) {
            return null;
        }

        return Message::where('thread_id', '=', $this->thread_id)
            ->where('created_at', '<=', $this->last_read)
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
            messenger()->getAllMessengerProviders()
        );
    }
}
