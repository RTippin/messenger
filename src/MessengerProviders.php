<?php

namespace RTippin\Messenger;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Messenger as MessengerModel;
use RTippin\Messenger\Models\Participant;

trait MessengerProviders
{
    /**
     * @var string|null
     */
    private ?string $alias = null;

    /**
     * @var null|MessengerProvider
     */
    private ?MessengerProvider $provider = null;

    /**
     * @var MessengerModel|null
     */
    private ?MessengerModel $providerMessengerModel = null;

    /**
     * @var bool
     */
    private bool $providerHasFriends = false;

    /**
     * @var bool
     */
    private bool $providerHasDevices = false;

    /**
     * @var array
     */
    private array $providerCanMessageFirst = [];

    /**
     * @var array
     */
    private array $providerCanFriend = [];

    /**
     * @var array
     */
    private array $providerCanSearch = [];

    /**
     * @var null|GhostUser
     */
    private ?GhostUser $ghost = null;

    /**
     * @var null|GhostUser
     */
    private ?GhostUser $ghostBot = null;

    /**
     * @var null|Participant
     */
    private ?Participant $ghostParticipant = null;

    /**
     * @var bool
     */
    private bool $providerIsSet = false;

    /**
     * Here we set a compatible provider model, which can be reused throughout our application!
     * It is recommended to set this in a middleware, after you have acquired your authenticated
     * user/provider. Most actions and methods require a provider being set before being used.
     * You may even choose to set multiple different providers in a row during a single cycle,
     * such as in a custom job or action.
     *
     * @param MessengerProvider|mixed|null $provider
     * @return $this
     * @throws InvalidProviderException
     */
    public function setProvider($provider = null): self
    {
        if (! $this->isValidMessengerProvider($provider)) {
            $this->throwProviderError();
        }

        $this->provider = $provider;
        $providerSettings = $this->providers->get(get_class($provider));
        $this->alias = $providerSettings['alias'];
        $this->providerHasFriends = $providerSettings['friendable'];
        $this->providerHasDevices = $providerSettings['devices'];
        $this->providerCanMessageFirst = $this->getCanMessageFirstClasses($providerSettings['cant_message_first']);
        $this->providerCanFriend = $this->getCanFriendClasses($providerSettings['cant_friend']);
        $this->providerCanSearch = $this->getCanSearchClasses($providerSettings['cant_search']);
        $this->providerIsSet = true;

        app()->instance(MessengerProvider::class, $provider);

        return $this;
    }

    /**
     * This will firstOrCreate a messenger model instance
     * for the given or currently set provider.
     *
     * @param MessengerProvider|mixed|null $provider
     * @return MessengerModel|null
     */
    public function getProviderMessenger($provider = null): ?MessengerModel
    {
        if ($this->isProviderSet()
            && (is_null($provider)
                || $this->getProvider()->is($provider))) {
            if (is_null($this->providerMessengerModel)) {
                $this->providerMessengerModel = MessengerModel::firstOrCreate([
                    'owner_id' => $this->getProvider()->getKey(),
                    'owner_type' => $this->getProvider()->getMorphClass(),
                ]);
            }

            return $this->providerMessengerModel;
        }

        if (! is_null($provider)
            && $this->isValidMessengerProvider($provider)) {
            return MessengerModel::firstOrCreate([
                'owner_id' => $provider->getKey(),
                'owner_type' => $provider->getMorphClass(),
            ]);
        }

        return null;
    }

    /**
     * Unset the active provider.
     *
     * @return $this
     */
    public function unsetProvider(): self
    {
        $this->alias = null;
        $this->provider = null;
        $this->providerHasFriends = false;
        $this->providerHasDevices = false;
        $this->providerCanMessageFirst = [];
        $this->providerCanFriend = [];
        $this->providerCanSearch = [];
        $this->providerIsSet = false;

        app()->forgetInstance(MessengerProvider::class);

        return $this;
    }

    /**
     * Get the current Messenger Provider.
     *
     * @param bool $withoutRelations
     * @return MessengerProvider|Model|null
     */
    public function getProvider(bool $withoutRelations = false): ?MessengerProvider
    {
        if ($withoutRelations && $this->isProviderSet()) {
            return $this->provider->withoutRelations();
        }

        return $this->provider;
    }

    /**
     * Get the current alias of the set Messenger Provider.
     *
     * @return string|null
     */
    public function getProviderAlias(): ?string
    {
        return $this->alias;
    }

    /**
     * Does the current Messenger Provider have friends?
     *
     * @return bool
     */
    public function providerHasFriends(): bool
    {
        return $this->providerHasFriends;
    }

    /**
     * Does the current Messenger Provider have devices?
     *
     * @return bool
     */
    public function providerHasDevices(): bool
    {
        return $this->providerHasDevices;
    }

    /**
     * Can the current Messenger Provider message given provider first?
     *
     * @param MessengerProvider|Model|null $provider
     * @return bool
     */
    public function canMessageProviderFirst($provider = null): bool
    {
        return $provider
            && is_object($provider)
            && in_array(get_class($provider), $this->providerCanMessageFirst);
    }

    /**
     * Can the current Messenger Provider initiate a
     * friend request with given provider?
     *
     * @param MessengerProvider|Model|null $provider
     * @return bool
     */
    public function canFriendProvider($provider = null): bool
    {
        return $provider
            && is_object($provider)
            && in_array(get_class($provider), $this->providerCanFriend);
    }

    /**
     * Can the current Messenger Provider search the given provider?
     *
     * @param MessengerProvider|Model|null $provider
     * @return bool
     */
    public function canSearchProvider($provider = null): bool
    {
        return $provider
            && is_object($provider)
            && in_array(get_class($provider), $this->providerCanSearch);
    }

    /**
     * Get the ghost model.
     *
     * @return GhostUser
     */
    public function getGhostProvider(): GhostUser
    {
        if (is_null($this->ghost)) {
            $this->setGhostProvider();
        }

        return $this->ghost;
    }

    /**
     * Get the ghost model.
     *
     * @return GhostUser
     */
    public function getGhostBot(): GhostUser
    {
        if (is_null($this->ghostBot)) {
            $this->setGhostBot();
        }

        return $this->ghostBot;
    }

    /**
     * Get a ghost participant model.
     *
     * @param $threadId
     * @return Participant
     */
    public function getGhostParticipant($threadId): Participant
    {
        if ($this->ghostParticipant
            && $this->ghostParticipant->thread_id === $threadId) {
            return $this->ghostParticipant;
        }

        $this->ghostParticipant = (new Participant([
            'thread_id' => $threadId,
            'admin' => false,
            'muted' => true,
            'pending' => false,
            'last_read' => null,
            'start_calls' => false,
            'send_knocks' => false,
            'send_messages' => false,
            'add_participants' => false,
            'manage_invites' => false,
            'manage_bots' => false,
        ]))->setRelation('owner', $this->getGhostProvider());

        return $this->ghostParticipant;
    }

    /**
     * @return bool
     */
    public function isProviderSet(): bool
    {
        return $this->providerIsSet;
    }

    /**
     * Get all base classes of valid providers the current
     * Messenger Provider can search.
     *
     * @return array
     */
    public function getSearchableForCurrentProvider(): array
    {
        return $this->providers->filter(function ($settings, $class) {
            return $settings['searchable'] === true && in_array($class, $this->providerCanSearch);
        })
            ->map(fn ($provider) => $provider['morph_class'])
            ->flatten()
            ->toArray();
    }

    /**
     * Put the given or loaded model into cache as online.
     *
     * @param null|string|MessengerProvider $provider
     */
    public function setProviderToOnline($provider = null): void
    {
        $provider = $provider ?: $this->getProvider();

        if ($this->isOnlineStatusEnabled()
            && $this->isValidMessengerProvider($provider)
            && $this->getOnlineStatusSetting($provider) !== 0) {
            $this->getOnlineStatusSetting($provider) === 2
                ? $this->setToAway($provider)
                : $this->setToOnline($provider);
        }
    }

    /**
     * Remove the given or loaded model from online cache.
     *
     * @param null|string|MessengerProvider $provider
     */
    public function setProviderToOffline($provider = null): void
    {
        $provider = $provider ?: $this->getProvider();

        if ($this->isOnlineStatusEnabled()
            && $this->isValidMessengerProvider($provider)) {
            $this->setToOffline($provider);
        }
    }

    /**
     * Put the given or loaded model into cache as away.
     *
     * @param null|string|MessengerProvider $provider
     */
    public function setProviderToAway($provider = null): void
    {
        $provider = $provider ?: $this->getProvider();

        if ($this->isOnlineStatusEnabled()
            && $this->isValidMessengerProvider($provider)
            && $this->getOnlineStatusSetting($provider) !== 0) {
            $this->setToAway($provider);
        }
    }

    /**
     * Check if cache has online key for given or loaded model.
     *
     * @param null|string|MessengerProvider $provider
     * @return bool
     */
    public function isProviderOnline($provider = null): bool
    {
        $provider = $provider ?: $this->getProvider();

        if ($this->isOnlineStatusEnabled()
            && $this->isValidMessengerProvider($provider)) {
            return Cache::get("{$this->findProviderAlias($provider)}:online:{$provider->getKey()}") === 'online';
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
        $provider = $provider ?: $this->getProvider();

        if ($this->isOnlineStatusEnabled()
            && $this->isValidMessengerProvider($provider)) {
            return Cache::get("{$this->findProviderAlias($provider)}:online:{$provider->getKey()}") === 'away';
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
        $provider = $provider ?: $this->getProvider();

        if ($this->isOnlineStatusEnabled()
            && $this->isValidMessengerProvider($provider)
            && $cache = Cache::get("{$this->findProviderAlias($provider)}:online:{$provider->getKey()}")) {
            return $cache === 'online' ? 1 : 2;
        }

        return 0;
    }

    /**
     * @param array $limits
     * @return array
     */
    private function getCanMessageFirstClasses(array $limits): array
    {
        $validProviders = $this->getAllProviders(true);

        if (count($limits) > 0) {
            return $this->filterCanList($validProviders, $limits);
        }

        return $validProviders;
    }

    /**
     * @param array $limits
     * @return array
     */
    private function getCanFriendClasses(array $limits): array
    {
        $validProviders = $this->getAllFriendableProviders(true);

        if (count($limits) > 0) {
            return $this->filterCanList($validProviders, $limits);
        }

        return $validProviders;
    }

    /**
     * @param array $limits
     * @return array
     */
    private function getCanSearchClasses(array $limits): array
    {
        $validProviders = $this->getAllSearchableProviders(true);

        if (count($limits) > 0) {
            return $this->filterCanList($validProviders, $limits);
        }

        return $validProviders;
    }

    /**
     * @param array $providers
     * @param array $limits
     * @return array
     */
    private function filterCanList(array $providers, array $limits): array
    {
        return (new Collection($providers))
            ->reject(fn ($provider) => in_array($provider, $limits))
            ->values()
            ->toArray();
    }

    /**
     * @throws InvalidProviderException
     */
    public function throwProviderError(): void
    {
        throw new InvalidProviderException;
    }

    /**
     * Set the ghost user.
     */
    private function setGhostProvider(): void
    {
        $this->ghost = new GhostUser;
    }

    /**
     * Set the ghost bot.
     */
    private function setGhostBot(): void
    {
        $this->ghostBot = (new GhostUser)->ghostBot();
    }

    /**
     * @param MessengerProvider $provider
     * @return int
     */
    private function getOnlineStatusSetting(MessengerProvider $provider): int
    {
        return $this->getProviderMessenger($provider)->online_status;
    }

    /**
     * @param MessengerProvider $provider
     */
    private function setToOnline(MessengerProvider $provider): void
    {
        Cache::put(
            "{$this->findProviderAlias($provider)}:online:{$provider->getKey()}",
            'online',
            now()->addMinutes($this->getOnlineCacheLifetime())
        );
    }

    /**
     * @param MessengerProvider $provider
     */
    private function setToAway(MessengerProvider $provider): void
    {
        Cache::put(
            "{$this->findProviderAlias($provider)}:online:{$provider->getKey()}",
            'away',
            now()->addMinutes($this->getOnlineCacheLifetime())
        );
    }

    /**
     * @param MessengerProvider $provider
     */
    private function setToOffline(MessengerProvider $provider): void
    {
        Cache::forget("{$this->findProviderAlias($provider)}:online:{$provider->getKey()}");
    }
}
