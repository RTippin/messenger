<?php

namespace RTippin\Messenger;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Exceptions\InvalidMessengerProvider;
use RTippin\Messenger\Models\Messenger as MessengerModel;
use Illuminate\Contracts\Foundation\Application;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Participant;

/**
 * @property-read Application $app
 */
trait ProviderInterface
{
    /**
     * @var string
     */
    private string $alias = 'guest';

    /**
     * @var null|MessengerProvider
     */
    private ?MessengerProvider $provider = null;

    /**
     * @var MessengerModel|null
     */
    private ?MessengerModel $providerMessengerModel = null;

    /**
     * @var null|string|int
     */
    private $id = null;

    /**
     * @var null|string
     */
    private ?string $providerClass = null;

    /**
     * @var bool
     */
    private bool $providerHasFriends = false;

    /**
     * @var bool
     */
    private bool $providerHasMobileDevices = false;

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
     * such as in a custom job or action
     *
     * @param MessengerProvider|mixed|null $provider
     * @return $this
     * @throws InvalidMessengerProvider
     */
    public function setProvider($provider = null): self
    {
        if( ! $this->isValidMessengerProvider($provider))
        {
            $this->unsetProvider()->throwProviderError();
        }

        $this->alias = $this->findProviderAlias($provider);
        $interactions = $this->providers->get($this->alias)['provider_interactions'];
        $this->provider = $provider;
        $this->providerClass = get_class($provider);
        $this->providerHasFriends = $this->isProviderFriendable($provider);
        $this->providerHasMobileDevices = $this->providers->get($this->alias)['mobile_devices'];
        $this->providerCanMessageFirst = $interactions['can_message'];
        $this->providerCanFriend = $interactions['can_friend'];
        $this->providerCanSearch = $interactions['can_search'];
        $this->id = $provider->getKey();
        $this->providerIsSet = true;

        $this->app->instance(MessengerProvider::class, $provider);

        return $this;
    }

    /**
     * This will firstOrCreate a messenger model instance
     * for the given or currently set provider
     *
     * @param MessengerProvider|mixed|null $provider
     * @return MessengerModel|null
     */
    public function getProviderMessenger($provider = null): ?MessengerModel
    {
        if($this->isProviderSet()
            && (is_null($provider)
                || $this->getProvider()->is($provider)))
        {
            if(is_null($this->providerMessengerModel))
            {
                $this->providerMessengerModel = MessengerModel::firstOrCreate([
                    'owner_id' => $this->getProviderId(),
                    'owner_type' => $this->getProviderClass()
                ]);
            }

            return $this->providerMessengerModel;
        }
        else if( ! is_null($provider)
            && $this->isValidMessengerProvider($provider))
        {
            return MessengerModel::firstOrCreate([
                'owner_id' => $provider->getKey(),
                'owner_type' => get_class($provider)
            ]);
        }

        return null;
    }

    /**
     * Unset the active provider
     *
     * @return $this
     * @noinspection PhpUndefinedMethodInspection
     */
    public function unsetProvider(): self
    {
        $this->alias = 'guest';
        $this->provider = null;
        $this->providerClass = null;
        $this->providerHasFriends = false;
        $this->providerHasMobileDevices = false;
        $this->providerCanMessageFirst = [];
        $this->providerCanFriend = [];
        $this->providerCanSearch = [];
        $this->id = null;
        $this->providerIsSet = false;

        $this->app->forgetInstance(MessengerProvider::class);

        return $this;
    }

    /**
     * Get the current Messenger Provider
     *
     * @return MessengerProvider|null
     */
    public function getProvider(): ?MessengerProvider
    {
        return $this->provider;
    }

    /**
     * Get the current alias of the set Messenger Provider
     *
     * @return string
     */
    public function getProviderAlias(): string
    {
        return $this->alias;
    }

    /**
     * Get the current primary key of the set Messenger Provider
     *
     * @return int|string|null
     */
    public function getProviderId()
    {
        return $this->id;
    }

    /**
     * Get the current base class of set Messenger Provider
     *
     * @return string|null
     */
    public function getProviderClass(): ?string
    {
        return $this->providerClass;
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
    public function providerHasMobileDevices(): bool
    {
        return $this->providerHasMobileDevices;
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

        return $alias && in_array($alias, $this->providerCanMessageFirst);
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
     * Get the ghost model
     *
     * @return GhostUser
     */
    public function getGhostProvider(): GhostUser
    {
        if($this->ghost)
        {
            return $this->ghost;
        }

        return $this->setGhostProvider();
    }

    /**
     * Get a ghost participant model
     *
     * @param $threadId
     * @return Participant
     */
    public function getGhostParticipant($threadId): Participant
    {
        if($this->ghostParticipant
            && $this->ghostParticipant->thread_id === $threadId)
        {
            return $this->ghostParticipant;
        }

        $this->ghostParticipant = new Participant([
            'thread_id' => $threadId,
            'admin' => 0,
            'muted' => 1,
            'pending' => 1,
            'last_read' => null
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
     * Messenger Provider can search
     *
     * @return array
     */
    public function getSearchableForCurrentProvider(): array
    {
        return $this->providers->filter(fn($provider, $alias) =>
            $provider['searchable'] === true
            && in_array($alias, $this->providerCanSearch)
        )
        ->map(
            fn($provider) => $provider['model']
        )
        ->flatten()
        ->toArray();
    }

    /**
     * Get all base classes of valid providers the current
     * Messenger Provider can initiate a friend request with
     *
     * @return array
     * @noinspection SpellCheckingInspection
     */
    public function getFriendableForCurrentProvider(): array
    {
        return $this->providers->filter(fn($provider, $alias) =>
            $provider['friendable'] === true
            && in_array($alias, $this->providerCanFriend)
        )
        ->map(
            fn($provider) => $provider['model']
        )
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
     * @throws InvalidMessengerProvider
     */
    public function throwProviderError(): void
    {
        throw new InvalidMessengerProvider;
    }
}