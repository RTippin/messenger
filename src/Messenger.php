<?php

namespace RTippin\Messenger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Bot;

/**
 * Laravel Messenger System.
 * Created by: Richard Tippin.
 */
final class Messenger
{
    use MessengerProviders,
        MessengerConfig;

    /**
     * @var Collection
     */
    private Collection $providers;

    /**
     * Messenger constructor.
     */
    public function __construct()
    {
        $this->providers = new Collection;
        $this->setMessengerConfig();
        $this->setBotProvider();
    }

    /**
     * Set all providers we want to use in this messenger system.
     *
     * @param array $providers
     * @param bool $overwrite
     */
    public function registerProviders(array $providers, bool $overwrite = false): void
    {
        if ($overwrite) {
            $this->providers = new Collection;
            $this->setBotProvider();
        }

        foreach ($providers as $provider) {
            if (! is_subclass_of($provider, MessengerProvider::class)) {
                throw new InvalidArgumentException("The given provider { $provider } must implement our contract ".MessengerProvider::class);
            }

            $this->providers[$provider] = $this->makeProviderSettings($provider);
        }
    }

    /**
     * Check if provider is valid by seeing if alias exist.
     *
     * @param MessengerProvider|string|null $provider
     * @return bool
     */
    public function isValidMessengerProvider($provider = null): bool
    {
        return (bool) $this->findProviderAlias($provider);
    }

    /**
     * Get the defined alias of the provider class defined in config.
     *
     * @param MessengerProvider|string|null $provider
     * @return string|null
     */
    public function findProviderAlias($provider = null): ?string
    {
        $baseClass = $this->getClassNameString($provider);

        $search = $this->providers->search(function ($item, $class) use ($baseClass) {
            return $baseClass === $class || $item['morph_class'] === $baseClass;
        }) ?: null;

        return $search ? $this->providers->get($search)['alias'] : null;
    }

    /**
     * Get the provider class for the given alias.
     *
     * @param string $alias
     * @return string|null
     */
    public function findAliasProvider(string $alias): ?string
    {
        return $this->providers->search(function ($item) use ($alias) {
            return $item['alias'] === $alias || $item['morph_class'] === $alias;
        }) ?: null;
    }

    /**
     * Get all provider classes using morph alias overrides. If return full
     * class is true, we return full class namespaces ignoring morph maps.
     *
     * @param bool $returnFullClass
     * @return array
     */
    public function getAllProviders(bool $returnFullClass = false): array
    {
        $providers = $this->providers->reject(fn ($provider) => $provider['alias'] === 'bot');

        if ($returnFullClass) {
            return $providers->keys()->toArray();
        }

        return $providers
            ->map(fn ($provider) => $provider['morph_class'])
            ->flatten()
            ->toArray();
    }

    /**
     * Return the raw array of our registered providers.
     *
     * @return array
     */
    public function getRawProviders(): array
    {
        return $this->providers->toArray();
    }

    /**
     * Determine if the provider is searchable.
     *
     * @param MessengerProvider|string|null $provider
     * @return bool
     */
    public function isProviderSearchable($provider = null): bool
    {
        $baseClass = $this->getClassNameString($provider);

        return (bool) $this->providers->search(function ($item, $class) use ($baseClass) {
            return ($baseClass === $class || $item['morph_class'] === $baseClass)
                && $item['searchable'] === true;
        });
    }

    /**
     * Determine if the provider is friendable.
     *
     * @param MessengerProvider|string|null $provider
     * @return bool
     */
    public function isProviderFriendable($provider = null): bool
    {
        $baseClass = $this->getClassNameString($provider);

        return (bool) $this->providers->search(function ($item, $class) use ($baseClass) {
            return ($baseClass === $class || $item['morph_class'] === $baseClass)
                && $item['friendable'] === true;
        });
    }

    /**
     * @param string $alias
     * @return string|null
     */
    public function getProviderDefaultAvatarPath(string $alias): ?string
    {
        $search = $this->providers->search(function ($item) use ($alias) {
            return $item['alias'] === $alias;
        }) ?: null;

        return $search ? $this->providers->get($search)['default_avatar'] : null;
    }

    /**
     * Get all searchable provider classes using morph alias overrides. If return
     * full class is true, we return full class namespaces ignoring morph maps.
     *
     * @param bool $returnFullClass
     * @return array
     */
    public function getAllSearchableProviders(bool $returnFullClass = false): array
    {
        $providers = $this->providers->filter(fn ($provider) => $provider['searchable'] === true);

        if ($returnFullClass) {
            return $providers->keys()->toArray();
        }

        return $providers
            ->map(fn ($provider) => $provider['morph_class'])
            ->flatten()
            ->toArray();
    }

    /**
     * Get all friendable provider classes using morph alias overrides. If return
     * full class is true, we return full class namespaces ignoring morph maps.
     *
     * @param bool $returnFullClass
     * @return array
     */
    public function getAllFriendableProviders(bool $returnFullClass = false): array
    {
        $providers = $this->providers->filter(fn ($provider) => $provider['friendable'] === true);

        if ($returnFullClass) {
            return $providers->keys()->toArray();
        }

        return $providers
            ->map(fn ($provider) => $provider['morph_class'])
            ->flatten()
            ->toArray();
    }

    /**
     * Get all provider classes with devices using morph alias overrides. If return
     * full class is true, we return full class namespaces ignoring morph maps.
     *
     * @param bool $returnFullClass
     * @return array
     */
    public function getAllProvidersWithDevices(bool $returnFullClass = false): array
    {
        $providers = $this->providers->filter(fn ($provider) => $provider['devices'] === true);

        if ($returnFullClass) {
            return $providers->keys()->toArray();
        }

        return $providers
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
     * @param $provider
     * @return Model|string|null
     */
    private function getClassNameString($provider = null): ?string
    {
        return $provider instanceof Model
            ? $provider->getMorphClass()
            : $provider;
    }

    /**
     * Add our Bot model to the providers.
     */
    private function setBotProvider(): void
    {
        $this->providers[Bot::class] = [
            'alias' => 'bot',
            'morph_class' => 'bots',
            'searchable' => false,
            'friendable' => false,
            'devices' => false,
            'default_avatar' => $this->getDefaultBotAvatar(),
            'cant_message_first' => [],
            'cant_search' => [],
            'cant_friend' => [],
        ];
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
            'searchable' => (method_exists($provider, 'getProviderSearchableBuilder') && ($settings['searchable'] ?? true)),
            'friendable' => $settings['friendable'] ?? true,
            'devices' => $settings['devices'] ?? true,
            'default_avatar' => $settings['default_avatar'] ?? $this->getDefaultNotFoundImage(),
            'cant_message_first' => $settings['cant_message_first'] ?? [],
            'cant_search' => $settings['cant_search'] ?? [],
            'cant_friend' => $settings['cant_friend'] ?? [],
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
