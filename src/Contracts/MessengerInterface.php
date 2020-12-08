<?php

namespace RTippin\Messenger\Contracts;

use Illuminate\Support\Collection;
use RTippin\Messenger\Exceptions\InvalidMessengerProvider;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Messenger as MessengerModel;
use RTippin\Messenger\Models\Participant;

/**
 * Class MessengerService.
 */
interface MessengerInterface
{
    /**
     * Check if provider is valid by seeing if alias exist.
     *
     * @param mixed $provider
     * @return bool
     */
    public function isValidMessengerProvider($provider = null): bool;

    /**
     * Get the defined alias of the provider class defined in config.
     *
     * @param mixed $provider
     * @return string|null
     */
    public function findProviderAlias($provider = null): ?string;

    /**
     * Get the provider class of the alias defined in the config.
     *
     * @param string $alias
     * @return string|null
     */
    public function findAliasProvider(string $alias): ?string;

    /**
     * Determine if the provider is searchable.
     *
     * @param mixed $provider
     * @return bool
     */
    public function isProviderSearchable($provider = null): bool;

    /**
     * Determine if the provider is friendable.
     *
     * @param mixed $provider
     * @return bool
     * @noinspection SpellCheckingInspection
     */
    public function isProviderFriendable($provider = null): bool;

    /**
     * Is the messenger config cached?
     *
     * @return bool
     */
    public function isProvidersCached(): bool;

    /**
     * Set the configuration properties dynamically.
     *
     * @param array $params
     * @return $this
     */
    public function setConfig(array $params);

    /**
     * @return array
     */
    public function getConfig(): array;

    /**
     * @param string $alias
     * @return string|null
     */
    public function getProviderDefaultAvatarPath(string $alias): ?string;

    /**
     * @return string
     */
    public function getDefaultNotFoundImage(): string;

    /**
     * @param string|null $image
     * @return array|string
     */
    public function getDefaultThreadAvatars(string $image = null);

    /**
     * @return array
     */
    public function getAllSearchableProviders(): array;

    /**
     * @return array
     * @noinspection SpellCheckingInspection
     */
    public function getAllFriendableProviders(): array;

    /**
     * @return array
     */
    public function getAllMessengerProviders(): array;

    /**
     * @return array
     */
    public function getAllProvidersWithDevices(): array;

    /**
     * @return bool
     */
    public function isKnockKnockEnabled(): bool;

    /**
     * @param bool $knockKnock
     * @return $this
     */
    public function setKnockKnock(bool $knockKnock);

    /**
     * @return int
     */
    public function getKnockTimeout(): int;

    /**
     * @param int $knockTimeout
     * @return $this
     */
    public function setKnockTimeout(int $knockTimeout);

    /**
     * @return bool
     */
    public function isCallingEnabled(): bool;

    /**
     * @param bool $calling
     * @return $this
     */
    public function setCalling(bool $calling);

    /**
     * @param bool $invites
     * @return $this
     */
    public function setThreadInvites(bool $invites);

    /**
     * @return bool
     */
    public function isThreadInvitesEnabled(): bool;

    /**
     * @param int $maxInviteCount
     * @return $this
     */
    public function setThreadInvitesMaxCount(int $maxInviteCount);

    /**
     * @return int
     */
    public function getThreadMaxInvitesCount(): int;

    /**
     * @return bool
     */
    public function isMessageImageUploadEnabled(): bool;

    /**
     * @param bool $messageImageUpload
     * @return $this
     */
    public function setMessageImageUpload(bool $messageImageUpload);

    /**
     * @return bool
     */
    public function isThreadAvatarUploadEnabled(): bool;

    /**
     * @param bool $threadAvatarUpload
     * @return $this
     */
    public function setThreadAvatarUpload(bool $threadAvatarUpload);

    /**
     * @return bool
     */
    public function isMessageDocumentUploadEnabled(): bool;

    /**
     * @param bool $messageDocumentUpload
     * @return $this
     */
    public function setMessageDocumentUpload(bool $messageDocumentUpload);

    /**
     * @return bool
     */
    public function isMessageDocumentDownloadEnabled(): bool;

    /**
     * @param bool $messageDocumentDownload
     * @return $this
     */
    public function setMessageDocumentDownload(bool $messageDocumentDownload);

    /**
     * @return bool
     */
    public function isProviderAvatarUploadEnabled(): bool;

    /**
     * @param bool $providerAvatarUpload
     * @return $this
     */
    public function setProviderAvatarUpload(bool $providerAvatarUpload);

    /**
     * @return bool
     */
    public function isProviderAvatarRemovalEnabled(): bool;

    /**
     * @param bool $providerAvatarRemoval
     * @return $this
     */
    public function setProviderAvatarRemoval(bool $providerAvatarRemoval);

    /**
     * @return bool
     */
    public function isOnlineStatusEnabled(): bool;

    /**
     * @param bool $onlineStatus
     * @return $this
     */
    public function setOnlineStatus(bool $onlineStatus);

    /**
     * @return int
     */
    public function getOnlineCacheLifetime(): int;

    /**
     * @param int $onlineCacheLifetime
     * @return $this
     */
    public function setOnlineCacheLifetime(int $onlineCacheLifetime);

    /**
     * @return int
     */
    public function getThreadsIndexCount(): int;

    /**
     * @param int $threadsIndexCount
     * @return $this
     */
    public function setThreadsIndexCount(int $threadsIndexCount);

    /**
     * @return int
     */
    public function getSearchPageCount(): int;

    /**
     * @param int $searchPageCount
     * @return $this
     */
    public function setSearchPageCount(int $searchPageCount);

    /**
     * @return int
     */
    public function getThreadsPageCount(): int;

    /**
     * @param int $threadsPageCount
     * @return $this
     */
    public function setThreadsPageCount(int $threadsPageCount);

    /**
     * @return int
     */
    public function getParticipantsIndexCount(): int;

    /**
     * @param int $participantsIndexCount
     * @return $this
     */
    public function setParticipantsIndexCount(int $participantsIndexCount);

    /**
     * @return int
     */
    public function getParticipantsPageCount(): int;

    /**
     * @param int $participantsPageCount
     * @return $this
     */
    public function setParticipantsPageCount(int $participantsPageCount);

    /**
     * @return int
     */
    public function getMessagesIndexCount(): int;

    /**
     * @param int $messagesIndexCount
     * @return $this
     */
    public function setMessagesIndexCount(int $messagesIndexCount);

    /**
     * @return int
     */
    public function getMessagesPageCount(): int;

    /**
     * @param int $messagesPageCount
     * @return $this
     */
    public function setMessagesPageCount(int $messagesPageCount);

    /**
     * @return int
     */
    public function getCallsIndexCount(): int;

    /**
     * @param int $callsIndexCount
     * @return $this
     */
    public function setCallsIndexCount(int $callsIndexCount);

    /**
     * @return int
     */
    public function getCallsPageCount(): int;

    /**
     * @param int $callsPageCount
     * @return $this
     */
    public function setCallsPageCount(int $callsPageCount);

    /**
     * @param string|null $config
     * @return array|string
     */
    public function getAvatarStorage(string $config = null);

    /**
     * @param string|null $config
     * @return array|string
     */
    public function getThreadStorage(string $config = null);

    /**
     * @return string
     */
    public function getBroadcastDriver(): string;

    /**
     * @return string
     */
    public function getVideoDriver(): string;

    /**
     * @return string
     */
    public function getApiEndpoint(): string;

    /**
     * @return string
     */
    public function getWebEndpoint(): string;

    /**
     * @return string
     */
    public function getSocketEndpoint(): string;

    /**
     * @return string
     */
    public function getPushNotificationDriver(): string;

    /**
     * @return string
     */
    public function getSiteName(): string;

    /**
     * Put the given or loaded model into cache as online.
     *
     * @param null|MessengerProvider $provider
     * @return $this
     */
    public function setProviderToOnline($provider = null);

    /**
     * Remove the given or loaded model from online cache.
     *
     * @param null|MessengerProvider $provider
     * @return $this
     */
    public function setProviderToOffline($provider = null);

    /**
     * Put the given or loaded model into cache as away.
     *
     * @param null|MessengerProvider $provider
     * @return $this
     */
    public function setProviderToAway($provider = null);

    /**
     * Check if cache has online key for given or loaded model.
     *
     * @param null|MessengerProvider $provider
     * @return bool
     */
    public function isProviderOnline($provider = null): bool;

    /**
     * Check if cache has away key for given or loaded model.
     *
     * @param null|MessengerProvider $provider
     * @return bool
     */
    public function isProviderAway($provider = null): bool;

    /**
     * Get the status number representing online status of given or loaded model
     * 0 = offline, 1 = online, 2 = away.
     *
     * @param null|MessengerProvider $provider
     * @return int
     */
    public function getProviderOnlineStatus($provider = null): int;

    /**
     * Here we set a compatible provider model, which can be reused throughout our application!
     * It is recommended to set this in a middleware, after you have acquired your authenticated
     * user/provider. Most actions and methods require a provider being set before being used.
     * You may even choose to set many different providers in a row during a single cycle,
     * such as in a custom job or action.
     *
     * @param MessengerProvider|mixed|null $provider
     * @return $this
     * @throws InvalidMessengerProvider
     */
    public function setProvider($provider = null);

    /**
     * This will firstOrCreate a messenger model instance
     * for the given or currently set provider.
     *
     * @param MessengerProvider|mixed|null $provider
     * @return MessengerModel|null
     */
    public function getProviderMessenger($provider = null): ?MessengerModel;

    /**
     * Unset the active provider.
     *
     * @return $this
     */
    public function unsetProvider();

    /**
     * Get the current Messenger Provider.
     *
     * @return MessengerProvider|null
     */
    public function getProvider(): ?MessengerProvider;

    /**
     * Get the current alias of the set Messenger Provider.
     *
     * @return string
     */
    public function getProviderAlias(): string;

    /**
     * Get the current primary key of the set Messenger Provider.
     *
     * @return int|string|null
     */
    public function getProviderId();

    /**
     * Get the current base class of set Messenger Provider.
     *
     * @return string|null
     */
    public function getProviderClass(): ?string;

    /**
     * Does the current Messenger Provider have friends?
     *
     * @return bool
     */
    public function providerHasFriends(): bool;

    /**
     * Does the current Messenger Provider have devices?
     *
     * @return bool
     */
    public function providerHasMobileDevices(): bool;

    /**
     * Can the current Messenger Provider message given provider first?
     *
     * @param null $provider
     * @return bool
     */
    public function canMessageProviderFirst($provider = null): bool;

    /**
     * Can the current Messenger Provider initiate a
     * friend request with given provider?
     *
     * @param null $provider
     * @return bool
     */
    public function canFriendProvider($provider = null): bool;

    /**
     * Can the current Messenger Provider search the given provider?
     *
     * @param null $provider
     * @return bool
     */
    public function canSearchProvider($provider = null): bool;

    /**
     * Get the ghost model.
     *
     * @return GhostUser
     */
    public function getGhostProvider(): GhostUser;

    /**
     * Get a ghost participant model.
     *
     * @param $threadId
     * @return Participant
     */
    public function getGhostParticipant($threadId): Participant;

    /**
     * @return bool
     */
    public function isProviderSet(): bool;

    /**
     * Get all base classes of valid providers the current
     * Messenger Provider can search.
     *
     * @return array
     */
    public function getSearchableForCurrentProvider(): array;

    /**
     * Get all base classes of valid providers the current
     * Messenger Provider can initiate a friend request with.
     *
     * @return array
     * @noinspection SpellCheckingInspection
     */
    public function getFriendableForCurrentProvider(): array;

    /**
     * @throws InvalidMessengerProvider
     */
    public function throwProviderError(): void;

    /**
     * On boot, we set the services allowed provider classes.
     * We pass them through some validations.
     *
     * @param array $providers
     * @return Collection
     */
    public function formatValidProviders(array $providers): Collection;

    /**
     * @param string $abstract
     * @param string $contract
     * @return bool
     */
    public function passesReflectionInterface(string $abstract, string $contract): bool;
}
