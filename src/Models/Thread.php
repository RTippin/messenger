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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Contracts\HasPresenceChannel;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Database\Factories\ThreadFactory;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Support\Helpers;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * @mixin Model|\Eloquent
 *
 * @property string $id
 * @property int $type
 * @property string|null $subject
 * @property string|null $image
 * @property bool $add_participants
 * @property bool $calling
 * @property bool $messaging
 * @property bool $knocks
 * @property bool $chat_bots
 * @property bool $lockout
 * @property bool $invitations
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Call|null $activeCall
 * @property-read Collection|Call[] $calls
 * @property-read int|null $calls_count
 * @property-read Collection|Invite[] $invites
 * @property-read int|null $invites_count
 * @property-read Collection|Message[] $messages
 * @property-read Collection|Message[] $audio
 * @property-read Collection|Message[] $documents
 * @property-read Collection|Message[] $images
 * @property-read Collection|Message[] $logs
 * @property-read Collection|Message[] $videos
 * @property-read int|null $messages_count
 * @property-read Collection|Participant[] $participants
 * @property-read int|null $participants_count
 * @property-read Message|null $latestMessage
 * @property-read Bot[] $bots
 *
 * @method static Builder|Thread group()
 * @method static Builder|Thread private()
 * @method static Builder|Thread hasProvider(MessengerProvider $provider)
 * @method static \Illuminate\Database\Query\Builder|Thread onlyTrashed()
 * @method static \Illuminate\Database\Query\Builder|Thread withTrashed()
 * @method static \Illuminate\Database\Query\Builder|Thread withoutTrashed()
 * @method static ThreadFactory factory(...$parameters)
 */
class Thread extends Model implements HasPresenceChannel
{
    use HasFactory,
        ScopesProvider,
        SoftDeletes,
        Uuids;

    const PRIVATE = 1;
    const GROUP = 2;
    const TYPE = [
        1 => 'PRIVATE',
        2 => 'GROUP',
    ];
    const DefaultSettings = [
        'type' => self::PRIVATE,
        'subject' => null,
        'image' => null,
        'calling' => true,
        'invitations' => false,
        'add_participants' => false,
        'messaging' => true,
        'knocks' => true,
        'chat_bots' => false,
        'lockout' => false,
    ];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'threads';

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
     * The attributes that can't be set with Mass Assignment.
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
        'invitations' => 'boolean',
        'calling' => 'boolean',
        'messaging' => 'boolean',
        'knocks' => 'boolean',
        'type' => 'integer',
        'lockout' => 'boolean',
        'chat_bots' => 'boolean',
    ];

    /**
     * @var Participant|null
     */
    private ?Participant $recipientCache = null;

    /**
     * @var string|null
     */
    private ?string $nameCache = null;

    /**
     * @var Participant|null
     */
    private ?Participant $currentParticipantCache = null;

    /**
     * @var int
     */
    private int $unreadCountCache = 0;

    /**
     * @return HasMany|Participant|Collection
     */
    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    /**
     * @return HasMany|Message|Collection
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasMany|Call|Collection
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * @return HasMany|Invite|Collection
     */
    public function invites(): HasMany
    {
        return $this->hasMany(Invite::class);
    }

    /**
     * @return HasOne|Call
     */
    public function activeCall(): HasOne
    {
        return $this->hasOne(Call::class)->ofMany(
            ['id' => 'MAX'],
            fn (Builder $query) => $query->whereNull('call_ended')
        );
    }

    /**
     * @return HasOne
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * @return HasMany|Bot|Collection
     */
    public function bots(): HasMany
    {
        return $this->hasMany(Bot::class);
    }

    /**
     * @return HasMany|Message|Collection
     */
    public function audio(): HasMany
    {
        return $this->hasMany(Message::class)
            ->where('type', '=', Message::AUDIO_MESSAGE);
    }

    /**
     * @return HasMany|Message|Collection
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Message::class)
            ->where('type', '=', Message::DOCUMENT_MESSAGE);
    }

    /**
     * @return HasMany|Message|Collection
     */
    public function images(): HasMany
    {
        return $this->hasMany(Message::class)
            ->where('type', '=', Message::IMAGE_MESSAGE);
    }

    /**
     * @return HasMany|Message|Collection
     */
    public function logs(): HasMany
    {
        return $this->hasMany(Message::class)
            ->whereNotIn('type', Message::NonSystemTypes);
    }

    /**
     * @return HasMany|Message|Collection
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Message::class)
            ->where('type', '=', Message::VIDEO_MESSAGE);
    }

    /**
     * Scope a query for only group threads.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeGroup(Builder $query): Builder
    {
        return $query->where('type', '=', self::GROUP);
    }

    /**
     * Scope a query for only private threads.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('type', '=', self::PRIVATE);
    }

    /**
     * @param  Builder  $query
     * @param  MessengerProvider  $provider
     * @return Builder
     */
    public function scopeHasProvider(Builder $query, MessengerProvider $provider): Builder
    {
        return $query->select('threads.*')
            ->join('participants', 'threads.id', '=', 'participants.thread_id')
            ->where('participants.owner_id', '=', $provider->getKey())
            ->where('participants.owner_type', '=', $provider->getMorphClass())
            ->whereNull('participants.deleted_at');
    }

    /**
     * @inheritDoc
     */
    public function getPresenceChannel(): string
    {
        return 'thread.'.$this->id;
    }

    /**
     * @param  MessengerProvider|null  $provider
     * @return string
     */
    public function getKnockCacheKey(?MessengerProvider $provider = null): string
    {
        $baseKey = "knock.knock.$this->id";

        return $this->isGroup()
            ? $baseKey
            : $baseKey.'.'.optional($provider)->getKey();
    }

    /**
     * @param  MessengerProvider|null  $provider
     */
    public function setKnockCacheLockout(?MessengerProvider $provider = null)
    {
        if (Messenger::isKnockKnockEnabled()
            && Messenger::getKnockTimeout() !== 0) {
            Cache::put(
                $this->getKnockCacheKey($provider),
                true,
                now()->addMinutes(Messenger::getKnockTimeout())
            );
        }
    }

    /**
     * @param  MessengerProvider|null  $provider
     * @return bool
     */
    public function hasKnockTimeout(?MessengerProvider $provider = null): bool
    {
        return Messenger::isKnockKnockEnabled()
            && Messenger::getKnockTimeout() !== 0
            && Cache::has($this->getKnockCacheKey($provider));
    }

    /**
     * @return string
     */
    public function getStorageDisk(): string
    {
        return Messenger::getThreadStorage('disk');
    }

    /**
     * @return string
     */
    public function getStorageDirectory(): string
    {
        return Messenger::getThreadStorage('directory')."/$this->id";
    }

    /**
     * @return string
     */
    public function getImagesDirectory(): string
    {
        return "{$this->getStorageDirectory()}/images";
    }

    /**
     * @return string
     */
    public function getDocumentsDirectory(): string
    {
        return "{$this->getStorageDirectory()}/documents";
    }

    /**
     * @return string
     */
    public function getAudioDirectory(): string
    {
        return "{$this->getStorageDirectory()}/audio";
    }

    /**
     * @return string
     */
    public function getVideoDirectory(): string
    {
        return "{$this->getStorageDirectory()}/videos";
    }

    /**
     * @return string
     */
    public function getAvatarDirectory(): string
    {
        return "{$this->getStorageDirectory()}/avatar";
    }

    /**
     * @return string
     */
    public function getAvatarPath(): string
    {
        return "{$this->getAvatarDirectory()}/$this->image";
    }

    /**
     * @return string
     */
    public function getTypeVerbose(): string
    {
        return self::TYPE[$this->type];
    }

    /**
     * @return bool
     */
    public function isGroup(): bool
    {
        return $this->type === self::GROUP;
    }

    /**
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->type === self::PRIVATE;
    }

    /**
     * @return Participant
     */
    public function recipient(): Participant
    {
        if (! is_null($this->recipientCache)) {
            return $this->recipientCache;
        }

        if ($this->isGroup() || ! $this->hasCurrentProvider()) {
            return $this->recipientCache = Messenger::getGhostParticipant($this->id);
        }

        if ($this->relationLoaded('participants')) {
            $recipient = $this->participants
                ->where('id', '!=', $this->currentParticipant()->id)
                ->first();
        } else {
            $recipient = $this->participants()
                ->notProvider(Messenger::getProvider())
                ->first();
        }

        return $this->recipientCache = (! is_null($recipient)
            && Messenger::isValidMessengerProvider($recipient->owner))
            ? $recipient
            : Messenger::getGhostParticipant($this->id);
    }

    /**
     * @return Participant|null
     */
    public function currentParticipant(): ?Participant
    {
        if ($this->currentParticipantCache
            || ! Messenger::isProviderSet()) {
            return $this->currentParticipantCache;
        }

        if ($this->relationLoaded('participants')) {
            $this->currentParticipantCache = Helpers::forProviderInCollection(
                $this->participants,
                Messenger::getProvider()
            )->first();
        } else {
            $this->currentParticipantCache = $this->participants()
                ->forProvider(Messenger::getProvider())
                ->first();
        }

        return $this->currentParticipantCache;
    }

    /**
     * @return bool
     */
    public function hasCurrentProvider(): bool
    {
        return ! is_null($this->currentParticipant());
    }

    /**
     * @return string
     */
    public function name(): string
    {
        if (! is_null($this->nameCache)) {
            return $this->nameCache;
        }

        if ($this->isGroup()) {
            $name = $this->subject ?: 'Group';
        } else {
            $name = $this->recipient()->owner->getProviderName() ?: 'Conversation';
        }

        return $this->nameCache = htmlspecialchars($name);
    }

    /**
     * @return bool
     */
    public function hasActiveCall(): bool
    {
        return ! is_null($this->activeCall);
    }

    /**
     * @param  string  $size
     * @return string|null
     */
    public function getThreadAvatarRoute(string $size = 'sm'): ?string
    {
        return Helpers::route('assets.messenger.threads.avatar.render',
            [
                'thread' => $this->id,
                'size' => $size,
                'image' => $this->image ?: 'default.png',
            ]
        );
    }

    /**
     * @return array
     */
    public function threadAvatar(): array
    {
        if ($this->isPrivate()) {
            return [
                'sm' => $this->recipient()->owner->getProviderAvatarRoute(),
                'md' => $this->recipient()->owner->getProviderAvatarRoute('md'),
                'lg' => $this->recipient()->owner->getProviderAvatarRoute('lg'),
            ];
        }

        return [
            'sm' => $this->getThreadAvatarRoute(),
            'md' => $this->getThreadAvatarRoute('md'),
            'lg' => $this->getThreadAvatarRoute('lg'),
        ];
    }

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->isGroup()
            && $this->hasCurrentProvider()
            && $this->currentParticipant()->admin;
    }

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return ! $this->hasCurrentProvider()
            || $this->lockout
            || ($this->isPrivate()
                && $this->recipient()->owner instanceof GhostUser);
    }

    /**
     * @return bool
     */
    public function isMuted(): bool
    {
        return $this->hasCurrentProvider()
            && $this->currentParticipant()->muted;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->hasCurrentProvider()
            && $this->isPrivate()
            && ($this->currentParticipant()->pending
                || $this->recipient()->pending);
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
        return ! $this->isLocked()
            && $this->messaging
            && ! $this->isAwaitingMyApproval()
            && ($this->currentParticipant()->send_messages
                || $this->isAdmin());
    }

    /**
     * @return bool
     */
    public function canAddParticipants(): bool
    {
        return ! $this->isLocked()
            && $this->isGroup()
            && $this->add_participants
            && ($this->currentParticipant()->add_participants
                || $this->isAdmin());
    }

    /**
     * @return bool
     */
    public function hasInvitationsFeature(): bool
    {
        return Messenger::isThreadInvitesEnabled()
            && $this->isGroup()
            && $this->invitations;
    }

    /**
     * @return bool
     */
    public function canInviteParticipants(): bool
    {
        return $this->hasInvitationsFeature()
            && ! $this->isLocked()
            && ($this->currentParticipant()->manage_invites
                || $this->isAdmin());
    }

    /**
     * @return bool
     */
    public function canJoinWithInvite(): bool
    {
        return $this->hasInvitationsFeature()
            && Messenger::isProviderSet()
            && ! $this->lockout
            && ! $this->hasCurrentProvider();
    }

    /**
     * @return bool
     */
    public function hasCallingFeature(): bool
    {
        return Messenger::isCallingEnabled()
            && ! Messenger::isCallingTemporarilyDisabled()
            && $this->calling;
    }

    /**
     * @return bool
     */
    public function canCall(): bool
    {
        return $this->hasCallingFeature()
            && ! $this->isLocked()
            && ! $this->isPending()
            && ($this->isPrivate()
                || ($this->currentParticipant()->start_calls
                    || $this->isAdmin()));
    }

    /**
     * @return bool
     */
    public function hasKnocksFeature(): bool
    {
        return Messenger::isKnockKnockEnabled() && $this->knocks;
    }

    /**
     * @return bool
     */
    public function canKnock(): bool
    {
        return $this->hasKnocksFeature()
            && ! $this->isLocked()
            && ! $this->isPending()
            && ($this->isPrivate()
                || ($this->currentParticipant()->send_knocks
                    || $this->isAdmin()));
    }

    /**
     * @return bool
     */
    public function hasBotsFeature(): bool
    {
        return Messenger::isBotsEnabled()
            && $this->isGroup()
            && $this->chat_bots;
    }

    /**
     * @return bool
     */
    public function canManageBots(): bool
    {
        return $this->hasBotsFeature()
            && ! $this->isLocked()
            && ($this->isAdmin()
                || $this->currentParticipant()->manage_bots);
    }

    /**
     * @return bool
     */
    public function isUnread(): bool
    {
        return $this->hasCurrentProvider()
            && (is_null($this->currentParticipant()->last_read)
                || Helpers::precisionTime($this->updated_at) > Helpers::precisionTime($this->currentParticipant()->last_read));
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
                ->where('created_at', '>', Helpers::precisionTime($this->currentParticipant()->last_read))
                ->count();

        return $this->unreadCountCache;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return ThreadFactory::new();
    }
}
