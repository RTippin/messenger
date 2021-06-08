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
     * @var string
     */
    private string $name = 'Ghost Profile';

    /**
     * @var bool
     */
    private bool $ghostBot = false;

    /**
     * Set the ghost to being a bot.
     */
    public function ghostBot(): self
    {
        $this->ghostBot = true;

        $this->name = 'Bot';

        return $this;
    }

    /**
     * @return string
     */
    public function getProviderName(): string
    {
        return $this->name;
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
                'alias' => $this->ghostBot ? 'bot' : 'ghost',
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
