<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Ownerable;
use RTippin\Messenger\Database\Factories\BotFactory;
use RTippin\Messenger\Facades\Messenger as MessengerFacade;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Support\Helpers;
use RTippin\Messenger\Traits\HasOwner;
use RTippin\Messenger\Traits\ScopesProvider;

/**
 * @mixin Model|\Eloquent
 *
 * @property string|int $id
 * @property string $thread_id
 * @property string $name
 * @property string $avatar
 * @property bool $enabled
 * @property bool $hide_actions
 * @property int $cooldown
 * @property int $valid_actions_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Thread $thread
 * @property-read BotAction[]|Collection $actions
 * @property-read BotAction[]|Collection $validActions
 * @property-read BotAction[]|Collection $validUniqueActions
 *
 * @method static BotFactory factory(...$parameters)
 */
class Bot extends Model implements MessengerProvider, Ownerable
{
    use HasFactory,
        HasOwner,
        ScopesProvider,
        SoftDeletes;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        $this->setKeyType(Messenger::shouldUseUuids() ? 'string' : 'int');

        $this->setIncrementing(! Messenger::shouldUseUuids());

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
            if (Messenger::shouldUseUuids()) {
                $model->{$model->getKeyName()} = Str::orderedUuid()->toString();
            }
        });
    }

    /**
     * Get the provider settings.
     *
     * @return array
     */
    public static function getProviderSettings(): array
    {
        return [
            'alias' => 'bot',
            'searchable' => false,
            'friendable' => false,
            'devices' => false,
            'default_avatar' => config('messenger.files.default_bot_avatar'),
            'cant_message_first' => [],
            'cant_search' => [],
            'cant_friend' => [],
        ];
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
     * @return HasMany|BotAction|Collection
     */
    public function validUniqueActions(): HasMany
    {
        return $this->hasMany(BotAction::class)
            ->whereIn('handler', MessengerBots::getUniqueHandlerClasses());
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
        return htmlspecialchars($this->name);
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
        return MessengerFacade::getThreadStorage('disk');
    }

    /**
     * @return string
     */
    public function getStorageDirectory(): string
    {
        return MessengerFacade::getThreadStorage('directory')."/$this->thread_id/bots/$this->id";
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
     * @param  string  $size
     * @return string|null
     */
    public function getProviderAvatarRoute(string $size = 'sm'): ?string
    {
        return Helpers::route('assets.messenger.threads.bots.avatar.render',
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
        return MessengerProvider::OFFLINE;
    }

    /**
     * Are actions visible to regular participants?
     *
     * @param  Thread|null  $thread
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
     * @return string
     */
    public function getCooldownCacheKey(): string
    {
        return "bot:$this->id:cooldown";
    }

    /**
     * Does the bot have an active cooldown?
     *
     * @return bool
     */
    public function isOnCooldown(): bool
    {
        return Cache::has($this->getCooldownCacheKey());
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
     * Set the bot cooldown.
     */
    public function startCooldown(): void
    {
        if ($this->cooldown > 0) {
            Cache::put($this->getCooldownCacheKey(), true, now()->addSeconds($this->cooldown));
        }
    }

    /**
     * Release the bot cooldown.
     */
    public function releaseCooldown(): void
    {
        Cache::forget($this->getCooldownCacheKey());
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
