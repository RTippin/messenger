<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * App\GhostUser
 * @mixin \Eloquent
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
        'email' => 'ghost@example.org'
    ];

    /**
     * @return null
     */
    public function lastActiveDateTime()
    {
        return null;
    }

    /**
     * @param string $size
     * @param bool $api
     * @return string|null
     */
    public function getAvatarRoute(string $size = 'sm', $api = false)
    {
        return messengerRoute(($api ? 'api.' : '') . 'avatar.render',
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
     * @param bool $full
     * @return string
     */
    public function slug($full = false)
    {
        return 'ghost';
    }

    /**
     * @return string
     */
    public function name()
    {
        return "Ghost Profile";
    }
}
