<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\Ownerable;
use RTippin\Messenger\Database\Factories\MessageReactionFactory;
use RTippin\Messenger\Traits\HasOwner;
use RTippin\Messenger\Traits\ScopesProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * @mixin Model|\Eloquent
 *
 * @property string $id
 * @property string $message_id
 * @property string $reaction
 * @property Carbon|null $created_at
 * @property-read Message $message
 *
 * @method static Builder|MessageReaction whereReaction(string $reaction)
 * @method static Builder|MessageReaction notReaction(string $reaction)
 * @method static MessageReactionFactory factory(...$parameters)
 */
class MessageReaction extends Model implements Ownerable
{
    use HasFactory,
        HasOwner,
        Uuids,
        ScopesProvider;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'message_reactions';

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
     * @var bool
     */
    public $timestamps = false;

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
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo|Message
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Scope a query for matching reactions.
     *
     * @param  Builder  $query
     * @param  string  $reaction
     * @return Builder
     */
    public function scopeWhereReaction(Builder $query, string $reaction): Builder
    {
        return $query->where('reaction', '=', $reaction);
    }

    /**
     * Scope a query for matching reactions.
     *
     * @param  Builder  $query
     * @param  string  $reaction
     * @return Builder
     */
    public function scopeNotReaction(Builder $query, string $reaction): Builder
    {
        return $query->where('reaction', '!=', $reaction);
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return MessageReactionFactory::new();
    }
}
