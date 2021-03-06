<?php

namespace RTippin\Messenger;

use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Support\ProvidersVerification;

/**
 * Laravel Messenger System.
 * Created by: Richard Tippin.
 */
final class Messenger
{
    use MessengerProviders,
        MessengerConfig,
        MessengerOnline;

    /**
     * @var ProvidersVerification
     */
    private ProvidersVerification $providersVerification;

    /**
     * Messenger constructor.
     * Load config values to use at runtime.
     *
     * @param ProvidersVerification $providersVerification
     */
    public function __construct(ProvidersVerification $providersVerification)
    {
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
        return $this->providers->search(function ($item) use ($provider) {
            return $item['morph_class'] === $this->getClassNameString($provider)
                || $item['model'] === $this->getClassNameString($provider);
        }) ?: null;
    }

    /**
     * Get the provider class of the alias defined in the config.
     *
     * @param string $alias
     * @return string|null
     */
    public function findAliasProvider(string $alias): ?string
    {
        if ($this->providers->has($alias)) {
            return $this->providers->get($alias)['model'];
        }

        if (! is_null($morphAlias = $this->findProviderAlias($alias))) {
            return $this->providers->get($morphAlias)['model'];
        }

        return null;
    }

    /**
     * Determine if the provider is searchable.
     *
     * @param mixed $provider
     * @return bool
     */
    public function isProviderSearchable($provider = null): bool
    {
        return (bool) $this->providers->search(function ($item) use ($provider) {
            return ($item['morph_class'] === $this->getClassNameString($provider)
                    || $item['model'] === $this->getClassNameString($provider))
                && $item['searchable'] === true;
        });
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
        return (bool) $this->providers->search(function ($item) use ($provider) {
            return ($item['morph_class'] === $this->getClassNameString($provider)
                    || $item['model'] === $this->getClassNameString($provider))
                && $item['friendable'] === true;
        });
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
            ->map(fn ($provider) => $provider['morph_class'])
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
            ->map(fn ($provider) => $provider['morph_class'])
            ->flatten()
            ->toArray();
    }

    /**
     * @return array
     */
    public function getAllMessengerProviders(): array
    {
        return $this->providers
            ->map(fn ($provider) => $provider['morph_class'])
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
            ->map(fn ($provider) => $provider['morph_class'])
            ->flatten()
            ->toArray();
    }

    /**
     * Return the current instance of messenger.
     */
    public function getInstance(): self
    {
        return $this;
    }

    /**
     * Reset all values back to default.
     */
    public function reset(): void
    {
        $this->ghost = null;

        $this->ghostBot = null;

        $this->ghostParticipant = null;

        $this->unsetProvider()->boot();
    }

    /**
     * Boot up our configs.
     */
    private function boot(): void
    {
        $this->isProvidersCached = $this->isProvidersCached();

        $this->setMessengerConfig();

        $this->setMessengerProviders();
    }

    /**
     * @param $provider
     * @return Model|string|null
     */
    private function getClassNameString($provider = null): ?string
    {
        return is_object($provider)
        && $provider instanceof Model
            ? $provider->getMorphClass()
            : $provider;
    }
}
