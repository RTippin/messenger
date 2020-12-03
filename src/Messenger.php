<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Filesystem\Filesystem;
use RTippin\Messenger\Contracts\MessengerInterface;

/**
 * Class MessengerService
 * @package App\Services\MessengerSystem
 */

final class Messenger implements MessengerInterface
{
    /**
     * MessengerService Provider Verifications
     */
    use MessengerProviderVerification;

    /**
     * MessengerService Provider Actions
     */
    use MessengerProviderInterface;

    /**
     * MessengerService Config Actions
     */
    use MessengerConfigInterface;

    /**
     * MessengerService Provider Online Actions
     */
    use MessengerOnlineInterface;

    /**
     * @var Application
     */
    private Application $app;

    /**
     * @var CacheRepository
     */
    private CacheRepository $cacheDriver;

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var ConfigRepository
     */
    private ConfigRepository $configRepo;

    /**
     * MessengerService constructor.
     * Load config values to use at runtime
     *
     * @param Application $app
     * @param CacheRepository $cacheDriver
     * @param Filesystem $filesystem
     * @param ConfigRepository $configRepo
     */
    public function __construct(Application $app,
                                CacheRepository $cacheDriver,
                                Filesystem $filesystem,
                                ConfigRepository $configRepo)
    {
        $this->app = $app;
        $this->cacheDriver = $cacheDriver;
        $this->filesystem = $filesystem;
        $this->configRepo = $configRepo;

        $this->boot();
    }

    /**
     * Boot up our configs
     */
    private function boot(): void
    {
        $this->isProvidersCached = $this->isProvidersCached();

        $this->setMessengerProviders();

        $this->setMessengerConfig();
    }

    /**
     * Check if provider is valid by seeing if alias exist
     *
     * @param mixed $provider
     * @return bool
     */
    public function isValidMessengerProvider($provider = null): bool
    {
        return $this->findProviderAlias($provider)
            ? true
            : false;
    }

    /**
     * Get the defined alias of the provider class defined in config
     *
     * @param mixed $provider
     * @return string|null
     */
    public function findProviderAlias($provider = null): ?string
    {
        return $this->providers->search(
            fn($item) => $item['model'] === $this->getClassNameString($provider)
        ) ?: null;
    }

    /**
     * Get the provider class of the alias defined in the config
     *
     * @param string $alias
     * @return string|null
     */
    public function findAliasProvider(string $alias): ?string
    {
        return $this->providers->has($alias)
            ? $this->providers->get($alias)['model']
            : null;
    }

    /**
     * Determine if the provider is searchable
     *
     * @param mixed $provider
     * @return bool
     */
    public function isProviderSearchable($provider = null): bool
    {
        return $this->providers->search(fn ($item) =>
            $item['model'] === $this->getClassNameString($provider)
            && $item['searchable'] === true
        ) ? true : false;
    }

    /**
     * Determine if the provider is friendable
     *
     * @param mixed $provider
     * @return bool
     * @noinspection SpellCheckingInspection
     */
    public function isProviderFriendable($provider = null): bool
    {
        return $this->providers->search(fn ($item) =>
            $item['model'] === $this->getClassNameString($provider)
            && $item['friendable'] === true
        ) ? true : false;
    }
}