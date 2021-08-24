<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RTippin\Messenger\Contracts\Ownerable;
use RTippin\Messenger\Database\Factories\CallParticipantFactory;
use RTippin\Messenger\Traits\HasOwner;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * @property string $id
 * @property string $call_id
 * @property \Illuminate\Support\Carbon|null $left_call
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \RTippin\Messenger\Models\Call $call
 * @mixin Model|\Eloquent
 * @property bool $kicked
 * @method static \Illuminate\Database\Eloquent\Builder|CallParticipant whereKicked($value)
 * @method static Builder|CallParticipant inCall()
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
     * @param Builder $query
     * @return Builder
     */
    public function scopeInCall(Builder $query): Builder
    {
        return $query->whereNull('left_call');
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
