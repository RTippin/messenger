<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RTippin\Messenger\Traits\Uuids;

/**
 * App\Models\Messages\MessageEdit.
 *
 * @property string $id
 * @property string $message_id
 * @property string $body
 * @property \Illuminate\Support\Carbon|null $edited_at
 * @property-read \RTippin\Messenger\Models\Message $message
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereMessageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereEditedAt($value)
 * @mixin Model|\Eloquent
 */
class MessageEdit extends Model
{
    use Uuids;

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
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'message_edits';

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
    protected $dates = ['edited_at'];

    /**
     * @return BelongsTo|Message
     */
    public function message()
    {
        return $this->belongsTo(
            Message::class,
            'message_id',
            'id'
        );
    }
}
