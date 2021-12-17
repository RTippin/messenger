<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Contracts\Ownerable;
use RTippin\Messenger\Database\Factories\BotActionFactory;
use RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Support\BotActionHandler;
use RTippin\Messenger\Traits\HasOwner;
use RTippin\Messenger\Traits\Uuids;

/**
 * @mixin Model|\Eloquent
 *
 * @property string $id
 * @property string|int $bot_id
 * @property string|BotActionHandler $handler
 * @property string $triggers
 * @property string|null $payload
 * @property bool $admin_only
 * @property string $match
 * @property int $cooldown
 * @property bool $enabled
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|Bot $bot
 *
 * @method static Builder|BotAction enabled()
 * @method static Builder|BotAction validHandler()
 * @method static Builder|BotAction handler(string $handler)
 * @method static Builder|BotAction validFromThread(string $threadId)
 * @method static Builder|BotAction fromThread(string $threadId, bool $unique = false)
 * @method static Builder|BotAction uniqueFromThread(string $threadId)
 * @method static BotActionFactory factory(...$parameters)
 */
class BotAction extends Model implements Ownerable
{
    use HasFactory,
        HasOwner,
        Uuids;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'bot_actions';

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
        'admin_only' => 'boolean',
        'enabled' => 'boolean',
        'cooldown' => 'integer',
    ];

    /**
     * @param  string  $threadId
     * @return string
     */
    public static function getActionsForThreadCacheKey(string $threadId): string
    {
        return "thread:$threadId:bot:actions";
    }

    /**
     * @param  string  $threadId
     */
    public static function clearActionsCacheForThread(string $threadId): void
    {
        Cache::forget(self::getActionsForThreadCacheKey($threadId));
    }

    /**
     * @param  string  $threadId
     * @return Collection
     */
    public static function getActionsWithBotFromThread(string $threadId): Collection
    {
        return Cache::remember(
            self::getActionsForThreadCacheKey($threadId),
            now()->addDay(),
            fn () => self::validFromThread($threadId)->with('bot')->get()
        );
    }

    /**
     * @param  Thread  $thread
     * @return array
     */
    public static function getUniqueHandlersInThread(Thread $thread): array
    {
        return self::uniqueFromThread($thread->id)
            ->select(['handler'])
            ->get()
            ->map(fn (BotAction $action) => $action->handler)
            ->toArray();
    }

    /**
     * Combine the final triggers to be a single string, separated by the
     * pipe (|), and removing duplicates.
     *
     * @param  null|string|array  $triggers
     * @return string|null
     */
    public static function formatTriggers($triggers): ?string
    {
        if (is_null($triggers)) {
            return null;
        }

        $triggers = is_array($triggers)
            ? implode('|', $triggers)
            : $triggers;

        return \Illuminate\Support\Collection::make(preg_split('/[|,]/', $triggers))
            ->map(fn ($item) => trim($item))
            ->unique()
            ->filter()
            ->implode('|');
    }

    /**
     * @return BelongsTo|Bot
     */
    public function bot(): BelongsTo
    {
        return $this->belongsTo(Bot::class);
    }

    /**
     * Scope actions that are enabled.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', '=', true);
    }

    /**
     * Scope actions that have a valid handler set.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeValidHandler(Builder $query): Builder
    {
        return $query->whereIn('handler', MessengerBots::getHandlerClasses());
    }

    /**
     * Scope actions that have a valid handler set.
     *
     * @param  Builder  $query
     * @param  string  $handler
     * @return Builder
     */
    public function scopeHandler(Builder $query, string $handler): Builder
    {
        return $query->where('handler', '=', $handler);
    }

    /**
     * Scope actions that belong to an enabled bot from inside the provided thread.
     *
     * @param  Builder  $query
     * @param  string  $threadId
     * @return Builder
     */
    public function scopeValidFromThread(Builder $query, string $threadId): Builder
    {
        return $query->select('bot_actions.*')
            ->join('bots', 'bot_actions.bot_id', '=', 'bots.id')
            ->where('bots.thread_id', '=', $threadId)
            ->where('bot_actions.enabled', '=', true)
            ->where('bots.enabled', '=', true)
            ->whereNull('bots.deleted_at')
            ->whereIn('bot_actions.handler', MessengerBots::getHandlerClasses());
    }

    /**
     * Scope actions that belong inside the provided thread.
     *
     * @param  Builder  $query
     * @param  string  $threadId
     * @param  bool  $unique
     * @return Builder
     */
    public function scopeFromThread(Builder $query,
                                    string $threadId,
                                    bool $unique = false): Builder
    {
        return $query->select('bot_actions.*')
            ->join('bots', 'bot_actions.bot_id', '=', 'bots.id')
            ->where('bots.thread_id', '=', $threadId)
            ->whereNull('bots.deleted_at')
            ->whereIn(
                'bot_actions.handler',
                $unique
                    ? MessengerBots::getUniqueHandlerClasses()
                    : MessengerBots::getHandlerClasses()
            );
    }

    /**
     * Scope actions flagged as unique that belong inside the provided thread.
     *
     * @param  Builder  $query
     * @param  string  $threadId
     * @return Builder
     */
    public function scopeUniqueFromThread(Builder $query, string $threadId): Builder
    {
        return $this->fromThread($threadId, true);
    }

    /**
     * Get all triggers for the action.
     *
     * @return array
     */
    public function getTriggers(): array
    {
        if (optional($this->getHandler())->triggers) {
            return $this->getHandler()->triggers;
        }

        return ! is_null($this->triggers)
            ? explode('|', $this->triggers)
            : [];
    }

    /**
     * @return string
     */
    public function getMatchMethod(): string
    {
        return optional($this->getHandler())->matchMethod ?: $this->match;
    }

    /**
     * Get the handler DTO.
     *
     * @return BotActionHandlerDTO|null
     */
    public function getHandler(): ?BotActionHandlerDTO
    {
        return MessengerBots::getHandler($this->handler);
    }

    /**
     * @return string|null
     */
    public function getMatchDescription(): ?string
    {
        return MessengerBots::getMatchDescription($this->match);
    }

    /**
     * @return mixed|null
     */
    public function getPayload(?string $key = null)
    {
        if (is_null($this->payload)) {
            return null;
        }

        $payload = json_decode($this->payload, true);

        if (! is_null($payload) && ! is_null($key)) {
            return $payload[$key];
        }

        return $payload;
    }

    /**
     * @return string
     */
    public function getCooldownCacheKey(): string
    {
        return "bot:$this->bot_id:$this->id:cooldown";
    }

    /**
     * Does the action have an active cooldown?
     *
     * @return bool
     */
    public function isOnCooldown(): bool
    {
        return Cache::has($this->getCooldownCacheKey());
    }

    /**
     * Is the action available?
     *
     * @return bool
     */
    public function notOnCooldown(): bool
    {
        return ! $this->isOnCooldown();
    }

    /**
     * Does the action or the action's bot have an active cooldown?
     *
     * @return bool
     */
    public function isOnAnyCooldown(): bool
    {
        return $this->isOnCooldown() || $this->bot->isOnCooldown();
    }

    /**
     * Is the action and bot available?
     *
     * @return bool
     */
    public function notOnAnyCooldown(): bool
    {
        return ! $this->isOnAnyCooldown();
    }

    /**
     * Set the action cooldown.
     */
    public function startCooldown(): void
    {
        if ($this->cooldown > 0) {
            Cache::put($this->getCooldownCacheKey(), true, now()->addSeconds($this->cooldown));
        }
    }

    /**
     * Release the action cooldown.
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
        return BotActionFactory::new();
    }
}
