<?php

namespace RTippin\Messenger;

use Exception;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Support\ProvidersVerification;
use RTippin\MessengerBots\MessengerBotsServiceProvider;

/**
 * @property-read Collection $providers
 * @property-read Application $app
 * @property-read CacheRepository $cacheDriver
 * @property-read ConfigRepository $configRepo
 * @property-read Filesystem $filesystem
 * @property-read ProvidersVerification $providersVerification
 */
trait MessengerConfig
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
     * @var bool
     */
    private bool $webRoutes;

    /**
     * @var bool
     */
    private bool $providerAvatarRoutes;

    /**
     * @var bool
     */
    private bool $channelRoutes;

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
    private bool $botsInstalled;

    /**
     * @var bool
     */
    private bool $messageEdits;

    /**
     * @var bool
     */
    private bool $messageReactions;

    /**
     * @var int
     */
    private int $messageReactionsMax;

    /**
     * @var bool
     */
    private bool $messageEditsView;

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
     * @var int
     */
    private int $providerAvatarSizeLimit;

    /**
     * @var string
     */
    private string $providerAvatarMimeTypes;

    /**
     * @var bool
     */
    private bool $messageDocumentUpload;

    /**
     * @var int
     */
    private int $messageDocumentSizeLimit;

    /**
     * @var string
     */
    private string $messageDocumentMimeTypes;

    /**
     * @var bool
     */
    private bool $messageImageUpload;

    /**
     * @var int
     */
    private int $messageImageSizeLimit;

    /**
     * @var string
     */
    private string $messageImageMimeTypes;

    /**
     * @var bool
     */
    private bool $messageAudioUpload;

    /**
     * @var int
     */
    private int $messageAudioSizeLimit;

    /**
     * @var string
     */
    private string $messageAudioMimeTypes;

    /**
     * @var bool
     */
    private bool $threadAvatarUpload;

    /**
     * @var int
     */
    private int $threadAvatarSizeLimit;

    /**
     * @var string
     */
    private string $threadAvatarMimeTypes;

    /**
     * @var int
     */
    private int $apiRateLimit;

    /**
     * @var int
     */
    private int $searchRateLimit;

    /**
     * @var int
     */
    private int $messageRateLimit;

    /**
     * @var int
     */
    private int $attachmentRateLimit;

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
        'providerMessengerModel',
        'providerHasFriends',
        'ghostParticipant',
        'providerIsSet',
        'providers',
        'providerCanSearch',
        'providerCanFriend',
        'providerCanMessageFirst',
        'providerHasDevices',
        'providersVerification',
        'defaultNotFoundImage',
        'defaultThreadAvatars',
        'avatarStorage',
        'threadStorage',
        'botsInstalled',
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
        return (new Collection(get_object_vars($this)))->reject(function ($value, $key) {
            return in_array($key, self::$guarded) && ! in_array($key, ['isProvidersCached']);
        })
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
    public function isMessageEditsEnabled(): bool
    {
        return $this->messageEdits;
    }

    /**
     * @param bool $messageEdits
     * @return $this
     */
    public function setMessageEdits(bool $messageEdits): self
    {
        $this->messageEdits = $messageEdits;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMessageEditsViewEnabled(): bool
    {
        return $this->messageEditsView;
    }

    /**
     * @param bool $messageEditsView
     * @return $this
     */
    public function setMessageEditsView(bool $messageEditsView): self
    {
        $this->messageEditsView = $messageEditsView;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMessageReactionsEnabled(): bool
    {
        return $this->messageReactions;
    }

    /**
     * @param bool $messageReactions
     * @return $this
     */
    public function setMessageReactions(bool $messageReactions): self
    {
        $this->messageReactions = $messageReactions;

        return $this;
    }

    /**
     * @return int
     */
    public function getMessageReactionsMax(): int
    {
        return $this->messageReactionsMax;
    }

    /**
     * @param int $messageReactionsMax
     * @return $this
     */
    public function setMessageReactionsMax(int $messageReactionsMax): self
    {
        $this->messageReactionsMax = $messageReactionsMax;

        return $this;
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
     * @param int $minutesDisabled
     * @return $this
     */
    public function disableCallsTemporarily(int $minutesDisabled): self
    {
        $this->cacheDriver->put('messenger:calls:down', true, now()->addMinutes($minutesDisabled));

        return $this;
    }

    /**
     * @return bool
     * @throws InvalidArgumentException
     */
    public function isCallingTemporarilyDisabled(): bool
    {
        return $this->cacheDriver->has('messenger:calls:down');
    }

    /**
     * @return $this
     * @throws InvalidArgumentException
     */
    public function removeTemporaryCallShutdown(): self
    {
        if ($this->isCallingTemporarilyDisabled()) {
            $this->cacheDriver->forget('messenger:calls:down');
        }

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
     * @return bool
     */
    public function isMessengerBotsInstalled(): bool
    {
        return $this->botsInstalled;
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
     * @return int
     */
    public function getMessageImageSizeLimit(): int
    {
        return $this->messageImageSizeLimit;
    }

    /**
     * @param int $messageImageSizeLimit
     * @return $this
     */
    public function setMessageImageSizeLimit(int $messageImageSizeLimit): self
    {
        $this->messageImageSizeLimit = $messageImageSizeLimit;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageImageMimeTypes(): string
    {
        return $this->messageImageMimeTypes;
    }

    /**
     * @param string $messageImageMimeTypes
     * @return $this
     */
    public function setMessageImageMimeTypes(string $messageImageMimeTypes): self
    {
        $this->messageImageMimeTypes = $messageImageMimeTypes;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMessageAudioUploadEnabled(): bool
    {
        return $this->messageAudioUpload;
    }

    /**
     * @param bool $messageAudioUpload
     * @return $this
     */
    public function setMessageAudioUpload(bool $messageAudioUpload): self
    {
        $this->messageAudioUpload = $messageAudioUpload;

        return $this;
    }

    /**
     * @return int
     */
    public function getMessageAudioSizeLimit(): int
    {
        return $this->messageAudioSizeLimit;
    }

    /**
     * @param int $messageAudioSizeLimit
     * @return $this
     */
    public function setMessageAudioSizeLimit(int $messageAudioSizeLimit): self
    {
        $this->messageAudioSizeLimit = $messageAudioSizeLimit;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageAudioMimeTypes(): string
    {
        return $this->messageAudioMimeTypes;
    }

    /**
     * @param string $messageAudioMimeTypes
     * @return $this
     */
    public function setMessageAudioMimeTypes(string $messageAudioMimeTypes): self
    {
        $this->messageAudioMimeTypes = $messageAudioMimeTypes;

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
     * @return int
     */
    public function getMessageDocumentSizeLimit(): int
    {
        return $this->messageDocumentSizeLimit;
    }

    /**
     * @param int $messageDocumentSizeLimit
     * @return $this
     */
    public function setMessageDocumentSizeLimit(int $messageDocumentSizeLimit): self
    {
        $this->messageDocumentSizeLimit = $messageDocumentSizeLimit;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageDocumentMimeTypes(): string
    {
        return $this->messageDocumentMimeTypes;
    }

    /**
     * @param string $messageDocumentMimeTypes
     * @return $this
     */
    public function setMessageDocumentMimeTypes(string $messageDocumentMimeTypes): self
    {
        $this->messageDocumentMimeTypes = $messageDocumentMimeTypes;

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
     * @return int
     */
    public function getThreadAvatarSizeLimit(): int
    {
        return $this->threadAvatarSizeLimit;
    }

    /**
     * @param int $threadAvatarSizeLimit
     * @return $this
     */
    public function setThreadAvatarSizeLimit(int $threadAvatarSizeLimit): self
    {
        $this->threadAvatarSizeLimit = $threadAvatarSizeLimit;

        return $this;
    }

    /**
     * @return string
     */
    public function getThreadAvatarMimeTypes(): string
    {
        return $this->threadAvatarMimeTypes;
    }

    /**
     * @param string $threadAvatarMimeTypes
     * @return $this
     */
    public function setThreadAvatarMimeTypes(string $threadAvatarMimeTypes): self
    {
        $this->threadAvatarMimeTypes = $threadAvatarMimeTypes;

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
     * @return int
     */
    public function getProviderAvatarSizeLimit(): int
    {
        return $this->providerAvatarSizeLimit;
    }

    /**
     * @param int $providerAvatarSizeLimit
     * @return $this
     */
    public function setProviderAvatarSizeLimit(int $providerAvatarSizeLimit): self
    {
        $this->providerAvatarSizeLimit = $providerAvatarSizeLimit;

        return $this;
    }

    /**
     * @return string
     */
    public function getProviderAvatarMimeTypes(): string
    {
        return $this->providerAvatarMimeTypes;
    }

    /**
     * @param string $providerAvatarMimeTypes
     * @return $this
     */
    public function setProviderAvatarMimeTypes(string $providerAvatarMimeTypes): self
    {
        $this->providerAvatarMimeTypes = $providerAvatarMimeTypes;

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
    public function getApiRateLimit(): int
    {
        return $this->apiRateLimit;
    }

    /**
     * @param int $apiRateLimit
     * @return $this
     */
    public function setApiRateLimit(int $apiRateLimit): self
    {
        $this->apiRateLimit = $apiRateLimit;

        return $this;
    }

    /**
     * @return int
     */
    public function getSearchRateLimit(): int
    {
        return $this->searchRateLimit;
    }

    /**
     * @param int $searchRateLimit
     * @return $this
     */
    public function setSearchRateLimit(int $searchRateLimit): self
    {
        $this->searchRateLimit = $searchRateLimit;

        return $this;
    }

    /**
     * @return int
     */
    public function getMessageRateLimit(): int
    {
        return $this->messageRateLimit;
    }

    /**
     * @param int $messageRateLimit
     * @return $this
     */
    public function setMessageRateLimit(int $messageRateLimit): self
    {
        $this->messageRateLimit = $messageRateLimit;

        return $this;
    }

    /**
     * @return int
     */
    public function getAttachmentRateLimit(): int
    {
        return $this->attachmentRateLimit;
    }

    /**
     * @param int $attachmentRateLimit
     * @return $this
     */
    public function setAttachmentRateLimit(int $attachmentRateLimit): self
    {
        $this->attachmentRateLimit = $attachmentRateLimit;

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
     * @param string $driverAlias
     * @return $this
     */
    public function setBroadcastDriver(string $driverAlias): self
    {
        $this->broadcastDriver = $driverAlias;

        $driver = $this->configRepo->get('messenger.drivers.broadcasting')[$driverAlias];

        $this->app->singleton(BroadcastDriver::class, $driver);

        return $this;
    }

    /**
     * @return string
     */
    public function getVideoDriver(): string
    {
        return $this->videoDriver;
    }

    /**
     * @param string $driverAlias
     * @return $this
     */
    public function setVideoDriver(string $driverAlias): self
    {
        $this->videoDriver = $driverAlias;

        $driver = $this->configRepo->get('messenger.drivers.calling')[$driverAlias];

        $this->app->singleton(VideoDriver::class, $driver);

        return $this;
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
     * @return bool
     */
    public function isWebRoutesEnabled(): bool
    {
        return $this->webRoutes;
    }

    /**
     * @return bool
     */
    public function isProviderAvatarRoutesEnabled(): bool
    {
        return $this->providerAvatarRoutes;
    }

    /**
     * @return bool
     */
    public function isChannelRoutesEnabled(): bool
    {
        return $this->channelRoutes;
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
     * @return array
     */
    public function getMessengerProviders(): array
    {
        return $this->providers->toArray();
    }

    /**
     * Set providers if provided, from cache if exist, otherwise set from config.
     *
     * @param array $providers
     */
    public function setMessengerProviders(array $providers = []): void
    {
        if (count($providers)) {
            $this->providers = $this->providersVerification->formatValidProviders($providers);
        } elseif ($this->isProvidersCached) {
            $providersFile = $this->loadCachedProvidersFile();
            if ($providersFile) {
                $this->providers = collect($providersFile);
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
        $this->providers = $this->providersVerification->formatValidProviders(
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

    /**
     * Set all configs from the config file.
     */
    private function setMessengerConfig(): void
    {
        $this->siteName = $this->configRepo->get('messenger.site_name');
        $this->apiEndpoint = '/'.$this->configRepo->get('messenger.routing.api.prefix');
        $this->webEndpoint = '/'.$this->configRepo->get('messenger.routing.web.prefix');
        $this->webRoutes = $this->configRepo->get('messenger.routing.web.enabled');
        $this->providerAvatarRoutes = $this->configRepo->get('messenger.routing.provider_avatar.enabled');
        $this->channelRoutes = $this->configRepo->get('messenger.routing.channels.enabled');
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
        $this->messageEdits = $this->configRepo->get('messenger.message_edits.enabled');
        $this->messageEditsView = $this->configRepo->get('messenger.message_edits.history_view');
        $this->messageReactions = $this->configRepo->get('messenger.message_reactions.enabled');
        $this->messageReactionsMax = $this->configRepo->get('messenger.message_reactions.max_unique');
        $this->threadInvites = $this->configRepo->get('messenger.invites.enabled');
        $this->threadInvitesMax = $this->configRepo->get('messenger.invites.max_per_thread');
        $this->onlineStatus = $this->configRepo->get('messenger.online_status.enabled');
        $this->onlineCacheLifetime = $this->configRepo->get('messenger.online_status.lifetime');
        $this->calling = $this->configRepo->get('messenger.calling.enabled');
        $this->providerAvatarUpload = $this->configRepo->get('messenger.files.provider_avatars.upload');
        $this->providerAvatarRemoval = $this->configRepo->get('messenger.files.provider_avatars.removal');
        $this->providerAvatarSizeLimit = $this->configRepo->get('messenger.files.provider_avatars.size_limit');
        $this->providerAvatarMimeTypes = $this->configRepo->get('messenger.files.provider_avatars.mime_types');
        $this->messageImageUpload = $this->configRepo->get('messenger.files.message_images.upload');
        $this->messageImageSizeLimit = $this->configRepo->get('messenger.files.message_images.size_limit');
        $this->messageImageMimeTypes = $this->configRepo->get('messenger.files.message_images.mime_types');
        $this->messageAudioUpload = $this->configRepo->get('messenger.files.message_audio.upload');
        $this->messageAudioSizeLimit = $this->configRepo->get('messenger.files.message_audio.size_limit');
        $this->messageAudioMimeTypes = $this->configRepo->get('messenger.files.message_audio.mime_types');
        $this->messageDocumentUpload = $this->configRepo->get('messenger.files.message_documents.upload');
        $this->messageDocumentSizeLimit = $this->configRepo->get('messenger.files.message_documents.size_limit');
        $this->messageDocumentMimeTypes = $this->configRepo->get('messenger.files.message_documents.mime_types');
        $this->threadAvatarUpload = $this->configRepo->get('messenger.files.thread_avatars.upload');
        $this->threadAvatarSizeLimit = $this->configRepo->get('messenger.files.thread_avatars.size_limit');
        $this->threadAvatarMimeTypes = $this->configRepo->get('messenger.files.thread_avatars.mime_types');
        $this->searchPageCount = $this->configRepo->get('messenger.collections.search.page_count');
        $this->threadsIndexCount = $this->configRepo->get('messenger.collections.threads.index_count');
        $this->threadsPageCount = $this->configRepo->get('messenger.collections.threads.page_count');
        $this->participantsIndexCount = $this->configRepo->get('messenger.collections.participants.index_count');
        $this->participantsPageCount = $this->configRepo->get('messenger.collections.participants.page_count');
        $this->messagesIndexCount = $this->configRepo->get('messenger.collections.messages.index_count');
        $this->messagesPageCount = $this->configRepo->get('messenger.collections.messages.page_count');
        $this->callsIndexCount = $this->configRepo->get('messenger.collections.calls.index_count');
        $this->callsPageCount = $this->configRepo->get('messenger.collections.calls.page_count');
        $this->apiRateLimit = $this->configRepo->get('messenger.rate_limits.api');
        $this->searchRateLimit = $this->configRepo->get('messenger.rate_limits.search');
        $this->messageRateLimit = $this->configRepo->get('messenger.rate_limits.message');
        $this->attachmentRateLimit = $this->configRepo->get('messenger.rate_limits.attachment');
        $this->botsInstalled = class_exists(MessengerBotsServiceProvider::class);
    }
}
