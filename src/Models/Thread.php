<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Database\Factories\ThreadFactory;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Traits\Uuids;
use Staudenmeir\EloquentEagerLimit\HasEagerLimit;

/**
 * App\Models\Messages\Thread.
 *
 * @property string $id
 * @property int $type
 * @property string|null $subject
 * @property string|null $image
 * @property bool $add_participants
 * @property bool $calling
 * @property bool $messaging
 * @property bool $knocks
 * @property int $lockout
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \RTippin\Messenger\Models\Call|null $activeCall
 * @property-read Collection|\RTippin\Messenger\Models\Call[] $calls
 * @property-read int|null $calls_count
 * @property-read Collection|\RTippin\Messenger\Models\Invite[] $invites
 * @property-read int|null $invites_count
 * @property-read Collection|\RTippin\Messenger\Models\Message[] $messages
 * @property-read int|null $messages_count
 * @property-read Collection|\RTippin\Messenger\Models\Participant[] $participants
 * @property-read int|null $participants_count
 * @property-read \RTippin\Messenger\Models\Message|null $recentMessage
 * @method static Builder|Thread group()
 * @method static Builder|Thread newModelQuery()
 * @method static Builder|Thread newQuery()
 * @method static \Illuminate\Database\Query\Builder|Thread onlyTrashed()
 * @method static Builder|Thread private()
 * @method static Builder|Thread query()
 * @method static Builder|Thread whereAddParticipants($value)
 * @method static Builder|Thread whereCalling($value)
 * @method static Builder|Thread whereCreatedAt($value)
 * @method static Builder|Thread whereDeletedAt($value)
 * @method static Builder|Thread whereId($value)
 * @method static Builder|Thread whereImage($value)
 * @method static Builder|Thread whereKnocks($value)
 * @method static Builder|Thread whereLockout($value)
 * @method static Builder|Thread whereMessaging($value)
 * @method static Builder|Thread whereSubject($value)
 * @method static Builder|Thread whereType($value)
 * @method static Builder|Thread whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|Thread withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Thread withoutTrashed()
 * @mixin Model|\Eloquent
 * @property bool $invitations
 * @method static Builder|Thread whereInvitations($value)
 */
class Thread extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Uuids;
    use HasEagerLimit;

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    public $keyType = 'string';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'threads';

    /**
     * The attributes that can't be set with Mass Assignment.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'add_participants' => 'boolean',
        'invitations' => 'boolean',
        'calling' => 'boolean',
        'messaging' => 'boolean',
        'knocks' => 'boolean',
    ];

    /**
     * @var Participant
     */
    private Participant $recipientCache;

    /**
     * @var string
     */
    private string $nameCache;

    /**
     * @var Participant|null
     */
    private ?Participant $currentParticipantCache = null;

    /**
     * @var int
     */
    private int $unreadCountCache = 0;

    /**
     * @return HasMany|Message|Collection
     */
    public function messages()
    {
        return $this->hasMany(
            Message::class,
            'thread_id',
            'id'
        );
    }

    /**
     * @return HasMany|Participant|Collection
     */
    public function participants()
    {
        return $this->hasMany(
            Participant::class,
            'thread_id',
            'id'
        );
    }

    /**
     * @return HasMany|Call|Collection
     */
    public function calls()
    {
        return $this->hasMany(
            Call::class,
            'thread_id',
            'id'
        );
    }

    /**
     * @return HasOne|Call
     */
    public function activeCall()
    {
        return $this->hasOne(
            Call::class,
            'thread_id',
            'id'
        )
            ->whereNull('call_ended')
            ->latest();
    }

    /**
     * Scope a query for only group threads.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeGroup(Builder $query): Builder
    {
        return $query->where('type', '=', 2);
    }

    /**
     * Scope a query for only private threads.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('type', '=', 1);
    }

    /**
     * @return HasOne
     */
    public function recentMessage()
    {
        return $this->hasOne(
            Message::class,
            'thread_id',
            'id')
            ->latest()
            ->limit(1);
    }

    /**
     * @return HasMany|Invite|Collection
     */
    public function invites()
    {
        return $this->hasMany(Invite::class);
    }

    /**
     * @return string
     */
    public function getStorageDisk(): string
    {
        return messenger()->getThreadStorage('disk');
    }

    /**
     * @return string
     */
    public function getStorageDirectory(): string
    {
        return messenger()->getThreadStorage('directory')."/{$this->id}";
    }

    /**
     * @return string
     */
    public function getAvatarPath(): string
    {
        return "{$this->getStorageDirectory()}/avatar/{$this->image}";
    }

    /**
     * @return string
     */
    public function getTypeVerbose(): string
    {
        return Definitions::Thread[$this->type];
    }

    /**
     * @return Participant
     */
    public function recipient(): Participant
    {
        if (isset($this->recipientCache)) {
            return $this->recipientCache;
        }

        if (! $this->hasCurrentProvider()) {
            return $this->recipientCache = messenger()->getGhostParticipant($this->id);
        }

        /** @var Participant $recipient */
        $recipient = null;

        if ($this->isPrivate()) {
            if ($this->relationLoaded('participants')) {
                $recipient = $this->participants
                    ->where('id', '!=', $this->currentParticipant()->id)
                    ->first();
            } else {
                $recipient = $this->participants()
                    ->where('id', '!=', $this->currentParticipant()->id)
                    ->first();
            }
        }

        return $this->recipientCache = ($recipient
            && messenger()->isValidMessengerProvider($recipient->owner))
            ? $recipient
            : messenger()->getGhostParticipant($this->id);
    }

    /**
     * @return Participant|null
     */
    public function currentParticipant(): ?Participant
    {
        if ($this->currentParticipantCache
            || ! messenger()->isProviderSet()) {
            return $this->currentParticipantCache;
        }

        if ($this->relationLoaded('participants')) {
            $this->currentParticipantCache = $this->participants
                ->where('owner_id', messenger()->getProviderId())
                ->where('owner_type', messenger()->getProviderClass())
                ->first();
        } else {
            $this->currentParticipantCache = $this->participants()
                ->where('owner_id', messenger()->getProviderId())
                ->where('owner_type', messenger()->getProviderClass())
                ->first();
        }

        return $this->currentParticipantCache;
    }

    /**
     * @return bool
     */
    public function hasCurrentProvider(): bool
    {
        return is_null($this->currentParticipant())
            ? false
            : true;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        if (isset($this->nameCache)) {
            return $this->nameCache;
        }

        $name = 'Conversation';

        if ($this->isPrivate()) {
            $name = $this->recipient()->owner->name();
        } elseif ($this->isGroup()) {
            $name = $this->subject;
        }

        $this->nameCache = htmlspecialchars($name);

        return $this->nameCache;
    }

    /**
     * @return bool
     */
    public function hasActiveCall(): bool
    {
        return ! is_null($this->activeCall);
    }

    /**
     * @param string $size
     * @param bool $api
     * @return string|null
     */
    public function getThreadAvatarRoute(string $size = 'sm', $api = false): string
    {
        return messengerRoute(($api ? 'api.' : '').'messenger.threads.avatar.render',
            [
                'thread' => $this->id,
                'size' => $size,
                'image' => $this->image,
            ]
        );
    }

    /**
     * @param bool $api
     * @return array
     */
    public function threadAvatar($api = false): array
    {
        if ($this->isPrivate()) {
            return [
                'sm' => $this->recipient()->owner->getAvatarRoute('sm', $api),
                'md' => $this->recipient()->owner->getAvatarRoute('md', $api),
                'lg' => $this->recipient()->owner->getAvatarRoute('lg', $api),
            ];
        }

        return [
            'sm' => $this->getThreadAvatarRoute('sm', $api),
            'md' => $this->getThreadAvatarRoute('md', $api),
            'lg' => $this->getThreadAvatarRoute('lg', $api),
        ];
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        if ($this->isGroup()
            && $this->hasCurrentProvider()) {
            return $this->currentParticipant()->admin
                ? true
                : false;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        if (! $this->hasCurrentProvider()
            || ($this->isPrivate()
                && ($this->lockout
                    || $this->recipient()->owner instanceof GhostUser))) {
            return true;
        } elseif ($this->isGroup() && $this->lockout) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isMuted(): bool
    {
        if (! $this->hasCurrentProvider()
            || $this->isLocked()
            || $this->currentParticipant()->muted) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        if ($this->hasCurrentProvider() && $this->isPrivate()) {
            return $this->currentParticipant()->pending
                || $this->recipient()->pending;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isAwaitingMyApproval(): bool
    {
        return $this->isPending()
            && $this->currentParticipant()->pending;
    }

    /**
     * @return bool
     */
    public function canMessage(): bool
    {
        if ($this->isLocked()
            || $this->currentParticipant()->pending
            || ($this->isGroup()
                && (! $this->messaging
                    || (! $this->isAdmin()
                        && ! $this->currentParticipant()->send_messages)))) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function canAddParticipants(): bool
    {
        if ($this->isLocked()
            || $this->isPrivate()) {
            return false;
        }

        return $this->add_participants
            && ($this->isAdmin()
                || $this->currentParticipant()->add_participants);
    }

    /**
     * @return bool
     */
    public function canInviteParticipants(): bool
    {
        return messenger()->isThreadInvitesEnabled()
            && ! $this->isLocked()
            && $this->isGroup()
            && $this->invitations
            && ($this->isAdmin()
                || $this->currentParticipant()->manage_invites);
    }

    /**
     * @return bool
     */
    public function canCall(): bool
    {
        if (! messenger()->isCallingEnabled()
            || $this->isLocked()
            || $this->isPending()
            || ($this->isGroup()
                && (! $this->calling
                    || (! $this->isAdmin()
                        && ! $this->currentParticipant()->start_calls)))) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function canKnock(): bool
    {
        if (! messenger()->isKnockKnockEnabled()
            || $this->isLocked()
            || $this->isPending()
            || ($this->isGroup()
                && (! $this->knocks
                    || (! $this->isAdmin()
                        && ! $this->currentParticipant()->send_knocks)))) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function hasKnockTimeout(): bool
    {
        if ($this->hasCurrentProvider()) {
            return $this->isGroup()
                ? Cache::has("knock.knock.{$this->id}")
                : Cache::has("knock.knock.{$this->id}.{$this->currentParticipant()->owner_id}");
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isUnread(): bool
    {
        if (! $this->hasCurrentProvider()) {
            return false;
        }

        if ($this->currentParticipant()->last_read === null
            || $this->updated_at->gt($this->currentParticipant()->last_read)) {
            return true;
        }

        return false;
    }

    /**
     * @return int
     */
    public function unreadCount(): int
    {
        if (! $this->hasCurrentProvider()
            || $this->unreadCountCache !== 0
            || ! $this->isUnread()) {
            return $this->unreadCountCache;
        }

        $this->unreadCountCache = is_null($this->currentParticipant()->last_read)
            ? $this->messages()->count()
            : $this->messages()
                ->where('created_at', '>', $this->currentParticipant()->last_read)
                ->count();

        return $this->unreadCountCache;
    }

    /**
     * @return bool
     */
    public function isGroup(): bool
    {
        return $this->type === 2;
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->type === 1;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return ThreadFactory::new();
    }
}
