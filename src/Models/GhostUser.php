<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\GhostUser
 *
 * @property-read string $avatar
 * @property-read string $j_s_name
 * @property-read Messenger $messenger
 * @property-read string $name
 * @property-read int $online_status_number
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\GhostUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\GhostUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\RTippin\Messenger\Models\GhostUser query()
 * @mixin Eloquent
 * @property-read \Illuminate\Database\Eloquent\Collection|GhostUser[] $devices
 * @property-read int|null $devices_count
 */
class GhostUser extends Eloquent
{
    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var string
     */
    public $keyType = 'string';

    /**
     * @var array
     */
    protected $attributes = [
        'id' => '12345678-1234-5678-9123-123456789874',
        'first' => 'Ghost',
        'last' => 'Profile',
        'slug' => 'ghost',
        'picture' => null,
        'email' => 'ghost@flipsetter.com'
    ];

    /**
     * @return Messenger
     */
    public function getMessengerAttribute()
    {
        return new Messenger([
            'owner_id' => '12345678-1234-5678-9123-123456789874',
            'owner_type' => 'App\GhostUser',
            'message_popups' => 0,
            'message_sound' => 0,
            'call_ringtone_sound' => 0,
            'notify_sound' => 0,
            'online_status' => 0,
            'knoks' => 0,
            'friend_approval' => 0,
            'calls_outside_networks' => 0,
            'dark_mode' => 0,
            'ip' => null,
            'timezone' => null
        ]);
    }

    /**
     * @return string|null
     */
    public function providerAlias()
    {
        return 'ghost';
    }

    /**
     * @return null
     */
    public function lastActiveDateTime()
    {
        return null;
    }

    /**
     * @param string $size
     * @return string|null
     */
    public function getAvatarRoute(string $size = 'sm')
    {
        return messengerRoute('avatar.render',
            [
                'alias' => 'ghost',
                'id' => 'ghost',
                'size' => $size,
                'image' => 'default.png'
            ]
        );
    }

    /**
     * @return int
     */
    public function onlineStatus()
    {
        return 0;
    }

    /**
     * @return string
     */
    public function onlineStatusVerbose()
    {
        return 'offline';
    }

    /**
     * @return HasMany
     */
    public function devices()
    {
        return $this->newHasMany($this->newQuery(), $this, '', '');
    }

    /**
     * @param bool $full
     * @return string
     */
    public function slug($full = false)
    {
        return $full ? route(
            'model_profile',
            [
                'ghost',
                'ghost'
            ],
            false)
            : 'ghost';
    }

    /**
     * @return string
     */
    public function getNameAttribute()
    {
        return $this->name();
    }

    /**
     * @return string
     */
    public function name()
    {
        return "Ghost Profile";
    }

    /**
     * @return string
     */
    public function getJSNameAttribute()
    {
        return $this->name();
    }
}
