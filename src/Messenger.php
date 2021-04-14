<?php

namespace RTippin\Messenger;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use RTippin\Messenger\Support\ProvidersVerification;

/**
 * Laravel Messenger System.
 * Created by: Richard Tippin.
 */
final class Messenger
{
    use MessengerProviders;
    use MessengerConfig;
    use MessengerOnline;

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
     * @var ProvidersVerification
     */
    private ProvidersVerification $providersVerification;

    /**
     * Messenger constructor.
     * Load config values to use at runtime.
     *
     * @param Application $app
     * @param CacheRepository $cacheDriver
     * @param Filesystem $filesystem
     * @param ConfigRepository $configRepo
     * @param ProvidersVerification $providersVerification
     */
    public function __construct(Application $app,
                                CacheRepository $cacheDriver,
                                Filesystem $filesystem,
                                ConfigRepository $configRepo,
                                ProvidersVerification $providersVerification)
    {
        $this->app = $app;
        $this->cacheDriver = $cacheDriver;
        $this->filesystem = $filesystem;
        $this->configRepo = $configRepo;
        $this->providersVerification = $providersVerification;
        $this->boot();
    }

    /**
     * Check if provider is valid by seeing if alias exist.
     *
     * @param mixed $provider
     * @return bool
     */
    public function isValidMessengerProvider($provider = null): bool
    {
        return (bool) $this->findProviderAlias($provider);
    }

    /**
     * Get the defined alias of the provider class defined in config.
     *
     * @param mixed $provider
     * @return string|null
     */
    public function findProviderAlias($provider = null): ?string
    {
        return $this->providers->search(fn ($item) => $item['model'] === $this->getClassNameString($provider)) ?: null;
    }

    /**
     * Get the provider class of the alias defined in the config.
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
     * Determine if the provider is searchable.
     *
     * @param mixed $provider
     * @return bool
     */
    public function isProviderSearchable($provider = null): bool
    {
        return (bool) $this->providers->search(fn ($item) => $item['model'] === $this->getClassNameString($provider) && $item['searchable'] === true);
    }

    /**
     * Determine if the provider is friendable.
     *
     * @param mixed $provider
     * @return bool
     * @noinspection SpellCheckingInspection
     */
    public function isProviderFriendable($provider = null): bool
    {
        return (bool) $this->providers->search(fn ($item) => $item['model'] === $this->getClassNameString($provider) && $item['friendable'] === true);
    }

    /**
     * @param string $alias
     * @return string|null
     */
    public function getProviderDefaultAvatarPath(string $alias): ?string
    {
        return $this->providers->has($alias)
            ? $this->providers->get($alias)['default_avatar']
            : null;
    }

    /**
     * @return array
     */
    public function getAllSearchableProviders(): array
    {
        return $this->providers
            ->filter(fn ($provider) => $provider['searchable'] === true)
            ->map(fn ($provider) => $provider['model'])
            ->flatten()
            ->toArray();
    }

    /**
     * @return array
     * @noinspection SpellCheckingInspection
     */
    public function getAllFriendableProviders(): array
    {
        return $this->providers
            ->filter(fn ($provider) => $provider['friendable'] === true)
            ->map(fn ($provider) => $provider['model'])
            ->flatten()
            ->toArray();
    }

    /**
     * @return array
     */
    public function getAllMessengerProviders(): array
    {
        return $this->providers
            ->map(fn ($provider) => $provider['model'])
            ->flatten()
            ->toArray();
    }

    /**
     * @return array
     */
    public function getAllProvidersWithDevices(): array
    {
        return $this->providers
            ->filter(fn ($provider) => $provider['devices'] === true)
            ->map(fn ($provider) => $provider['model'])
            ->flatten()
            ->toArray();
    }

    /**
     * Return the current instance of messenger.
     */
    public function instance(): self
    {
        return $this;
    }

    /**
     * Reset all values back to default.
     */
    public function reset(): void
    {
        $this->ghost = null;

        $this->ghostParticipant = null;

        $this->unsetProvider()->boot();
    }

    /**
     * Boot up our configs.
     */
    private function boot(): void
    {
        $this->isProvidersCached = $this->isProvidersCached();

        $this->setMessengerProviders();

        $this->setMessengerConfig();
    }

    /**
     * @param $provider
     * @return string|null
     */
    private function getClassNameString($provider = null): ?string
    {
        return is_object($provider)
            ? get_class($provider)
            : $provider;
    }
}
