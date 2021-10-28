<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Support\Helpers;

/**
 * @mixin Model
 *
 * @method static getProviderSearchableBuilder(Builder $query, string $search, array $searchItems)
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
     * Get the provider settings and alias override, if set.
     *
     * @return array
     */
    public static function getProviderSettings(): array
    {
        return [
            'alias' => null,
            'searchable' => true,
            'friendable' => true,
            'devices' => true,
            'default_avatar' => public_path('vendor/messenger/images/users.png'),
            'cant_message_first' => [],
            'cant_search' => [],
            'cant_friend' => [],
        ];
    }

    /**
     * If your provider has a route/slug for a profile page,
     * return that route here.
     *
     * @return string|null
     */
    public function getProviderProfileRoute(): ?string
    {
        return null;
    }

    /**
     * Format and return your provider name here.
     * ex: $this->first . ' ' . $this->last.
     *
     * @return string
     */
    public function getProviderName(): string
    {
        return strip_tags(ucwords($this->name));
    }

    /**
     * The column name your providers avatar is stored in the database as.
     *
     * @return string
     */
    public function getProviderAvatarColumn(): string
    {
        return 'picture';
    }

    /**
     * The column name your provider has in the database that we will use to
     * show last active, and touch / update timestamp when using our online
     * heartbeat. This should be a timestamp column.
     *
     * @return string
     */
    public function getProviderLastActiveColumn(): string
    {
        return 'updated_at';
    }

    /**
     * Get the route of the avatar for your provider. We will call this
     * from our resource classes using sm/md/lg .
     *
     * @param  string  $size
     * @return string|null
     */
    public function getProviderAvatarRoute(string $size = 'sm'): ?string
    {
        return Helpers::route('assets.messenger.provider.avatar.render',
            [
                'alias' => Messenger::findProviderAlias($this),
                'id' => $this->getKey(),
                'size' => $size,
                'image' => $this->{$this->getProviderAvatarColumn()} ?: 'default.png',
            ]
        );
    }

    /**
     * Returns online status of your provider.
     * 0 - offline, 1 - online, 2 - away.
     *
     * @return int
     */
    public function getProviderOnlineStatus(): int
    {
        if (! is_null($this->isOnlineCache)) {
            return $this->isOnlineCache;
        }

        return $this->isOnlineCache = Messenger::getProviderOnlineStatus($this);
    }

    /**
     * Verbose meaning of the online status number.
     *
     * @deprecated To be removed in v2. No longer used in the backend.
     *
     * @return string
     */
    public function getProviderOnlineStatusVerbose(): string
    {
        return Str::lower(MessengerProvider::ONLINE_STATUS[$this->getProviderOnlineStatus()]);
    }
}
