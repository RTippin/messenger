<?php

namespace RTippin\Messenger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\DataTransferObjects\MessengerProviderDTO;
use RTippin\Messenger\Exceptions\InvalidProviderException;
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
     * @var Collection|MessengerProviderDTO[]
     */
    private Collection $providers;

    /**
     * @var bool
     */
    private static bool $useUuid = false;

    /**
     * @var bool
     */
    private static bool $useAbsoluteRoutes = false;

    /**
     * Messenger constructor.
     */
    public function __construct()
    {
        $this->providers = Collection::make();
        $this->setMessengerConfig();
        $this->setBotProvider();
    }

    /**
     * This determines if we use UUID or BigInt for provider related models and migrations.
     *
     * @param  bool|null  $shouldUseUuids
     * @return bool
     */
    public static function shouldUseUuids(?bool $shouldUseUuids = null): bool
    {
        if (is_null($shouldUseUuids)) {
            return self::$useUuid;
        }

        return self::$useUuid = $shouldUseUuids;
    }

    /**
     * @param  bool|null  $shouldUseAbsoluteRoutes
     * @return bool
     */
    public static function shouldUseAbsoluteRoutes(bool $shouldUseAbsoluteRoutes = null): bool
    {
        if (is_null($shouldUseAbsoluteRoutes)) {
            return self::$useAbsoluteRoutes;
        }

        return self::$useAbsoluteRoutes = $shouldUseAbsoluteRoutes;
    }

    /**
     * Set all providers we want to use in this messenger system.
     *
     * @param  array  $providers
     * @param  bool  $overwrite
     * @return void
     */
    public function registerProviders(array $providers, bool $overwrite = false): void
    {
        if ($overwrite) {
            $this->providers = Collection::make();
            $this->setBotProvider();
        }

        foreach ($providers as $provider) {
            if (! is_subclass_of($provider, MessengerProvider::class)) {
                throw new InvalidArgumentException("The given provider { $provider } must implement the interface ".MessengerProvider::class);
            }

            $this->providers[$provider] = new MessengerProviderDTO($provider);
        }
    }

    /**
     * Check if provider is valid by seeing if alias exist.
     *
     * @param  MessengerProvider|string|null  $provider
     * @return bool
     */
    public function isValidMessengerProvider($provider = null): bool
    {
        return (bool) $this->findProviderAlias($provider);
    }

    /**
     * Get the defined alias of the provider class defined in config.
     *
     * @param  MessengerProvider|string|null  $provider
     * @return string|null
     */
    public function findProviderAlias($provider = null): ?string
    {
        $baseClass = $this->getClassNameString($provider);

        $search = $this->providers->search(
            fn (MessengerProviderDTO $item) => $this->isBaseOrMorphClass($item, $baseClass)
        ) ?: null;

        return optional($this->getProviderDTO($search))->alias ?: null;
    }

    /**
     * Get the provider class for the given alias.
     *
     * @param  string  $alias
     * @return string|null
     */
    public function findAliasProvider(string $alias): ?string
    {
        return $this->providers->search(
            fn (MessengerProviderDTO $provider) => in_array($alias, [$provider->alias, $provider->morphClass])
        ) ?: null;
    }

    /**
     * Get all provider classes using morph alias overrides. If return full
     * class is true, we return full class namespaces ignoring morph maps.
     *
     * @param  bool  $returnFullClass
     * @return array
     */
    public function getAllProviders(bool $returnFullClass = false): array
    {
        $providers = $this->providers->reject(
            fn (MessengerProviderDTO $provider) => $provider->alias === 'bot'
        );

        if ($returnFullClass) {
            return $providers->keys()->toArray();
        }

        return $providers
            ->map(fn (MessengerProviderDTO $provider) => $provider->morphClass)
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
     * @param  MessengerProvider|string|null  $provider
     * @return bool
     */
    public function isProviderSearchable($provider = null): bool
    {
        $baseClass = $this->getClassNameString($provider);

        return (bool) $this->providers->search(
            fn (MessengerProviderDTO $item) => $this->isBaseOrMorphClass($item, $baseClass)
                && $item->searchable
        );
    }

    /**
     * Determine if the provider is friendable.
     *
     * @param  MessengerProvider|string|null  $provider
     * @return bool
     */
    public function isProviderFriendable($provider = null): bool
    {
        $baseClass = $this->getClassNameString($provider);

        return (bool) $this->providers->search(
            fn (MessengerProviderDTO $item) => $this->isBaseOrMorphClass($item, $baseClass)
                && $item->friendable
        );
    }

    /**
     * @param  string  $alias
     * @return string|null
     */
    public function getProviderDefaultAvatarPath(string $alias): ?string
    {
        $search = $this->providers->search(
            fn (MessengerProviderDTO $provider) => $provider->alias === $alias
        ) ?: null;

        return optional($this->getProviderDTO($search))->defaultAvatarPath ?: null;
    }

    /**
     * Get all searchable provider classes using morph alias overrides. If return
     * full class is true, we return full class namespaces ignoring morph maps.
     *
     * @param  bool  $returnFullClass
     * @return array
     */
    public function getAllSearchableProviders(bool $returnFullClass = false): array
    {
        $providers = $this->providers->filter(
            fn (MessengerProviderDTO $provider) => $provider->searchable
        );

        if ($returnFullClass) {
            return $providers->keys()->toArray();
        }

        return $providers
            ->map(fn (MessengerProviderDTO $provider) => $provider->morphClass)
            ->flatten()
            ->toArray();
    }

    /**
     * Get all friendable provider classes using morph alias overrides. If return
     * full class is true, we return full class namespaces ignoring morph maps.
     *
     * @param  bool  $returnFullClass
     * @return array
     */
    public function getAllFriendableProviders(bool $returnFullClass = false): array
    {
        $providers = $this->providers->filter(
            fn (MessengerProviderDTO $provider) => $provider->friendable
        );

        if ($returnFullClass) {
            return $providers->keys()->toArray();
        }

        return $providers
            ->map(fn (MessengerProviderDTO $provider) => $provider->morphClass)
            ->flatten()
            ->toArray();
    }

    /**
     * Get all provider classes with devices using morph alias overrides. If return
     * full class is true, we return full class namespaces ignoring morph maps.
     *
     * @param  bool  $returnFullClass
     * @return array
     */
    public function getAllProvidersWithDevices(bool $returnFullClass = false): array
    {
        $providers = $this->providers->filter(
            fn (MessengerProviderDTO $provider) => $provider->hasDevices
        );

        if ($returnFullClass) {
            return $providers->keys()->toArray();
        }

        return $providers
            ->map(fn (MessengerProviderDTO $provider) => $provider->morphClass)
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
     * Flush any active and scoped provider set, reset our configs to defaults,
     * and flush the FriendDriver instance from the container.
     *
     * @return void
     *
     * @throws InvalidProviderException
     */
    public function flush(): void
    {
        $this->unsetProvider(false, true);
        $this->setMessengerConfig();
        $this->flushFriendDriverInstance();
        self::shouldUseAbsoluteRoutes(config('messenger.use_absolute_routes'));
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
     *
     * @return void
     */
    private function setBotProvider(): void
    {
        $this->providers[Bot::class] = new MessengerProviderDTO(Bot::class);
    }

    /**
     * @param  string|null  $provider
     * @return MessengerProviderDTO|null
     */
    private function getProviderDTO(?string $provider): ?MessengerProviderDTO
    {
        return $this->providers->get($provider);
    }

    /**
     * @param  MessengerProviderDTO  $provider
     * @param  string|null  $baseClass
     * @return bool
     */
    private function isBaseOrMorphClass(MessengerProviderDTO $provider, ?string $baseClass): bool
    {
        return in_array($baseClass, [
            $provider->class,
            $provider->morphClass,
        ]);
    }

    /**
     * Flush any active instance of our FriendDriver from the container.
     *
     * @return void
     */
    private function flushFriendDriverInstance(): void
    {
        app()->instance(FriendDriver::class, null);
    }
}
