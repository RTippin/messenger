<?php

namespace RTippin\Messenger\Contracts;

use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Messenger as MessengerModel;
use RTippin\Messenger\Models\Participant;

/**
 * Class MessengerService.
 */
interface MessengerInterface
{
    /**
     * Return the current instance of messenger.
     * @return $this
     */
    public function instance();

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
     * @param bool $pushNotifications
     * @return $this
     */
    public function setPushNotifications(bool $pushNotifications);

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
    public function isMessageEditsEnabled(): bool;

    /**
     * @param bool $messageEdits
     * @return $this
     */
    public function setMessageEdits(bool $messageEdits);

    /**
     * @return bool
     */
    public function isMessageEditsViewEnabled(): bool;

    /**
     * @param bool $messageEditsView
     * @return $this
     */
    public function setMessageEditsView(bool $messageEditsView);

    /**
     * @param int $minutesDisabled
     * @return $this
     */
    public function disableCallsTemporarily(int $minutesDisabled);

    /**
     * @return bool
     */
    public function isCallingTemporarilyDisabled(): bool;

    /**
     * @return $this
     */
    public function removeTemporaryCallShutdown();

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
     * @return int
     */
    public function getMessageImageSizeLimit(): int;

    /**
     * @param int $messageImageSizeLimit
     * @return $this
     */
    public function setMessageImageSizeLimit(int $messageImageSizeLimit);

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
     * @return int
     */
    public function getThreadAvatarSizeLimit(): int;

    /**
     * @param int $threadAvatarSizeLimit
     * @return $this
     */
    public function setThreadAvatarSizeLimit(int $threadAvatarSizeLimit);

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
     * @return int
     */
    public function getMessageDocumentSizeLimit(): int;

    /**
     * @param int $messageDocumentSizeLimit
     * @return $this
     */
    public function setMessageDocumentSizeLimit(int $messageDocumentSizeLimit);

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
     * @return int
     */
    public function getProviderAvatarSizeLimit(): int;

    /**
     * @param int $providerAvatarSizeLimit
     * @return $this
     */
    public function setProviderAvatarSizeLimit(int $providerAvatarSizeLimit);

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
    public function getApiRateLimit(): int;

    /**
     * @param int $apiRateLimit
     * @return $this
     */
    public function setApiRateLimit(int $apiRateLimit);

    /**
     * @return int
     */
    public function getSearchRateLimit(): int;

    /**
     * @param int $searchRateLimit
     * @return $this
     */
    public function setSearchRateLimit(int $searchRateLimit);

    /**
     * @return int
     */
    public function getMessageRateLimit(): int;

    /**
     * @param int $messageRateLimit
     * @return $this
     */
    public function setMessageRateLimit(int $messageRateLimit);

    /**
     * @return int
     */
    public function getAttachmentRateLimit(): int;

    /**
     * @param int $attachmentRateLimit
     * @return $this
     */
    public function setAttachmentRateLimit(int $attachmentRateLimit);

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
     * @param string $driverAlias
     * @return $this
     */
    public function setBroadcastDriver(string $driverAlias);

    /**
     * @return string
     */
    public function getVideoDriver(): string;

    /**
     * @param string $driverAlias
     * @return $this
     */
    public function setVideoDriver(string $driverAlias);

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
     * @return bool
     */
    public function isPushNotificationsEnabled(): bool;

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
     * @throws InvalidProviderException
     */
    public function setProvider($provider = null);

    /**
     * Set providers if provided, from cache if exist, otherwise set from config.
     *
     * @param array $providers
     */
    public function setMessengerProviders(array $providers = []): void;

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
     * @return string|null
     */
    public function getProviderAlias(): ?string;

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
    public function providerHasDevices(): bool;

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
     * @throws InvalidProviderException
     */
    public function throwProviderError(): void;

    /**
     * Reset all values back to default.
     */
    public function reset(): void;

    /**
     * @return array
     */
    public function getMessengerProviders(): array;
}
