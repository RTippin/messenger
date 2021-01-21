<?php

namespace RTippin\Messenger\Facades;

use Illuminate\Support\Facades\Facade;
use RTippin\Messenger\Contracts\MessengerInterface;

/**
 * @method static setProvider($provider = null)
 * @method static isValidMessengerProvider($provider = null)
 * @method static findProviderAlias($provider = null)
 * @method static findAliasProvider(string $alias)
 * @method static isProviderSearchable($provider = null)
 * @method static isProviderFriendable($provider = null)
 * @method static isProvidersCached()
 * @method static setConfig(array $params)
 * @method static getConfig()
 * @method static getAllSearchableProviders()
 * @method static getAllFriendableProviders()
 * @method static getAllMessengerProviders()
 * @method static getAllProvidersWithDevices()
 * @method static isKnockKnockEnabled()
 * @method static setKnockKnock(bool $knockKnock)
 * @method static getKnockTimeout()
 * @method static setKnockTimeout(int $knockTimeout)
 * @method static isCallingEnabled()
 * @method static setCalling(bool $calling)
 * @method static setThreadInvites(bool $invites)
 * @method static isThreadInvitesEnabled()
 * @method static setThreadInvitesMaxCount(int $maxInviteCount)
 * @method static getThreadMaxInvitesCount()
 * @method static isMessageImageUploadEnabled()
 * @method static setMessageImageUpload(bool $messageImageUpload)
 * @method static isThreadAvatarUploadEnabled()
 * @method static setThreadAvatarUpload(bool $threadAvatarUpload)
 * @method static isMessageDocumentUploadEnabled()
 * @method static setMessageDocumentUpload(bool $messageDocumentUpload)
 * @method static isMessageDocumentDownloadEnabled()
 * @method static setMessageDocumentDownload(bool $messageDocumentDownload)
 * @method static isProviderAvatarUploadEnabled()
 * @method static setProviderAvatarUpload(bool $providerAvatarUpload)
 * @method static isProviderAvatarRemovalEnabled()
 * @method static setProviderAvatarRemoval(bool $providerAvatarRemoval)
 * @method static isOnlineStatusEnabled()
 * @method static setOnlineStatus(bool $onlineStatus)
 * @method static getOnlineCacheLifetime()
 * @method static setOnlineCacheLifetime(int $onlineCacheLifetime)
 * @method static getThreadsIndexCount()
 * @method static setThreadsIndexCount(int $threadsIndexCount)
 * @method static getSearchPageCount()
 * @method static setSearchPageCount(int $searchPageCount)
 * @method static getThreadsPageCount()
 * @method static setThreadsPageCount(int $threadsPageCount)
 * @method static getParticipantsIndexCount()
 * @method static setParticipantsIndexCount(int $participantsIndexCount)
 * @method static getParticipantsPageCount()
 * @method static setParticipantsPageCount(int $participantsPageCount)
 * @method static getMessagesIndexCount()
 * @method static setMessagesIndexCount(int $messagesIndexCount)
 * @method static getMessagesPageCount()
 * @method static setMessagesPageCount(int $messagesPageCount)
 * @method static getCallsIndexCount()
 * @method static setCallsIndexCount(int $callsIndexCount)
 * @method static getCallsPageCount()
 * @method static setCallsPageCount(int $callsPageCount)
 * @method static getAvatarStorage(string $config = null)
 * @method static getThreadStorage(string $config = null)
 * @method static getBroadcastDriver()
 * @method static getVideoDriver()
 * @method static isPushNotificationsEnabled()
 * @method static setPushNotifications(bool $pushNotifications)
 * @method static setProviderToOnline($provider = null)
 * @method static setProviderToOffline($provider = null)
 * @method static setProviderToAway($provider = null)
 * @method static isProviderOnline($provider = null)
 * @method static isProviderAway($provider = null)
 * @method static getProviderOnlineStatus($provider = null)
 * @method static getProviderMessenger($provider = null)
 * @method static unsetProvider()
 * @method static getProvider()
 * @method static getProviderAlias()
 * @method static getProviderId()
 * @method static getProviderClass()
 * @method static providerHasFriends()
 * @method static providerHasDevices()
 * @method static canMessageProviderFirst($provider = null)
 * @method static canFriendProvider($provider = null)
 * @method static canSearchProvider($provider = null)
 * @method static getGhostProvider()
 * @method static getGhostParticipant($threadId)
 * @method static isProviderSet()
 * @method static getSearchableForCurrentProvider()
 * @method static getFriendableForCurrentProvider()
 * @method static formatValidProviders(array $providers)
 * @method static passesReflectionInterface(string $abstract, string $contract)
 * @method static getApiEndpoint()
 * @method static getWebEndpoint()
 * @method static getSocketEndpoint()
 * @method static getSiteName()
 * @method static getProviderDefaultAvatarPath(string $alias)
 * @method static getDefaultNotFoundImage()
 * @method static getDefaultThreadAvatars(string $image = null)
 * @method static reset()
 *
 * @mixin \RTippin\Messenger\Messenger
 * @see MessengerInterface
 */
class Messenger extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'messenger';
    }
}
