<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RTippin\Messenger\Definitions;

/**
 * App\Traits\Messageable
 *
 * @mixin Model
 * @noinspection SpellCheckingInspection
 */

trait Messageable
{
    /**
     * @var null|int
     */
    public ?int $isOnlineCache = null;

    /**
     * @var null|string
     */
    public ?string $onlineStatusCache = null;

    /**
     * @param bool $full
     * @return string
     */
    public function slug($full = false)
    {
        return $full
            ? route('model_profile', [
                    messenger()->findProviderAlias($this),
                    $this->slug
                ], false)
            : $this->slug;
    }

    /**
     * @param string $size
     * @return string|null
     */
    public function getAvatarRoute(string $size = 'sm')
    {
        return messengerRoute('avatar.render',
            [
                'alias' => messenger()->findProviderAlias($this),
                'id' => $this->getKey(),
                'size' => $size,
                'image' => $this->picture ? $this->picture : 'default.png'
            ]
        );
    }

    /**
     * @return int
     */
    public function onlineStatus()
    {
        if( ! is_null($this->isOnlineCache))
        {
            return $this->isOnlineCache;
        }

        $this->isOnlineCache = messenger()->getProviderOnlineStatus($this);

        return $this->isOnlineCache;
    }

    /**
     * @return string
     */
    public function onlineStatusVerbose()
    {
        return Str::lower(Definitions::OnlineStatus[$this->onlineStatus()]);
    }

    /**
     * @return mixed
     */
    public function lastActiveDateTime()
    {
        return $this->updated_at;
    }
}