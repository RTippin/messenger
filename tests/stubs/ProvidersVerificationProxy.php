<?php

namespace RTippin\Messenger\Tests\stubs;

use Illuminate\Support\Collection;
use RTippin\Messenger\ProvidersVerification;

class ProvidersVerificationProxy extends ProvidersVerification
{
    public function collectAndFilterProviders(array $providers): Collection
    {
        return parent::collectAndFilterProviders($providers);
    }

    public function sanitizeAliasKey(Collection $providers): Collection
    {
        return parent::sanitizeAliasKey($providers);
    }

    public function verifyProviderConfigs(Collection $providers): Collection
    {
        return parent::verifyProviderConfigs($providers);
    }

    public function verifyProviderInteractions(Collection $providers): Collection
    {
        return parent::verifyProviderInteractions($providers);
    }

    public function passesProviderValidation($provider): bool
    {
        return parent::passesProviderValidation($provider);
    }

    public function passesFriendable(array $provider): bool
    {
        return parent::passesFriendable($provider);
    }

    public function passesSearchable(array $provider): bool
    {
        return parent::passesSearchable($provider);
    }

    public function passesHasDevices(array $provider): bool
    {
        return parent::passesHasDevices($provider);
    }

    public function validatesCanMessage(string $alias,
                                         array $provider,
                                         Collection $providers): array
    {
        return parent::validatesCanMessage($alias, $provider, $providers);
    }

    public function validatesCanSearch(string $alias,
                                        array $provider,
                                        Collection $providers): array
    {
        return parent::validatesCanSearch($alias, $provider, $providers);
    }

    public function validatesCanFriend(string $alias,
                                        array $provider,
                                        Collection $providers): array
    {
        return parent::validatesCanFriend($alias, $provider, $providers);
    }

    public function explodeAndCollect(string $items): Collection
    {
        return parent::explodeAndCollect($items);
    }

    public function sanitizeAlias(string $alias): string
    {
        return parent::sanitizeAlias($alias);
    }
}
