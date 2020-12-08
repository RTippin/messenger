<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RTippin\Messenger\Definitions;

/**
 * App\Traits\Messageable.
 *
 * @mixin Model
 * @noinspection SpellCheckingInspection
 */
trait Messageable
{
    /**
     * When calling for isOnline, we cache on the model as it is common
     * for the method to be called multiple times in collections.
     *
     * @var null|int
     */
    public ?int $isOnlineCache = null;

    /**
     * When calling for onlineStatus, we cache on the model as it is common
     * for the method to be called multiple times in collections.
     *
     * @var null|string
     */
    public ?string $onlineStatusCache = null;

    /**
     * If your provider has a route/slug for a profile page,
     * return that route here.
     *
     * @return string|null
     */
    public function getRoute(): ?string
    {
        return null;
    }

    /**
     * Format and return your provider name here.
     * ex: $this->first . ' ' . $this->last.
     *
     * @return string
     */
    public function name(): string
    {
        return strip_tags(ucwords($this->name));
    }

    /**
     * The column name your providers avatar is stored in the database as.
     *
     * @return string
     */
    public function getAvatarColumn(): string
    {
        return 'picture';
    }

    /**
     * Get the route of the avatar for your provider. We will call this
     * from our resource classes using sm/md/lg .
     *
     * @param string $size
     * @param bool $api
     * @return string|null
     */
    public function getAvatarRoute(string $size = 'sm', $api = false): ?string
    {
        return messengerRoute(($api ? 'api.' : '').'avatar.render',
            [
                'alias' => messenger()->findProviderAlias($this),
                'id' => $this->getKey(),
                'size' => $size,
                'image' => $this->{$this->getAvatarColumn()} ? $this->{$this->getAvatarColumn()} : 'default.png',
            ]
        );
    }

    /**
     * Returns online status of your provider.
     * 0 - offline, 1 - online, 2 - away.
     *
     * @return int
     */
    public function onlineStatus(): int
    {
        if (! is_null($this->isOnlineCache)) {
            return $this->isOnlineCache;
        }

        $this->isOnlineCache = messenger()->getProviderOnlineStatus($this);

        return $this->isOnlineCache;
    }

    /**
     * Verbose meaning of the online status number.
     *
     * @return string
     */
    public function onlineStatusVerbose(): string
    {
        return Str::lower(Definitions::OnlineStatus[$this->onlineStatus()]);
    }

    /**
     * Return a last active time from your model. We usually use updated_at
     * as we touch your provider model when the heartbeat endpoint is hit.
     *
     * @return mixed
     */
    public function lastActiveDateTime()
    {
        return $this->updated_at;
    }
}
