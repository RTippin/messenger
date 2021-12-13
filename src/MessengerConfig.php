<?php

namespace RTippin\Messenger;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\VideoDriver;

trait MessengerConfig
{
    /**
     * @var string
     */
    private string $apiEndpoint;

    /**
     * @var bool
     */
    private bool $channelRoutes;

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
     * @var string
     */
    private string $defaultThreadAvatar;

    /**
     * @var string
     */
    private string $defaultGhostAvatar;

    /**
     * @var string
     */
    private string $defaultBotAvatar;

    /**
     * @var int
     */
    private int $messageSizeLimit;

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
    private bool $bots;

    /**
     * @var bool
     */
    private bool $systemMessages;

    /**
     * @var bool
     */
    private bool $verifyPrivateThreadFriendship;

    /**
     * @var bool
     */
    private bool $verifyGroupThreadFriendship;

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
     * @var int
     */
    private int $avatarSizeLimit;

    /**
     * @var string
     */
    private string $avatarMimeTypes;

    /**
     * @var bool
     */
    private bool $providerAvatars;

    /**
     * @var bool
     */
    private bool $threadAvatars;

    /**
     * @var bool
     */
    private bool $botAvatars;

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
    private bool $messageVideoUpload;

    /**
     * @var int
     */
    private int $messageVideoSizeLimit;

    /**
     * @var string
     */
    private string $messageVideoMimeTypes;

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
     * @var array
     */
    private array $subscribers;

    /**
     * @var bool|null
     */
    private ?bool $callingTemporarilyDisabled = null;

    /**
     * @var string[]
     */
    private static array $guarded = [
        'provider',
        'scopedProvider',
        'id',
        'alias',
        'ghost',
        'ghostBot',
        'providerMessengerModel',
        'providerHasFriends',
        'ghostParticipant',
        'providers',
        'providerCanSearch',
        'providerCanFriend',
        'providerCanMessageFirst',
        'callingTemporarilyDisabled',
        'providerHasDevices',
        'defaultNotFoundImage',
        'defaultGhostAvatar',
        'defaultThreadAvatar',
        'defaultBotAvatar',
        'avatarStorage',
        'threadStorage',
        'subscribers',
    ];

    /**
     * Set the configuration properties dynamically.
     *
     * @param  array  $params
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
     */
    public function getConfig(): array
    {
        return Collection::make(get_object_vars($this))
            ->reject(fn ($value, $key) => in_array($key, self::$guarded))
            ->toArray();
    }

    /**
     * Get all the base system features and their settings.
     *
     * @return array
     */
    public function getSystemFeatures(): array
    {
        return [
            'bots' => $this->isBotsEnabled(),
            'calling' => $this->isCallingEnabled(),
            'invitations' => $this->isThreadInvitesEnabled(),
            'invitations_max' => $this->getThreadMaxInvitesCount(),
            'knocks' => $this->isKnockKnockEnabled(),
            'audio_messages' => $this->isMessageAudioUploadEnabled(),
            'document_messages' => $this->isMessageDocumentUploadEnabled(),
            'image_messages' => $this->isMessageImageUploadEnabled(),
            'message_edits' => $this->isMessageEditsEnabled(),
            'message_edits_view' => $this->isMessageEditsViewEnabled(),
            'message_reactions' => $this->isMessageReactionsEnabled(),
            'message_reactions_max' => $this->getMessageReactionsMax(),
            'provider_avatars' => $this->isProviderAvatarEnabled(),
            'thread_avatars' => $this->isThreadAvatarEnabled(),
            'bot_avatars' => $this->isBotAvatarEnabled(),
        ];
    }

    /**
     * @return int
     */
    public function getMessageSizeLimit(): int
    {
        return $this->messageSizeLimit;
    }

    /**
     * @param  int  $messageSizeLimit
     * @return $this
     */
    public function setMessageSizeLimit(int $messageSizeLimit): self
    {
        $this->messageSizeLimit = $messageSizeLimit;

        return $this;
    }

    /**
     * @param  bool  $pushNotifications
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
     * @param  bool  $messageEdits
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
     * @param  bool  $messageEditsView
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
     * @param  bool  $messageReactions
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
     * @param  int  $messageReactionsMax
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
     * @param  bool  $knockKnock
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
     * @param  int  $knockTimeout
     * @return $this
     */
    public function setKnockTimeout(int $knockTimeout): self
    {
        $this->knockTimeout = $knockTimeout;

        return $this;
    }

    /**
     * @param  int  $minutesDisabled
     * @return $this
     */
    public function disableCallsTemporarily(int $minutesDisabled): self
    {
        Cache::put('messenger:calls:down', true, now()->addMinutes($minutesDisabled));

        return $this;
    }

    /**
     * @return bool
     */
    public function isCallingTemporarilyDisabled(): bool
    {
        if (! is_null($this->callingTemporarilyDisabled)) {
            return $this->callingTemporarilyDisabled;
        }

        return $this->callingTemporarilyDisabled = Cache::has('messenger:calls:down');
    }

    /**
     * @return $this
     */
    public function removeTemporaryCallShutdown(): self
    {
        if ($this->isCallingTemporarilyDisabled()) {
            Cache::forget('messenger:calls:down');
            $this->callingTemporarilyDisabled = false;
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
     * @param  bool  $calling
     * @return $this
     */
    public function setCalling(bool $calling): self
    {
        $this->calling = $calling;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBotsEnabled(): bool
    {
        return $this->bots;
    }

    /**
     * @param  bool  $bots
     * @return $this
     */
    public function setBots(bool $bots): self
    {
        $this->bots = $bots;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldVerifyPrivateThreadFriendship(): bool
    {
        return $this->verifyPrivateThreadFriendship;
    }

    /**
     * @param  bool  $verifyPrivateThreadFriendship
     * @return $this
     */
    public function setVerifyPrivateThreadFriendship(bool $verifyPrivateThreadFriendship): self
    {
        $this->verifyPrivateThreadFriendship = $verifyPrivateThreadFriendship;

        return $this;
    }

    /**
     * @return bool
     */
    public function shouldVerifyGroupThreadFriendship(): bool
    {
        return $this->verifyGroupThreadFriendship;
    }

    /**
     * @param  bool  $verifyGroupThreadFriendship
     * @return $this
     */
    public function setVerifyGroupThreadFriendship(bool $verifyGroupThreadFriendship): self
    {
        $this->verifyGroupThreadFriendship = $verifyGroupThreadFriendship;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSystemMessagesEnabled(): bool
    {
        return $this->systemMessages;
    }

    /**
     * @param  bool  $systemMessages
     * @return $this
     */
    public function setSystemMessages(bool $systemMessages): self
    {
        $this->systemMessages = $systemMessages;

        return $this;
    }

    /**
     * @param  bool  $invites
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
     * @param  int  $maxInviteCount
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
     * @param  bool  $messageImageUpload
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
     * @param  int  $messageImageSizeLimit
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
     * @param  string  $messageImageMimeTypes
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
     * @param  bool  $messageAudioUpload
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
     * @param  int  $messageAudioSizeLimit
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
     * @param  string  $messageAudioMimeTypes
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
     * @param  bool  $messageDocumentUpload
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
     * @param  int  $messageDocumentSizeLimit
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
     * @param  string  $messageDocumentMimeTypes
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
    public function isMessageVideoUploadEnabled(): bool
    {
        return $this->messageVideoUpload;
    }

    /**
     * @param  bool  $messageVideoUpload
     * @return $this
     */
    public function setMessageVideoUpload(bool $messageVideoUpload): self
    {
        $this->messageVideoUpload = $messageVideoUpload;

        return $this;
    }

    /**
     * @return int
     */
    public function getMessageVideoSizeLimit(): int
    {
        return $this->messageVideoSizeLimit;
    }

    /**
     * @param  int  $messageVideoSizeLimit
     * @return $this
     */
    public function setMessageVideoSizeLimit(int $messageVideoSizeLimit): self
    {
        $this->messageVideoSizeLimit = $messageVideoSizeLimit;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessageVideoMimeTypes(): string
    {
        return $this->messageVideoMimeTypes;
    }

    /**
     * @param  string  $messageVideoMimeTypes
     * @return $this
     */
    public function setMessageVideoMimeTypes(string $messageVideoMimeTypes): self
    {
        $this->messageVideoMimeTypes = $messageVideoMimeTypes;

        return $this;
    }

    /**
     * @return bool
     */
    public function isProviderAvatarEnabled(): bool
    {
        return $this->providerAvatars;
    }

    /**
     * @param  bool  $providerAvatars
     * @return $this
     */
    public function setProviderAvatars(bool $providerAvatars): self
    {
        $this->providerAvatars = $providerAvatars;

        return $this;
    }

    /**
     * @return bool
     */
    public function isThreadAvatarEnabled(): bool
    {
        return $this->threadAvatars;
    }

    /**
     * @param  bool  $threadAvatars
     * @return $this
     */
    public function setThreadAvatars(bool $threadAvatars): self
    {
        $this->threadAvatars = $threadAvatars;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBotAvatarEnabled(): bool
    {
        return $this->botAvatars;
    }

    /**
     * @param  bool  $botAvatars
     * @return $this
     */
    public function setBotAvatars(bool $botAvatars): self
    {
        $this->botAvatars = $botAvatars;

        return $this;
    }

    /**
     * @return int
     */
    public function getAvatarSizeLimit(): int
    {
        return $this->avatarSizeLimit;
    }

    /**
     * @param  int  $avatarSizeLimit
     * @return $this
     */
    public function setAvatarSizeLimit(int $avatarSizeLimit): self
    {
        $this->avatarSizeLimit = $avatarSizeLimit;

        return $this;
    }

    /**
     * @return string
     */
    public function getAvatarMimeTypes(): string
    {
        return $this->avatarMimeTypes;
    }

    /**
     * @param  string  $avatarMimeTypes
     * @return $this
     */
    public function setAvatarMimeTypes(string $avatarMimeTypes): self
    {
        $this->avatarMimeTypes = $avatarMimeTypes;

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
     * @param  bool  $onlineStatus
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
     * @param  int  $onlineCacheLifetime
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
     * @param  int  $threadsIndexCount
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
     * @param  int  $apiRateLimit
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
     * @param  int  $searchRateLimit
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
     * @param  int  $messageRateLimit
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
     * @param  int  $attachmentRateLimit
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
     * @param  int  $searchPageCount
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
     * @param  int  $threadsPageCount
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
     * @param  int  $participantsIndexCount
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
     * @param  int  $participantsPageCount
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
     * @param  int  $messagesIndexCount
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
     * @param  int  $messagesPageCount
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
     * @param  int  $callsIndexCount
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
     * @param  int  $callsPageCount
     * @return $this
     */
    public function setCallsPageCount(int $callsPageCount): self
    {
        $this->callsPageCount = $callsPageCount;

        return $this;
    }

    /**
     * @param  string|null  $config
     * @return array|string
     */
    public function getAvatarStorage(?string $config = null)
    {
        if (! is_null($config)) {
            return trim($this->avatarStorage[$config], '/');
        }

        return $this->avatarStorage;
    }

    /**
     * @param  string|null  $config
     * @return array|string
     */
    public function getThreadStorage(?string $config = null)
    {
        if (! is_null($config)) {
            return trim($this->threadStorage[$config], '/');
        }

        return $this->threadStorage;
    }

    /**
     * @param  string  $driver
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function setBroadcastDriver(string $driver): self
    {
        if (! is_subclass_of($driver, BroadcastDriver::class)) {
            throw new InvalidArgumentException("The given driver { $driver } must implement our interface ".BroadcastDriver::class);
        }

        app()->bind(BroadcastDriver::class, $driver);

        return $this;
    }

    /**
     * @param  string  $driver
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function setVideoDriver(string $driver): self
    {
        if (! is_subclass_of($driver, VideoDriver::class)) {
            throw new InvalidArgumentException("The given driver { $driver } must implement our interface ".VideoDriver::class);
        }

        app()->bind(VideoDriver::class, $driver);

        return $this;
    }

    /**
     * @param  string  $driver
     * @return $this
     *
     * @throws InvalidArgumentException
     */
    public function setFriendDriver(string $driver): self
    {
        if (! is_subclass_of($driver, FriendDriver::class)) {
            throw new InvalidArgumentException("The given driver { $driver } must implement our interface ".FriendDriver::class);
        }

        app()->singleton(FriendDriver::class, $driver);

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
     * @return bool
     */
    public function isChannelRoutesEnabled(): bool
    {
        return $this->channelRoutes;
    }

    /**
     * @return string
     */
    public function getDefaultGhostAvatar(): string
    {
        return $this->defaultGhostAvatar;
    }

    /**
     * @return string
     */
    public function getDefaultThreadAvatar(): string
    {
        return $this->defaultThreadAvatar;
    }

    /**
     * @return string
     */
    public function getDefaultBotAvatar(): string
    {
        return $this->defaultBotAvatar;
    }

    /**
     * @return string
     */
    public function getDefaultNotFoundImage(): string
    {
        return $this->defaultNotFoundImage;
    }

    /**
     * @param  string  $option
     * @return bool|string
     */
    public function getBotSubscriber(string $option)
    {
        return $this->subscribers['bots'][$option];
    }

    /**
     * @param  string  $option
     * @param  bool|string  $value
     * @return $this
     */
    public function setBotSubscriber(string $option, $value): self
    {
        $this->subscribers['bots'][$option] = $value;

        return $this;
    }

    /**
     * @param  string  $option
     * @return bool|string
     */
    public function getCallSubscriber(string $option)
    {
        return $this->subscribers['calls'][$option];
    }

    /**
     * @param  string  $option
     * @param  bool|string  $value
     * @return $this
     */
    public function setCallSubscriber(string $option, $value): self
    {
        $this->subscribers['calls'][$option] = $value;

        return $this;
    }

    /**
     * @param  string  $option
     * @return bool|string
     */
    public function getSystemMessageSubscriber(string $option)
    {
        return $this->subscribers['system_messages'][$option];
    }

    /**
     * @param  string  $option
     * @param  bool|string  $value
     * @return $this
     */
    public function setSystemMessageSubscriber(string $option, $value): self
    {
        $this->subscribers['system_messages'][$option] = $value;

        return $this;
    }

    /**
     * Set all configs from the config file.
     */
    private function setMessengerConfig(): void
    {
        $this->apiEndpoint = '/'.config('messenger.routing.api.prefix');
        $this->channelRoutes = config('messenger.routing.channels.enabled');
        $this->avatarStorage = config('messenger.storage.avatars');
        $this->threadStorage = config('messenger.storage.threads');
        $this->messageSizeLimit = config('messenger.message_size_limit');
        $this->defaultNotFoundImage = config('messenger.files.default_not_found_image');
        $this->defaultGhostAvatar = config('messenger.files.default_ghost_avatar');
        $this->defaultThreadAvatar = config('messenger.files.default_thread_avatar');
        $this->defaultBotAvatar = config('messenger.files.default_bot_avatar');
        $this->pushNotifications = config('messenger.push_notifications');
        $this->knockKnock = config('messenger.knocks.enabled');
        $this->knockTimeout = config('messenger.knocks.timeout');
        $this->messageEdits = config('messenger.message_edits.enabled');
        $this->messageEditsView = config('messenger.message_edits.history_view');
        $this->messageReactions = config('messenger.message_reactions.enabled');
        $this->messageReactionsMax = config('messenger.message_reactions.max_unique');
        $this->threadInvites = config('messenger.invites.enabled');
        $this->threadInvitesMax = config('messenger.invites.max_per_thread');
        $this->onlineStatus = config('messenger.online_status.enabled');
        $this->onlineCacheLifetime = config('messenger.online_status.lifetime');
        $this->calling = config('messenger.calling.enabled');
        $this->bots = config('messenger.bots.enabled');
        $this->verifyPrivateThreadFriendship = config('messenger.thread_verifications.private_thread_friendship');
        $this->verifyGroupThreadFriendship = config('messenger.thread_verifications.group_thread_friendship');
        $this->systemMessages = config('messenger.system_messages.enabled');
        $this->providerAvatars = config('messenger.files.avatars.providers');
        $this->threadAvatars = config('messenger.files.avatars.threads');
        $this->botAvatars = config('messenger.files.avatars.bots');
        $this->avatarSizeLimit = config('messenger.files.avatars.size_limit');
        $this->avatarMimeTypes = config('messenger.files.avatars.mime_types');
        $this->messageImageUpload = config('messenger.files.message_images.upload');
        $this->messageImageSizeLimit = config('messenger.files.message_images.size_limit');
        $this->messageImageMimeTypes = config('messenger.files.message_images.mime_types');
        $this->messageAudioUpload = config('messenger.files.message_audio.upload');
        $this->messageAudioSizeLimit = config('messenger.files.message_audio.size_limit');
        $this->messageAudioMimeTypes = config('messenger.files.message_audio.mime_types');
        $this->messageDocumentUpload = config('messenger.files.message_documents.upload');
        $this->messageDocumentSizeLimit = config('messenger.files.message_documents.size_limit');
        $this->messageDocumentMimeTypes = config('messenger.files.message_documents.mime_types');
        $this->searchPageCount = config('messenger.collections.search.page_count');
        $this->threadsIndexCount = config('messenger.collections.threads.index_count');
        $this->threadsPageCount = config('messenger.collections.threads.page_count');
        $this->participantsIndexCount = config('messenger.collections.participants.index_count');
        $this->participantsPageCount = config('messenger.collections.participants.page_count');
        $this->messagesIndexCount = config('messenger.collections.messages.index_count');
        $this->messagesPageCount = config('messenger.collections.messages.page_count');
        $this->callsIndexCount = config('messenger.collections.calls.index_count');
        $this->callsPageCount = config('messenger.collections.calls.page_count');
        $this->apiRateLimit = config('messenger.rate_limits.api');
        $this->searchRateLimit = config('messenger.rate_limits.search');
        $this->messageRateLimit = config('messenger.rate_limits.message');
        $this->attachmentRateLimit = config('messenger.rate_limits.attachment');
        $this->subscribers = [
            'bots' => config('messenger.bots.subscriber'),
            'calls' => config('messenger.calling.subscriber'),
            'system_messages' => config('messenger.system_messages.subscriber'),
        ];

        $this->setNewFeatureConfigs();
    }

    /**
     * Set configs that were added before a major release, as we
     * need to check if the nested config value exists.
     *
     * @TODO Remove in v2.
     */
    private function setNewFeatureConfigs(): void
    {
        $this->messageVideoUpload = config('messenger.files.message_videos.upload') ?? false;
        $this->messageVideoSizeLimit = config('messenger.files.message_videos.size_limit') ?? 0;
        $this->messageVideoMimeTypes = config('messenger.files.message_videos.mime_types') ?? 'NaN';
    }
}
