<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RTippin\Messenger\Contracts\MessengerProvider;

/**
 * App\Models\Messages\Messenger.
 *
 * @property string $owner_type
 * @property string $owner_id
 * @property bool $message_popups
 * @property bool $message_sound
 * @property bool $call_ringtone_sound
 * @property bool $notify_sound
 * @property bool $dark_mode
 * @property int $online_status
 * @property string|null $ip
 * @property string|null $timezone
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model $owner
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger query()
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger whereCallRingtoneSound($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger whereDarkMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger whereIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger whereMessagePopups($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger whereMessageSound($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger whereNotifySound($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger whereOnlineStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger whereOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Messenger whereUpdatedAt($value)
 * @mixin Model|\Eloquent
 */
class Messenger extends Model
{
    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'message_popups' => 1,
        'message_sound' => 1,
        'call_ringtone_sound' => 1,
        'notify_sound' => 1,
        'online_status' => 1,
        'dark_mode' => 1,
        'ip' => null,
        'timezone' => null,
    ];

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    public $keyType = 'string';

    /**
     * @var string
     */
    protected $primaryKey = 'owner_id';
    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $hidden = [
        'ip',
        'timezone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'message_popups' => 'boolean',
        'message_sound' => 'boolean',
        'call_ringtone_sound' => 'boolean',
        'notify_sound' => 'boolean',
        'dark_mode' => 'boolean',
    ];

    /**
     * @return MorphTo|MessengerProvider
     */
    public function owner()
    {
        return $this->morphTo();
    }
}
