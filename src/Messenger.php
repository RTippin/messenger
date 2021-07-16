<?php

namespace RTippin\Messenger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Support\Helpers;
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
     * @var Collection
     */
    private Collection $_providers;

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
     * TODO.
     *
     * @param array $providers
     */
    public function registerProviders(array $providers)
    {
        foreach ($providers as $provider) {
            if (! Helpers::checkImplementsInterface($provider, MessengerProvider::class)) {
                throw new InvalidArgumentException("The given provider { $provider } must implement our contract ".MessengerProvider::class);
            }

            $this->_providers[$provider] = $this->makeProviderSettings($provider);
        }
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

        $this->_providers = new Collection;
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

    /**
     * @param MessengerProvider|string $provider
     * @return array
     */
    private function makeProviderSettings(string $provider): array
    {
        $settings = $provider::getProviderSettings();

        return [
            'alias' => $settings['alias'] ?? Str::snake(class_basename($provider)),
            'morph_class' => $this->determineProviderMorphClass($provider),
            'searchable' => method_exists($provider, 'getProviderSearchableBuilder') && $settings['searchable'] ?? true,
            'friendable' => $settings['friendable'] ?? true,
            'devices' => $settings['devices'] ?? true,
            'default_avatar' => $settings['default_avatar'] ?? $this->getDefaultNotFoundImage(),
            'limit_can_message' => $settings['limit_can_message'] ?? [],
            'limit_can_search' => $settings['limit_can_search'] ?? [],
            'limit_can_friend' => $settings['limit_can_friend'] ?? [],
        ];
    }

    /**
     * Get the classname/alias for polymorphic relations.
     *
     * @param string $provider
     * @return string
     */
    private function determineProviderMorphClass(string $provider): string
    {
        $morphMap = Relation::morphMap();

        if (! empty($morphMap) && in_array($provider, $morphMap)) {
            return array_search($provider, $morphMap, true);
        }

        return $provider;
    }
}
