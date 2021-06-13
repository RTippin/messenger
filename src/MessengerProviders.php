<?php

namespace RTippin\Messenger;

use Illuminate\Database\Eloquent\Model;
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
     * You may even choose to set many different providers in a row during a single cycle,
     * such as in a custom job or action.
     *
     * @param MessengerProvider|mixed|null $provider
     * @return $this
     * @throws InvalidProviderException
     */
    public function setProvider($provider = null): self
    {
        if (! $this->isValidMessengerProvider($provider)) {
            $this->unsetProvider()->throwProviderError();
        }

        $this->alias = $this->findProviderAlias($provider);
        $interactions = $this->providers->get($this->alias)['provider_interactions'];
        $this->provider = $provider;
        $this->providerHasFriends = $this->isProviderFriendable($provider);
        $this->providerHasDevices = $this->providers->get($this->alias)['devices'];
        $this->providerCanMessageFirst = $interactions['can_message'];
        $this->providerCanFriend = $interactions['can_friend'];
        $this->providerCanSearch = $interactions['can_search'];
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
        } elseif (! is_null($provider)
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
     * @return MessengerProvider|Model|null
     */
    public function getProvider(): ?MessengerProvider
    {
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
     * @param null $provider
     * @return bool
     */
    public function canMessageProviderFirst($provider = null): bool
    {
        $alias = $this->findProviderAlias($provider);

        return ! is_null($alias) && in_array($alias, $this->providerCanMessageFirst);
    }

    /**
     * Can the current Messenger Provider initiate a
     * friend request with given provider?
     *
     * @param null $provider
     * @return bool
     */
    public function canFriendProvider($provider = null): bool
    {
        $alias = $this->findProviderAlias($provider);

        return $alias && in_array($alias, $this->providerCanFriend);
    }

    /**
     * Can the current Messenger Provider search the given provider?
     *
     * @param null $provider
     * @return bool
     */
    public function canSearchProvider($provider = null): bool
    {
        $alias = $this->findProviderAlias($provider);

        return $alias && in_array($alias, $this->providerCanSearch);
    }

    /**
     * Get the ghost model.
     *
     * @return GhostUser
     */
    public function getGhostProvider(): GhostUser
    {
        if ($this->ghost) {
            return $this->ghost;
        }

        return $this->setGhostProvider();
    }

    /**
     * Get the ghost model.
     *
     * @return GhostUser
     */
    public function getGhostBot(): GhostUser
    {
        if ($this->ghostBot) {
            return $this->ghostBot;
        }

        return $this->setGhostBot();
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

        $this->ghostParticipant = new Participant([
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
        ]);

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
        return $this->providers->filter(function ($provider, $alias) {
            return $provider['searchable'] === true && in_array($alias, $this->providerCanSearch);
        })
            ->map(fn ($provider) => $provider['morph_class'])
            ->flatten()
            ->toArray();
    }

    /**
     * Get all base classes of valid providers the current
     * Messenger Provider can initiate a friend request with.
     *
     * @return array
     * @noinspection SpellCheckingInspection
     */
    public function getFriendableForCurrentProvider(): array
    {
        return $this->providers->filter(function ($provider, $alias) {
            return $provider['friendable'] === true && in_array($alias, $this->providerCanFriend);
        })
            ->map(fn ($provider) => $provider['morph_class'])
            ->flatten()
            ->toArray();
    }

    /**
     * @return GhostUser
     */
    private function setGhostProvider(): GhostUser
    {
        return $this->ghost = new GhostUser;
    }

    /**
     * @return GhostUser
     */
    private function setGhostBot(): GhostUser
    {
        return $this->ghostBot = (new GhostUser)->ghostBot();
    }

    /**
     * @throws InvalidProviderException
     */
    public function throwProviderError(): void
    {
        throw new InvalidProviderException;
    }
}
