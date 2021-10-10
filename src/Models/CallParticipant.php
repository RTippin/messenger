<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Contracts\Ownerable;
use RTippin\Messenger\Database\Factories\CallParticipantFactory;
use RTippin\Messenger\Traits\HasOwner;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * @mixin Model|\Eloquent
 *
 * @property string $id
 * @property string $call_id
 * @property bool $kicked
 * @property Carbon|null $left_call
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Call $call
 *
 * @method static Builder|CallParticipant whereKicked($value)
 * @method static Builder|CallParticipant inCall()
 * @method static CallParticipantFactory factory(...$parameters)
 */
class CallParticipant extends Model implements Ownerable
{
    use HasFactory,
        HasOwner,
        ScopesProvider,
        Uuids;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'call_participants';

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    public $keyType = 'string';

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $casts = [
        'kicked' => 'boolean',
        'left_call' => 'datetime',
    ];

    /**
     * @return BelongsTo|Call
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Scope a query for only video calls.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeInCall(Builder $query): Builder
    {
        return $query->whereNull('left_call');
    }

    /**
     * @return string
     */
    public function getParticipantInCallCacheKey(): string
    {
        return "call:$this->call_id:$this->id";
    }

    /**
     * @return bool
     */
    public function isParticipantInCallCache(): bool
    {
        return Cache::has($this->getParticipantInCallCacheKey());
    }

    /**
     * Put the participant's key in cache so that we may tell if they
     * left the call or became inactive without a proper post to the
     * backend (left_call null).
     */
    public function setParticipantInCallCache(): void
    {
        Cache::put($this->getParticipantInCallCacheKey(), true, 60);
    }

    /**
     * Remove the participant's key from cache.
     */
    public function removeParticipantInCallCache(): void
    {
        Cache::forget($this->getParticipantInCallCacheKey());
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return CallParticipantFactory::new();
    }
}
