<?php

namespace RTippin\Messenger;

use RTippin\Messenger\Contracts\MessengerProvider;
use Illuminate\Contracts\Cache\Repository;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @property-read Repository $cacheDriver
 */
trait MessengerOnlineInterface
{
    /**
     * Put the given or loaded model into cache as online
     *
     * @param null|MessengerProvider $provider
     * @return MessengerOnlineInterface
     */
    public function setProviderToOnline($provider = null): self
    {
        if($this->isOnlineStatusEnabled())
        {
            if(!$provider
                && $this->isProviderSet()
                && $this->getOnlineStatusSetting($this->getProvider()) !== 0)
            {
                if($this->getOnlineStatusSetting($this->getProvider()) === 2)
                {
                    $this->setProviderToAway();
                }
                else
                {
                    $this->cacheDriver->put(
                        "{$this->getProviderAlias()}:online:{$this->getProviderId()}",
                        'online',
                        now()->addMinutes($this->getOnlineCacheLifetime())
                    );
                }
            }

            else if($provider
                && $this->isValidMessengerProvider($provider)
                && $this->getOnlineStatusSetting($provider) !== 0)
            {
                if($this->getOnlineStatusSetting($this->getProvider()) === 2)
                {
                    $this->setProviderToAway($provider);
                }
                else
                {
                    $this->cacheDriver->put(
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
     * Remove the given or loaded model from online cache
     *
     * @param null|MessengerProvider $provider
     * @return MessengerOnlineInterface
     */
    public function setProviderToOffline($provider = null): self
    {
        if($this->isOnlineStatusEnabled())
        {
            if(!$provider && $this->isProviderSet())
            {
                $this->cacheDriver->forget("{$this->getProviderAlias()}:online:{$this->getProviderId()}");
            }

            else if($provider && $this->isValidMessengerProvider($provider))
            {
                $this->cacheDriver->forget("{$this->findProviderAlias($provider)}:online:{$provider->getKey()}");
            }
        }

        return $this;
    }

    /**
     * Put the given or loaded model into cache as away
     *
     * @param null|MessengerProvider $provider
     * @return MessengerOnlineInterface
     */
    public function setProviderToAway($provider = null): self
    {
        if($this->isOnlineStatusEnabled())
        {
            if(!$provider
                && $this->isProviderSet()
                && $this->getOnlineStatusSetting($this->getProvider()) !== 0)
            {
                $this->cacheDriver->put(
                    "{$this->getProviderAlias()}:online:{$this->getProviderId()}",
                    'away',
                    now()->addMinutes($this->getOnlineCacheLifetime())
                );
            }

            else if($provider
                && $this->isValidMessengerProvider($provider)
                && $this->getOnlineStatusSetting($provider) !== 0)
            {
                $this->cacheDriver->put(
                    "{$this->findProviderAlias($provider)}:online:{$provider->getKey()}",
                    'away',
                    now()->addMinutes($this->getOnlineCacheLifetime())
                );
            }
        }

        return $this;
    }

    /**
     * Check if cache has online key for given or loaded model
     *
     * @param null|MessengerProvider $provider
     * @return bool
     */
    public function isProviderOnline($provider = null): bool
    {
        if($this->isOnlineStatusEnabled())
        {
            try{
                if(!$provider && $this->isProviderSet())
                {
                    return $this->cacheDriver->get("{$this->getProviderAlias()}:online:{$this->getProviderId()}") === 'online';
                }

                return $this->isValidMessengerProvider($provider)
                    && $this->cacheDriver->get("{$this->findProviderAlias($provider)}:online:{$provider->getKey()}") === 'online';
            }catch(InvalidArgumentException $e){
                report($e);
            }
        }

        return false;
    }

    /**
     * Check if cache has away key for given or loaded model
     *
     * @param null|MessengerProvider $provider
     * @return bool
     */
    public function isProviderAway($provider = null): bool
    {
        if($this->isOnlineStatusEnabled())
        {
            try{
                if(!$provider && $this->isProviderSet())
                {
                    return $this->cacheDriver->get("{$this->getProviderAlias()}:online:{$this->getProviderId()}") === 'away';
                }

                return $this->isValidMessengerProvider($provider)
                    && $this->cacheDriver->get("{$this->findProviderAlias($provider)}:online:{$provider->getKey()}") === 'away';
            }catch(InvalidArgumentException $e){
                report($e);
            }
        }

        return false;
    }

    /**
     * Get the status number representing online status of given or loaded model
     * 0 = offline, 1 = online, 2 = away
     *
     * @param null|MessengerProvider $provider
     * @return int
     */
    public function getProviderOnlineStatus($provider = null): int
    {
        if($this->isOnlineStatusEnabled())
        {
            try{
                if (!$provider
                    && $this->isProviderSet()
                    && $self_cache = $this->cacheDriver->get("{$this->getProviderAlias()}:online:{$this->getProviderId()}")) {
                    return $self_cache === 'online' ? 1 : 2;
                }
                if($provider && $this->isValidMessengerProvider($provider)
                    && $cache = $this->cacheDriver->get("{$this->findProviderAlias($provider)}:online:{$provider->getKey()}"))
                {
                    return $cache === 'online' ? 1 : 2;
                }
            }catch(InvalidArgumentException $e){
                report($e);
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
        return $provider->messenger->online_status;
    }
}