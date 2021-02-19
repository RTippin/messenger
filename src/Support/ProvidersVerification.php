<?php

namespace RTippin\Messenger\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\Traits\ChecksReflection;

class ProvidersVerification
{
    use ChecksReflection;

    /**
     * On boot, we set the services allowed provider classes.
     * We pass them through some validations.
     *
     * @param array $providers
     * @return Collection
     */
    public function formatValidProviders(array $providers): Collection
    {
        return $this->verifyProviderInteractions(
            $this->verifyProviderConfigs(
                $this->sanitizeAliasKey(
                    $this->collectAndFilterProviders(
                        $providers
                    )
                )
            )
        );
    }

    /**
     * Collect and return valid classes that implement MessengerProvider.
     *
     * @param array $providers
     * @return Collection
     */
    protected function collectAndFilterProviders(array $providers): Collection
    {
        return collect($providers)->filter(fn ($provider, $alias) => $this->passesProviderValidation($provider, $alias));
    }

    /**
     * Force all provider aliases to be lowercase and remove underscores.
     *
     * @param Collection $providers
     * @return Collection
     */
    protected function sanitizeAliasKey(Collection $providers): Collection
    {
        return $providers->mapWithKeys(fn ($provider, $alias) => [
            $this->sanitizeAlias($alias) => $provider,
        ]);
    }

    /**
     * Pass valid providers through config checker for traits.
     *
     * @param Collection $providers
     * @return Collection
     * @noinspection SpellCheckingInspection
     */
    protected function verifyProviderConfigs(Collection $providers): Collection
    {
        return $providers->map(fn ($provider) => [
            'model' => $provider['model'],
            'searchable' => $this->passesSearchable($provider),
            'friendable' => $this->passesFriendable($provider),
            'devices' => $this->passesHasDevices($provider),
            'default_avatar' => $provider['default_avatar'],
            'provider_interactions' => $provider['provider_interactions'],
        ]);
    }

    /**
     * Verify all provider interactions listed.
     *
     * @param Collection $providers
     * @return Collection
     */
    protected function verifyProviderInteractions(Collection $providers): Collection
    {
        return $providers->map(fn ($provider, $alias) => array_merge($provider, [
            'provider_interactions' => [
                'can_message' => $this->validatesCanMessage($alias, $provider, $providers),
                'can_search' => $this->validatesCanSearch($alias, $provider, $providers),
                'can_friend' => $this->validatesCanFriend($alias, $provider, $providers),
            ],
        ]));
    }

    /**
     * @param mixed $provider
     * @param int|string $alias
     * @return bool
     * @noinspection SpellCheckingInspection
     */
    protected function passesProviderValidation($provider, $alias): bool
    {
        return is_array($provider)
            && is_string($alias)
            && array_key_exists('model', $provider)
            && class_exists($provider['model'])
            && array_key_exists('searchable', $provider)
            && array_key_exists('friendable', $provider)
            && array_key_exists('devices', $provider)
            && array_key_exists('provider_interactions', $provider)
            && is_array($provider['provider_interactions'])
            && array_key_exists('default_avatar', $provider)
            && array_key_exists('can_message', $provider['provider_interactions'])
            && array_key_exists('can_search', $provider['provider_interactions'])
            && array_key_exists('can_friend', $provider['provider_interactions'])
            && $this->checkImplementsInterface($provider['model'], MessengerProvider::class);
    }

    /**
     * @param array $provider
     * @return bool
     * @noinspection SpellCheckingInspection
     */
    protected function passesFriendable(array $provider): bool
    {
        return $provider['friendable'] === true;
    }

    /**
     * @param array $provider
     * @return bool
     */
    protected function passesSearchable(array $provider): bool
    {
        return $provider['searchable'] === true
            && $this->checkImplementsInterface($provider['model'], Searchable::class);
    }

    /**
     * @param array $provider
     * @return bool
     */
    protected function passesHasDevices(array $provider): bool
    {
        return $provider['devices'] === true;
    }

    /**
     * @param string $alias
     * @param array $provider
     * @param Collection $providers
     * @return array|string[]
     */
    protected function validatesCanMessage(string $alias,
                                         array $provider,
                                         Collection $providers): array
    {
        $canMessage = $provider['provider_interactions']['can_message'];

        if ($canMessage !== true) {
            if (is_null($canMessage)
                || empty($canMessage)
                || $canMessage === false) {
                return [$alias];
            }

            return $this->explodeAndCollect($canMessage)
                ->reject(fn ($value) => $value === $alias || ! $providers->has($value))
                ->push($alias)
                ->values()
                ->toArray();
        }

        return $providers->keys()->toArray();
    }

    /**
     * @param string $alias
     * @param array $provider
     * @param Collection $providers
     * @return array|string[]
     */
    protected function validatesCanSearch(string $alias,
                                        array $provider,
                                        Collection $providers): array
    {
        $canSearch = $provider['provider_interactions']['can_search'];

        if ($canSearch !== true) {
            if (is_null($canSearch)
                || empty($canSearch)
                || $canSearch === false) {
                return $provider['searchable'] === true
                    ? [$alias]
                    : [];
            }

            $filtered = $this->explodeAndCollect($canSearch)
                ->reject(fn ($value) => $value === $alias
                    || ! $providers->has($value)
                    || $providers->get($value)['searchable'] === false);

            return $provider['searchable'] === true
                ? $filtered->push($alias)->values()->toArray()
                : $filtered->values()->toArray();
        }

        return $providers
            ->filter(fn ($provider) => $provider['searchable'] === true)
            ->keys()
            ->toArray();
    }

    /**
     * @param string $alias
     * @param array $provider
     * @param Collection $providers
     * @return array|string[]
     * @noinspection SpellCheckingInspection
     */
    protected function validatesCanFriend(string $alias,
                                        array $provider,
                                        Collection $providers): array
    {
        if ($provider['friendable'] === false) {
            return [];
        }

        $canFriend = $provider['provider_interactions']['can_friend'];

        if ($canFriend !== true) {
            if (is_null($canFriend)
                || empty($canFriend)
                || $canFriend === false) {
                return [$alias];
            }

            $filtered = $this->explodeAndCollect($canFriend)
                ->reject(fn ($value) => $value === $alias
                    || ! $providers->has($value)
                    || $providers->get($value)['friendable'] === false);

            return $provider['friendable'] === true
                ? $filtered->push($alias)->values()->toArray()
                : $filtered->values()->toArray();
        }

        return $providers
            ->filter(fn ($provider) => $provider['friendable'] === true)
            ->keys()
            ->toArray();
    }

    /**
     * @param string $items
     * @return Collection
     */
    protected function explodeAndCollect(string $items): Collection
    {
        return collect(
            explode(
                '|',
                $this->sanitizeAlias($items)
            )
        );
    }

    /**
     * @param string $alias
     * @return string
     */
    protected function sanitizeAlias(string $alias): string
    {
        return Str::lower(
            str_replace(
                [' ', '_', '-', '.', ','],
                '',
                $alias
            )
        );
    }
}
