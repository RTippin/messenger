<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Database\Factories\MessengerFactory;
use RTippin\Messenger\Traits\Uuids;

/**
 * App\Models\Messages\Messenger.
 *
 * @property string $id
 * @property string $owner_type
 * @property string|int $owner_id
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
 * @mixin Model|\Eloquent
 */
class Messenger extends Model
{
    use HasFactory;
    use Uuids;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'messengers';

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'message_popups' => true,
        'message_sound' => true,
        'call_ringtone_sound' => true,
        'notify_sound' => true,
        'online_status' => 1,
        'dark_mode' => true,
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
        'online_status' => 'integer',
    ];

    /**
     * @return MorphTo|MessengerProvider
     */
    public function owner()
    {
        return $this->morphTo();
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return MessengerFactory::new();
    }
}
