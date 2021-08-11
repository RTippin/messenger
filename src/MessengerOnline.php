<?php

namespace RTippin\Messenger;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Contracts\MessengerProvider;

trait MessengerOnline
{
    /**
     * Put the given or loaded model into cache as online.
     *
     * @param null|string|MessengerProvider $provider
     * @return $this
     */
    public function setProviderToOnline($provider = null): self
    {
        if ($this->isOnlineStatusEnabled()) {
            if (! $provider
                && $this->isProviderSet()
                && $this->getOnlineStatusSetting($this->getProvider()) !== 0) {
                if ($this->getOnlineStatusSetting($this->getProvider()) === 2) {
                    $this->setProviderToAway();
                } else {
                    Cache::put(
                        "{$this->getProviderAlias()}:online:{$this->getProvider()->getKey()}",
                        'online',
                        now()->addMinutes($this->getOnlineCacheLifetime())
                    );
                }
            } elseif ($provider
                && $this->isValidMessengerProvider($provider)
                && $this->getOnlineStatusSetting($provider) !== 0) {
                if ($this->getOnlineStatusSetting($provider) === 2) {
                    $this->setProviderToAway($provider);
                } else {
                    Cache::put(
                        "{$this->findProviderAlias($provider)}:online:{$provider->getKey()}",
                        'online',
                        now()->addMinutes($this->getOnlineCacheLifetime())
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Remove the given or loaded model from online cache.
     *
     * @param null|string|MessengerProvider $provider
     * @return $this
     */
    public function setProviderToOffline($provider = null): self
    {
        if ($this->isOnlineStatusEnabled()) {
            if (! $provider && $this->isProviderSet()) {
                Cache::forget("{$this->getProviderAlias()}:online:{$this->getProvider()->getKey()}");
            } elseif ($provider && $this->isValidMessengerProvider($provider)) {
                Cache::forget("{$this->findProviderAlias($provider)}:online:{$provider->getKey()}");
            }
        }

        return $this;
    }

    /**
     * Put the given or loaded model into cache as away.
     *
     * @param null|string|MessengerProvider $provider
     * @return $this
     */
    public function setProviderToAway($provider = null): self
    {
        if ($this->isOnlineStatusEnabled()) {
            if (! $provider
                && $this->isProviderSet()
                && $this->getOnlineStatusSetting($this->getProvider()) !== 0) {
                Cache::put(
                    "{$this->getProviderAlias()}:online:{$this->getProvider()->getKey()}",
                    'away',
                    now()->addMinutes($this->getOnlineCacheLifetime())
                );
            } elseif ($provider
                && $this->isValidMessengerProvider($provider)
                && $this->getOnlineStatusSetting($provider) !== 0) {
                Cache::put(
                    "{$this->findProviderAlias($provider)}:online:{$provider->getKey()}",
                    'away',
                    now()->addMinutes($this->getOnlineCacheLifetime())
                );
            }
        }

        return $this;
    }

    /**
     * Check if cache has online key for given or loaded model.
     *
     * @param null|string|MessengerProvider $provider
     * @return bool
     */
    public function isProviderOnline($provider = null): bool
    {
        if ($this->isOnlineStatusEnabled()) {
            if (! $provider && $this->isProviderSet()) {
                return Cache::get("{$this->getProviderAlias()}:online:{$this->getProvider()->getKey()}") === 'online';
            }

            return $this->isValidMessengerProvider($provider)
                && Cache::get("{$this->findProviderAlias($provider)}:online:{$provider->getKey()}") === 'online';
        }

        return false;
    }

    /**
     * Check if cache has away key for given or loaded model.
     *
     * @param null|string|MessengerProvider $provider
     * @return bool
     */
    public function isProviderAway($provider = null): bool
    {
        if ($this->isOnlineStatusEnabled()) {
            if (! $provider && $this->isProviderSet()) {
                return Cache::get("{$this->getProviderAlias()}:online:{$this->getProvider()->getKey()}") === 'away';
            }

            return $this->isValidMessengerProvider($provider)
                && Cache::get("{$this->findProviderAlias($provider)}:online:{$provider->getKey()}") === 'away';
        }

        return false;
    }

    /**
     * Get the status number representing online status of given or loaded model
     * 0 = offline, 1 = online, 2 = away.
     *
     * @param null|string|MessengerProvider $provider
     * @return int
     */
    public function getProviderOnlineStatus($provider = null): int
    {
        if ($this->isOnlineStatusEnabled()) {
            if (! $provider
                && $this->isProviderSet()
                && $self_cache = Cache::get("{$this->getProviderAlias()}:online:{$this->getProvider()->getKey()}")) {
                return $self_cache === 'online' ? 1 : 2;
            }
            if ($provider && $this->isValidMessengerProvider($provider)
                && $cache = Cache::get("{$this->findProviderAlias($provider)}:online:{$provider->getKey()}")) {
                return $cache === 'online' ? 1 : 2;
            }
        }

        return 0;
    }

    /**
     * @param MessengerProvider $provider
     * @return int
     */
    private function getOnlineStatusSetting(MessengerProvider $provider): int
    {
        return $this->getProviderMessenger($provider)->online_status;
    }
}
