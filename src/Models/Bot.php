<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Database\Factories\BotFactory;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\MessengerBots as Bots;
use RTippin\Messenger\Support\Helpers;
use RTippin\Messenger\Traits\ScopesProvider;

/**
 * @property string|int $id
 * @property string $thread_id
 * @property string|int $owner_id
 * @property string $owner_type
 * @property string $name
 * @property string $avatar
 * @property bool $enabled
 * @property bool $hide_actions
 * @property int $cooldown
 * @property int $valid_actions_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \RTippin\Messenger\Models\Thread $thread
 * @property-read \RTippin\Messenger\Models\BotAction[]|Collection $actions
 * @mixin Model|\Eloquent
 * @property-read Model|MessengerProvider $owner
 */
class Bot extends Model implements MessengerProvider
{
    use HasFactory,
        SoftDeletes,
        ScopesProvider;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->setKeyType(Bots::shouldUseUuids() ? 'string' : 'int');

        $this->setIncrementing(! Bots::shouldUseUuids());

        parent::__construct($attributes);
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'bots';

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
        'enabled' => 'boolean',
        'hide_actions' => 'boolean',
        'cooldown' => 'integer',
    ];

    /**
     * On creating, set primary key as UUID if enabled.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function (Model $model) {
            if (Bots::shouldUseUuids()) {
                $model->id = Str::orderedUuid()->toString();
            }
        });
    }

    /**
     * @return MorphTo|MessengerProvider
     */
    public function owner(): MorphTo
    {
        return $this->morphTo()->withDefault(function () {
            return Messenger::getGhostProvider();
        });
    }

    /**
     * @return BelongsTo|Thread
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * @return HasMany|BotAction|Collection
     */
    public function actions(): HasMany
    {
        return $this->hasMany(BotAction::class);
    }

    /**
     * @return HasMany|BotAction|Collection
     */
    public function validActions(): HasMany
    {
        return $this->hasMany(BotAction::class)
            ->whereIn('handler', MessengerBots::getHandlerClasses());
    }

    /**
     * Get the provider settings and alias override, if set.
     *
     * @return array
     */
    public static function getProviderSettings(): array
    {
        return [];
    }

    /**
     * @return string|null
     */
    public function getProviderProfileRoute(): ?string
    {
        return null;
    }

    /**
     * @return string
     */
    public function getProviderName(): string
    {
        return strip_tags($this->name);
    }

    /**
     * @return string
     */
    public function getProviderAvatarColumn(): string
    {
        return 'avatar';
    }

    /**
     * @return string
     */
    public function getProviderLastActiveColumn(): string
    {
        return 'updated_at';
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
        return Messenger::getThreadStorage('directory')."/$this->thread_id/bots/$this->id";
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
        return "{$this->getAvatarDirectory()}/$this->avatar";
    }

    /**
     * @param string $size
     * @return string|null
     */
    public function getProviderAvatarRoute(string $size = 'sm'): ?string
    {
        return Helpers::Route('messenger.threads.bots.avatar.render',
            [
                'thread' => $this->thread_id,
                'bot' => $this->id,
                'size' => $size,
                'image' => $this->avatar ?: 'default.png',
            ]
        );
    }

    /**
     * @return int
     */
    public function getProviderOnlineStatus(): int
    {
        return 0;
    }

    /**
     * @return string
     */
    public function getProviderOnlineStatusVerbose(): string
    {
        return 'offline';
    }

    /**
     * Are actions visible to regular participants?
     *
     * @param Thread|null $thread
     * @return bool
     */
    public function isActionsVisible(?Thread $thread = null): bool
    {
        if (is_null($thread)) {
            return ! $this->hide_actions || $this->thread->canManageBots();
        }

        return ! $this->hide_actions || $thread->canManageBots();
    }

    /**
     * Does the bot have an active cooldown?
     *
     * @return bool
     */
    public function isOnCooldown(): bool
    {
        return Cache::has("bot:$this->id:cooldown");
    }

    /**
     * Is the bot available?
     *
     * @return bool
     */
    public function notOnCooldown(): bool
    {
        return ! $this->isOnCooldown();
    }

    /**
     * Set the bots cooldown.
     */
    public function startCooldown(): void
    {
        if ($this->cooldown > 0) {
            Cache::put("bot:$this->id:cooldown", true, now()->addSeconds($this->cooldown));
        }
    }

    /**
     * Release the bots cooldown.
     */
    public function releaseCooldown(): void
    {
        Cache::forget("bot:$this->id:cooldown");
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return BotFactory::new();
    }
}
