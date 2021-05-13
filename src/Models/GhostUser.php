<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;
use RTippin\Messenger\Support\Helpers;

/**
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
        'email' => 'ghost@example.org',
        'created_at' => null,
        'updated_at' => null,
    ];

    /**
     * @return string
     */
    public function getProviderName(): string
    {
        return 'Ghost Profile';
    }

    /**
     * @return string
     */
    public function getProviderAvatarColumn(): string
    {
        return 'updated_at';
    }

    /**
     * @return string
     */
    public function getProviderLastActiveColumn(): string
    {
        return 'updated_at';
    }

    /**
     * @return string|null
     */
    public function getProviderProfileRoute(): ?string
    {
        return null;
    }

    /**
     * @param string $size
     * @param bool $api
     * @return string|null
     */
    public function getProviderAvatarRoute(string $size = 'sm', bool $api = false): ?string
    {
        return Helpers::Route(($api ? 'api.' : '').'avatar.render',
            [
                'alias' => 'ghost',
                'id' => 'ghost',
                'size' => $size,
                'image' => 'default.png',
            ]
        );
    }

    /**
     * @return int
     */
    public function getProviderOnlineStatus(): int
    {
        return 0;
    }

    /**
     * @return string
     */
    public function getProviderOnlineStatusVerbose(): string
    {
        return 'offline';
    }
}
