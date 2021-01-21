<?php

namespace RTippin\Messenger;

use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;

/**
 * @property-read Collection $providers
 * @property-read Application $app
 * @property-read Repository $configRepo
 * @property-read Filesystem $filesystem
 */
trait ConfigInterface
{
    /**
     * @var string
     */
    private string $siteName;

    /**
     * @var Collection
     */
    private Collection $providers;

    /**
     * @var bool
     */
    private bool $isProvidersCached = false;

    /**
     * @var string
     */
    private string $apiEndpoint;

    /**
     * @var string
     */
    private string $webEndpoint;

    /**
     * @var string
     */
    private string $socketEndpoint;

    /**
     * @var string
     */
    private string $broadcastDriver;

    /**
     * @var string
     */
    private string $videoDriver;

    /**
     * @var bool
     */
    private bool $pushNotifications;

    /**
     * @var array
     */
    private array $avatarStorage;

    /**
     * @var array
     */
    private array $threadStorage;

    /**
     * @var string
     */
    private string $defaultNotFoundImage;

    /**
     * @var array
     */
    private array $defaultThreadAvatars;

    /**
     * @var bool
     */
    private bool $knockKnock;

    /**
     * @var int
     */
    private int $knockTimeout;

    /**
     * @var bool
     */
    private bool $onlineStatus;

    /**
     * @var int
     */
    private int $onlineCacheLifetime;

    /**
     * @var bool
     */
    private bool $calling;

    /**
     * @var bool
     */
    private bool $threadInvites;

    /**
     * @var int
     */
    private int $threadInvitesMax;

    /**
     * @var bool
     */
    private bool $providerAvatarUpload;

    /**
     * @var bool
     */
    private bool $providerAvatarRemoval;

    /**
     * @var bool
     */
    private bool $messageDocumentUpload;

    /**
     * @var bool
     */
    private bool $messageDocumentDownload;

    /**
     * @var bool
     */
    private bool $messageImageUpload;

    /**
     * @var bool
     */
    private bool $threadAvatarUpload;

    /**
     * @var int
     */
    private int $searchPageCount;

    /**
     * @var int
     */
    private int $threadsIndexCount;

    /**
     * @var int
     */
    private int $threadsPageCount;

    /**
     * @var int
     */
    private int $participantsIndexCount;

    /**
     * @var int
     */
    private int $participantsPageCount;

    /**
     * @var int
     */
    private int $messagesIndexCount;

    /**
     * @var int
     */
    private int $messagesPageCount;

    /**
     * @var int
     */
    private int $callsIndexCount;

    /**
     * @var int
     */
    private int $callsPageCount;

    /**
     * @var string[]
     */
    private static array $guarded = [
        'app',
        'cacheDriver',
        'configRepo',
        'filesystem',
        'provider',
        'id',
        'alias',
        'ghost',
        'providerClass',
        'providerMessengerModel',
        'providerHasFriends',
        'ghostParticipant',
        'providerIsSet',
        'providers',
        'providerCanSearch',
        'providerCanFriend',
        'providerCanMessageFirst',
        'providerHasDevices',
        'defaultNotFoundImage',
        'defaultThreadAvatars',
        'avatarStorage',
        'threadStorage',
    ];

    /**
     * Is the messenger config cached?
     *
     * @return bool
     */
    public function isProvidersCached(): bool
    {
        return $this->isProvidersCached
            ?: $this->filesystem->exists($this->app->bootstrapPath('cache/messenger.php'));
    }

    /**
     * Set the configuration properties dynamically.
     *
     * @param  array $params
     * @return $this
     */
    public function setConfig(array $params): self
    {
        foreach ($params as $key => $value) {
            if (property_exists($this, $key)
                && ! in_array($key, self::$guarded)) {
                $this->{$key} = $value;
            }
        }

        return $this;
    }

    /**
     * Format the config for a response to the frontend.
     *
     * @return array
     * @noinspection SpellCheckingInspection
     */
    public function getConfig(): array
    {
        return collect(get_object_vars($this))
            ->reject(fn ($value, $key) => in_array($key, self::$guarded) && ! in_array($key, ['isProvidersCached']))
            ->merge([
                'providers' => $this->providers->map(function ($provider) {
                    return [
                        'default_avatar' => basename($provider['default_avatar']),
                        'searchable' => $provider['searchable'],
                        'friendable' => $provider['friendable'],
                        'devices' => $provider['devices'],
                        'provider_interactions' => $provider['provider_interactions'],
                    ];
                }),
            ])
            ->toArray();
    }

    /**
     * @param bool $pushNotifications
     * @return $this
     */
    public function setPushNotifications(bool $pushNotifications): self
    {
        $this->pushNotifications = $pushNotifications;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPushNotificationsEnabled(): bool
    {
        return $this->pushNotifications;
    }

    /**
     * @return bool
     */
    public function isKnockKnockEnabled(): bool
    {
        return $this->knockKnock;
    }

    /**
     * @param bool $knockKnock
     * @return $this
     */
    public function setKnockKnock(bool $knockKnock): self
    {
        $this->knockKnock = $knockKnock;

        return $this;
    }

    /**
     * @return int
     */
    public function getKnockTimeout(): int
    {
        return $this->knockTimeout;
    }

    /**
     * @param int $knockTimeout
     * @return $this
     */
    public function setKnockTimeout(int $knockTimeout): self
    {
        $this->knockTimeout = $knockTimeout;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCallingEnabled(): bool
    {
        return $this->calling;
    }

    /**
     * @param bool $calling
     * @return $this
     */
    public function setCalling(bool $calling): self
    {
        $this->calling = $calling;

        return $this;
    }

    /**
     * @param bool $invites
     * @return $this
     */
    public function setThreadInvites(bool $invites): self
    {
        $this->threadInvites = $invites;

        return $this;
    }

    /**
     * @return bool
     */
    public function isThreadInvitesEnabled(): bool
    {
        return $this->threadInvites;
    }

    /**
     * @param int $maxInviteCount
     * @return $this
     */
    public function setThreadInvitesMaxCount(int $maxInviteCount): self
    {
        $this->threadInvitesMax = $maxInviteCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getThreadMaxInvitesCount(): int
    {
        return $this->threadInvitesMax;
    }

    /**
     * @return bool
     */
    public function isMessageImageUploadEnabled(): bool
    {
        return $this->messageImageUpload;
    }

    /**
     * @param bool $messageImageUpload
     * @return $this
     */
    public function setMessageImageUpload(bool $messageImageUpload): self
    {
        $this->messageImageUpload = $messageImageUpload;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMessageDocumentUploadEnabled(): bool
    {
        return $this->messageDocumentUpload;
    }

    /**
     * @param bool $messageDocumentUpload
     * @return $this
     */
    public function setMessageDocumentUpload(bool $messageDocumentUpload): self
    {
        $this->messageDocumentUpload = $messageDocumentUpload;

        return $this;
    }

    /**
     * @return bool
     */
    public function isThreadAvatarUploadEnabled(): bool
    {
        return $this->threadAvatarUpload;
    }

    /**
     * @param bool $threadAvatarUpload
     * @return $this
     */
    public function setThreadAvatarUpload(bool $threadAvatarUpload): self
    {
        $this->threadAvatarUpload = $threadAvatarUpload;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMessageDocumentDownloadEnabled(): bool
    {
        return $this->messageDocumentDownload;
    }

    /**
     * @param bool $messageDocumentDownload
     * @return $this
     */
    public function setMessageDocumentDownload(bool $messageDocumentDownload): self
    {
        $this->messageDocumentDownload = $messageDocumentDownload;

        return $this;
    }

    /**
     * @return bool
     */
    public function isProviderAvatarUploadEnabled(): bool
    {
        return $this->providerAvatarUpload;
    }

    /**
     * @param bool $providerAvatarUpload
     * @return $this
     */
    public function setProviderAvatarUpload(bool $providerAvatarUpload): self
    {
        $this->providerAvatarUpload = $providerAvatarUpload;

        return $this;
    }

    /**
     * @return bool
     */
    public function isProviderAvatarRemovalEnabled(): bool
    {
        return $this->providerAvatarRemoval;
    }

    /**
     * @param bool $providerAvatarRemoval
     * @return $this
     */
    public function setProviderAvatarRemoval(bool $providerAvatarRemoval): self
    {
        $this->providerAvatarRemoval = $providerAvatarRemoval;

        return $this;
    }

    /**
     * @return bool
     */
    public function isOnlineStatusEnabled(): bool
    {
        return $this->onlineStatus;
    }

    /**
     * @param bool $onlineStatus
     * @return $this
     */
    public function setOnlineStatus(bool $onlineStatus): self
    {
        $this->onlineStatus = $onlineStatus;

        return $this;
    }

    /**
     * @return int
     */
    public function getOnlineCacheLifetime(): int
    {
        return $this->onlineCacheLifetime;
    }

    /**
     * @param int $onlineCacheLifetime
     * @return $this
     */
    public function setOnlineCacheLifetime(int $onlineCacheLifetime): self
    {
        $this->onlineCacheLifetime = $onlineCacheLifetime;

        return $this;
    }

    /**
     * @return int
     */
    public function getThreadsIndexCount(): int
    {
        return $this->threadsIndexCount;
    }

    /**
     * @param int $threadsIndexCount
     * @return $this
     */
    public function setThreadsIndexCount(int $threadsIndexCount): self
    {
        $this->threadsIndexCount = $threadsIndexCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getSearchPageCount(): int
    {
        return $this->searchPageCount;
    }

    /**
     * @param int $searchPageCount
     * @return $this
     */
    public function setSearchPageCount(int $searchPageCount): self
    {
        $this->searchPageCount = $searchPageCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getThreadsPageCount(): int
    {
        return $this->threadsPageCount;
    }

    /**
     * @param int $threadsPageCount
     * @return $this
     */
    public function setThreadsPageCount(int $threadsPageCount): self
    {
        $this->threadsPageCount = $threadsPageCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getParticipantsIndexCount(): int
    {
        return $this->participantsIndexCount;
    }

    /**
     * @param int $participantsIndexCount
     * @return $this
     */
    public function setParticipantsIndexCount(int $participantsIndexCount): self
    {
        $this->participantsIndexCount = $participantsIndexCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getParticipantsPageCount(): int
    {
        return $this->participantsPageCount;
    }

    /**
     * @param int $participantsPageCount
     * @return $this
     */
    public function setParticipantsPageCount(int $participantsPageCount): self
    {
        $this->participantsPageCount = $participantsPageCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getMessagesIndexCount(): int
    {
        return $this->messagesIndexCount;
    }

    /**
     * @param int $messagesIndexCount
     * @return $this
     */
    public function setMessagesIndexCount(int $messagesIndexCount): self
    {
        $this->messagesIndexCount = $messagesIndexCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getMessagesPageCount(): int
    {
        return $this->messagesPageCount;
    }

    /**
     * @param int $messagesPageCount
     * @return $this
     */
    public function setMessagesPageCount(int $messagesPageCount): self
    {
        $this->messagesPageCount = $messagesPageCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getCallsIndexCount(): int
    {
        return $this->callsIndexCount;
    }

    /**
     * @param int $callsIndexCount
     * @return $this
     */
    public function setCallsIndexCount(int $callsIndexCount): self
    {
        $this->callsIndexCount = $callsIndexCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getCallsPageCount(): int
    {
        return $this->callsPageCount;
    }

    /**
     * @param int $callsPageCount
     * @return $this
     */
    public function setCallsPageCount(int $callsPageCount): self
    {
        $this->callsPageCount = $callsPageCount;

        return $this;
    }

    /**
     * @param string|null $config
     * @return array|string
     */
    public function getAvatarStorage(string $config = null)
    {
        if (! is_null($config)) {
            return trim($this->avatarStorage[$config], '/');
        }

        return $this->avatarStorage;
    }

    /**
     * @param string|null $config
     * @return array|string
     */
    public function getThreadStorage(string $config = null)
    {
        if (! is_null($config)) {
            return trim($this->threadStorage[$config], '/');
        }

        return $this->threadStorage;
    }

    /**
     * @return string
     */
    public function getBroadcastDriver(): string
    {
        return $this->broadcastDriver;
    }

    /**
     * @return string
     */
    public function getVideoDriver(): string
    {
        return $this->videoDriver;
    }

    /**
     * @return string
     */
    public function getApiEndpoint(): string
    {
        return $this->apiEndpoint;
    }

    /**
     * @return string
     */
    public function getWebEndpoint(): string
    {
        return $this->webEndpoint;
    }

    /**
     * @return string
     */
    public function getSocketEndpoint(): string
    {
        return $this->socketEndpoint;
    }

    /**
     * @return string
     */
    public function getSiteName(): string
    {
        return $this->siteName;
    }

    /**
     * @return string
     */
    public function getDefaultNotFoundImage(): string
    {
        return $this->defaultNotFoundImage;
    }

    /**
     * @param string|null $image
     * @return array|string
     */
    public function getDefaultThreadAvatars(string $image = null)
    {
        if (! is_null($image)) {
            return $this->defaultThreadAvatars[$image];
        }

        return $this->defaultThreadAvatars;
    }

    /**
     * Set all configs from the config file.
     */
    private function setMessengerConfig(): void
    {
        $this->siteName = $this->configRepo->get('messenger.site_name');
        $this->apiEndpoint = '/'.$this->configRepo->get('messenger.routing.api.prefix');
        $this->webEndpoint = '/'.$this->configRepo->get('messenger.routing.web.prefix');
        $this->socketEndpoint = $this->configRepo->get('messenger.socket_endpoint');
        $this->avatarStorage = $this->configRepo->get('messenger.storage.avatars');
        $this->threadStorage = $this->configRepo->get('messenger.storage.threads');
        $this->defaultNotFoundImage = $this->configRepo->get('messenger.files.default_not_found_image');
        $this->defaultThreadAvatars = $this->configRepo->get('messenger.files.default_thread_avatars');
        $this->broadcastDriver = $this->configRepo->get('messenger.broadcasting.driver') ?? 'null';
        $this->videoDriver = $this->configRepo->get('messenger.calling.driver') ?? 'null';
        $this->pushNotifications = $this->configRepo->get('messenger.push_notifications.enabled');
        $this->knockKnock = $this->configRepo->get('messenger.knocks.enabled');
        $this->knockTimeout = $this->configRepo->get('messenger.knocks.timeout');
        $this->threadInvites = $this->configRepo->get('messenger.invites.enabled');
        $this->threadInvitesMax = $this->configRepo->get('messenger.invites.max_per_thread');
        $this->onlineStatus = $this->configRepo->get('messenger.online_status.enabled');
        $this->onlineCacheLifetime = $this->configRepo->get('messenger.online_status.lifetime');
        $this->calling = $this->configRepo->get('messenger.calling.enabled');
        $this->providerAvatarUpload = $this->configRepo->get('messenger.files.provider_avatars.upload');
        $this->providerAvatarRemoval = $this->configRepo->get('messenger.files.provider_avatars.removal');
        $this->messageImageUpload = $this->configRepo->get('messenger.files.message_images.upload');
        $this->messageDocumentUpload = $this->configRepo->get('messenger.files.message_documents.upload');
        $this->messageDocumentDownload = $this->configRepo->get('messenger.files.message_documents.download');
        $this->threadAvatarUpload = $this->configRepo->get('messenger.files.thread_avatars.upload');
        $this->searchPageCount = $this->configRepo->get('messenger.collections.search.page_count');
        $this->threadsIndexCount = $this->configRepo->get('messenger.collections.threads.index_count');
        $this->threadsPageCount = $this->configRepo->get('messenger.collections.threads.page_count');
        $this->participantsIndexCount = $this->configRepo->get('messenger.collections.participants.index_count');
        $this->participantsPageCount = $this->configRepo->get('messenger.collections.participants.page_count');
        $this->messagesIndexCount = $this->configRepo->get('messenger.collections.messages.index_count');
        $this->messagesPageCount = $this->configRepo->get('messenger.collections.messages.page_count');
        $this->callsIndexCount = $this->configRepo->get('messenger.collections.calls.index_count');
        $this->callsPageCount = $this->configRepo->get('messenger.collections.calls.page_count');
    }

    /**
     * Set providers from cache if exist, otherwise set from config.
     */
    private function setMessengerProviders(): void
    {
        if ($this->isProvidersCached) {
            $providersFile = $this->loadCachedProvidersFile();

            if ($providersFile) {
                $this->providers = collect(
                    $providersFile
                );
            } else {
                $this->setProvidersFromConfig();
            }
        } else {
            $this->setProvidersFromConfig();
        }
    }

    /**
     * Set providers from config.
     */
    private function setProvidersFromConfig(): void
    {
        $this->providers = $this->formatValidProviders(
            $this->configRepo->get('messenger.providers')
        );
    }

    /**
     * @return mixed|null
     * @noinspection PhpIncludeInspection
     */
    private function loadCachedProvidersFile()
    {
        try {
            return require $this->app->bootstrapPath('cache/messenger.php');
        } catch (Exception $e) {
            report($e);

            return null;
        }
    }
}
