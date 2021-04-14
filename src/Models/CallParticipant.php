<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Database\Factories\CallParticipantFactory;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * App\Models\Messages\CallParticipant.
 *
 * @property string $id
 * @property string $call_id
 * @property string|int $owner_id
 * @property string $owner_type
 * @property \Illuminate\Support\Carbon|null $left_call
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \RTippin\Messenger\Models\Call $call
 * @mixin Model|\Eloquent
 * @property-read Model|MessengerProvider $owner
 * @property bool $kicked
 * @method static \Illuminate\Database\Eloquent\Builder|CallParticipant whereKicked($value)
 * @method static Builder|CallParticipant inCall()
 * @method static Builder|CallParticipant hasProvider(string $relation, MessengerProvider $provider)
 * @method static Builder|CallParticipant forProvider(MessengerProvider $provider, string $morph = 'owner')
 * @method static Builder|CallParticipant notProvider(MessengerProvider $provider, string $morph = 'owner')
 */
class CallParticipant extends Model
{
    use HasFactory;
    use Uuids;
    use ScopesProvider;

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
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['left_call'];

    /**
     * @var array
     */
    protected $casts = [
        'kicked' => 'boolean',
    ];

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
     * @return BelongsTo|Call
     */
    public function call()
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
