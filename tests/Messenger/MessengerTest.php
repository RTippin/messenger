<?php

namespace RTippin\Messenger\Tests\Messenger;

use InvalidArgumentException;
use RTippin\Messenger\Brokers\BroadcastBroker;
use RTippin\Messenger\Brokers\JanusBroker;
use RTippin\Messenger\Brokers\NullBroadcastBroker;
use RTippin\Messenger\Brokers\NullVideoBroker;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Facades\Messenger as MessengerFacade;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Services\Janus\VideoRoomService;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\OtherModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;
use RTippin\Messenger\Tests\MessengerTestCase;

class MessengerTest extends MessengerTestCase
{
    private Messenger $messenger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messenger = app(Messenger::class);
    }

    /** @test */
    public function messenger_facade_same_instance_as_container()
    {
        $this->assertSame($this->messenger, MessengerFacade::getInstance());
    }

    /** @test */
    public function messenger_helper_same_instance_as_container()
    {
        $this->assertSame($this->messenger, messenger());
    }

    /** @test */
    public function messenger_alias_same_instance_as_container()
    {
        $this->assertSame($this->messenger, app('messenger'));
    }

    /** @test */
    public function it_checks_objects_and_class_strings_for_valid_provider()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $providerUser = new $user;
        $providerOtherUser = new OtherModel;
        $providerCompany = new $company;

        $this->assertTrue($this->messenger->isValidMessengerProvider($user));
        $this->assertTrue($this->messenger->isValidMessengerProvider($company));
        $this->assertTrue($this->messenger->isValidMessengerProvider($providerUser));
        $this->assertTrue($this->messenger->isValidMessengerProvider($providerCompany));
        $this->assertFalse($this->messenger->isValidMessengerProvider($providerOtherUser));
        $this->assertFalse($this->messenger->isValidMessengerProvider(OtherModel::class));
        $this->assertFalse($this->messenger->isValidMessengerProvider());
    }

    /** @test */
    public function it_returns_valid_provider_alias_using_objects_and_class_strings()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $providerUser = new $user;
        $providerOtherUser = new OtherModel;
        $providerCompany = new $company;

        $this->assertSame('user', $this->messenger->findProviderAlias($user));
        $this->assertSame('company', $this->messenger->findProviderAlias($company));
        $this->assertSame('user', $this->messenger->findProviderAlias($providerUser));
        $this->assertSame('company', $this->messenger->findProviderAlias($providerCompany));
        $this->assertNull($this->messenger->findProviderAlias($providerOtherUser));
        $this->assertNull($this->messenger->findProviderAlias(OtherModel::class));
        $this->assertNull($this->messenger->findProviderAlias());
    }

    /** @test */
    public function it_returns_valid_provider_class_using_alias()
    {
        $this->assertSame(UserModel::class, $this->messenger->findAliasProvider('user'));
        $this->assertSame(CompanyModel::class, $this->messenger->findAliasProvider('company'));
        $this->assertNull($this->messenger->findAliasProvider('undefined'));
    }

    /** @test */
    public function it_returns_valid_provider_class_using_morph_alias_if_morph_maps_set()
    {
        if ($this->useMorphMap) {
            $this->assertSame(UserModel::class, $this->messenger->findAliasProvider('users'));
            $this->assertSame(CompanyModel::class, $this->messenger->findAliasProvider('companies'));
        } else {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function it_allows_given_provider_objects_and_class_strings_to_be_searched()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $providerUser = new $user;
        $providerCompany = new $company;

        $this->assertTrue($this->messenger->isProviderSearchable($user));
        $this->assertTrue($this->messenger->isProviderSearchable($company));
        $this->assertTrue($this->messenger->isProviderSearchable($providerUser));
        $this->assertTrue($this->messenger->isProviderSearchable($providerCompany));
    }

    /** @test */
    public function it_denies_given_provider_objects_and_class_strings_to_be_searched()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $providers = $this->getBaseProvidersConfig();
        $providers['user']['searchable'] = false;
        $providers['company']['searchable'] = false;
        $this->messenger->setMessengerProviders($providers);
        $providerUser = new $user;
        $providerOtherUser = new OtherModel;
        $providerCompany = new $company;

        $this->assertFalse($this->messenger->isProviderSearchable($user));
        $this->assertFalse($this->messenger->isProviderSearchable($company));
        $this->assertFalse($this->messenger->isProviderSearchable($providerUser));
        $this->assertFalse($this->messenger->isProviderSearchable($providerCompany));
        $this->assertFalse($this->messenger->isProviderSearchable($providerOtherUser));
        $this->assertFalse($this->messenger->isProviderSearchable(OtherModel::class));
    }

    /** @test */
    public function it_allows_given_provider_objects_and_class_strings_to_be_friended()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $providerUser = new $user;
        $providerCompany = new $company;

        $this->assertTrue($this->messenger->isProviderFriendable($user));
        $this->assertTrue($this->messenger->isProviderFriendable($company));
        $this->assertTrue($this->messenger->isProviderFriendable($providerUser));
        $this->assertTrue($this->messenger->isProviderFriendable($providerCompany));
    }

    /** @test */
    public function it_denies_given_provider_objects_and_class_strings_to_be_friended()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $providers = $this->getBaseProvidersConfig();
        $providers['user']['friendable'] = false;
        $providers['company']['friendable'] = false;
        $this->messenger->setMessengerProviders($providers);
        $providerUser = new $user;
        $providerOtherUser = new OtherModel;
        $providerCompany = new $company;

        $this->assertFalse($this->messenger->isProviderFriendable($user));
        $this->assertFalse($this->messenger->isProviderFriendable($company));
        $this->assertFalse($this->messenger->isProviderFriendable($providerUser));
        $this->assertFalse($this->messenger->isProviderFriendable($providerCompany));
        $this->assertFalse($this->messenger->isProviderFriendable($providerOtherUser));
        $this->assertFalse($this->messenger->isProviderFriendable(OtherModel::class));
    }

    /** @test */
    public function it_returns_all_provider_classes()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $expected = [
            (new $user)->getMorphClass(),
            (new $company)->getMorphClass(),
            'bots',
        ];

        $this->assertSame($expected, $this->messenger->getAllMessengerProviders());
    }

    /** @test */
    public function it_returns_provider_classes_that_can_be_searched()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $expected = [
            (new $user)->getMorphClass(),
            (new $company)->getMorphClass(),
        ];

        $this->assertSame($expected, $this->messenger->getAllSearchableProviders());
    }

    /** @test */
    public function it_returns_provider_classes_that_can_be_friended()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $expected = [
            (new $user)->getMorphClass(),
            (new $company)->getMorphClass(),
        ];

        $this->assertSame($expected, $this->messenger->getAllFriendableProviders());
    }

    /** @test */
    public function it_returns_provider_classes_that_have_devices()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $expected = [
            (new $user)->getMorphClass(),
            (new $company)->getMorphClass(),
        ];

        $this->assertSame($expected, $this->messenger->getAllProvidersWithDevices());
    }

    /** @test */
    public function it_returns_default_provider_avatar_path_using_provider_alias()
    {
        $this->assertSame('/path/to/user.png', $this->messenger->getProviderDefaultAvatarPath('user'));
        $this->assertSame('/path/to/company.png', $this->messenger->getProviderDefaultAvatarPath('company'));
        $this->assertNull($this->messenger->getProviderDefaultAvatarPath('undefined'));
    }

    /** @test */
    public function it_resolves_ghost_user_when_requested()
    {
        $ghost = $this->messenger->getGhostProvider();

        $this->assertInstanceOf(GhostUser::class, $ghost);
        $this->assertSame('Ghost Profile', $ghost->getProviderName());
    }

    /** @test */
    public function it_resolves_ghost_user_once()
    {
        $ghost = $this->messenger->getGhostProvider();

        $this->assertSame($ghost, $this->messenger->getGhostProvider());
        $this->assertSame($ghost, messenger()->getGhostProvider());
        $this->assertSame($ghost, MessengerFacade::getGhostProvider());

        $this->messenger->reset();

        $this->assertNotSame($ghost, $this->messenger->getGhostProvider());
    }

    /** @test */
    public function it_resolves_ghost_bot_when_requested()
    {
        $ghost = $this->messenger->getGhostBot();

        $this->assertInstanceOf(GhostUser::class, $ghost);
        $this->assertSame('Bot', $ghost->getProviderName());
    }

    /** @test */
    public function it_resolves_ghost_bot_once()
    {
        $ghost = $this->messenger->getGhostBot();

        $this->assertSame($ghost, $this->messenger->getGhostBot());
        $this->assertSame($ghost, messenger()->getGhostBot());
        $this->assertSame($ghost, MessengerFacade::getGhostBot());

        $this->messenger->reset();

        $this->assertNotSame($ghost, $this->messenger->getGhostBot());
    }

    /** @test */
    public function it_resolves_ghost_participant_when_requested()
    {
        $participant = $this->messenger->getGhostParticipant('1234');

        $this->assertInstanceOf(Participant::class, $participant);
    }

    /** @test */
    public function it_resolves_ghost_participant_once_unless_thread_id_changes()
    {
        $participant = $this->messenger->getGhostParticipant('1234');

        $this->assertSame($participant, $this->messenger->getGhostParticipant('1234'));
        $this->assertSame($participant, messenger()->getGhostParticipant('1234'));
        $this->assertSame($participant, MessengerFacade::getGhostParticipant('1234'));

        $this->assertNotSame($participant, $this->messenger->getGhostParticipant('5678'));
    }

    /** @test */
    public function it_throws_exception_when_setting_invalid_provider()
    {
        $this->expectException(InvalidProviderException::class);
        $this->expectExceptionMessage('Messenger provider not set or compatible.');

        $this->messenger->setProvider(new OtherModel);
    }

    /** @test */
    public function it_sets_valid_provider()
    {
        $model = UserModel::class;
        $provider = new $model([
            'id' => 1,
            'name' => 'Richard Tippin',
            'email' => 'tippindev@gmail.com',
            'password' => 'secret',
        ]);
        $this->messenger->setProvider($provider);
        $user = UserModel::class;
        $company = CompanyModel::class;
        $expected = [
            (new $user)->getMorphClass(),
            (new $company)->getMorphClass(),
        ];

        $this->assertSame($provider, $this->messenger->getProvider());
        $this->assertNotSame($provider, $this->messenger->getProvider(true));
        $this->assertSame('user', $this->messenger->getProviderAlias());
        $this->assertSame(1, $this->messenger->getProvider()->getKey());
        $this->assertSame(UserModel::class, get_class($this->messenger->getProvider()));
        $this->assertTrue(app()->bound(MessengerProvider::class));
        $this->assertSame($provider, app(MessengerProvider::class));
        $this->assertTrue($this->messenger->providerHasFriends());
        $this->assertTrue($this->messenger->providerHasDevices());
        $this->assertTrue($this->messenger->isProviderSet());
        $this->assertSame($expected, $this->messenger->getFriendableForCurrentProvider());
        $this->assertSame($expected, $this->messenger->getSearchableForCurrentProvider());
    }

    /** @test */
    public function it_unsets_provider()
    {
        $model = UserModel::class;

        $provider = new $model([
            'id' => 1,
            'name' => 'Richard Tippin',
            'email' => 'tippindev@gmail.com',
            'password' => 'secret',
        ]);

        $this->messenger->setProvider($provider);

        $this->assertSame($provider, $this->messenger->getProvider());

        $this->messenger->unsetProvider();

        $this->assertNull($this->messenger->getProviderAlias());
        $this->assertFalse(app()->bound(MessengerProvider::class));
        $this->assertFalse($this->messenger->providerHasFriends());
        $this->assertFalse($this->messenger->providerHasDevices());
        $this->assertFalse($this->messenger->isProviderSet());
        $this->assertSame([], $this->messenger->getFriendableForCurrentProvider());
        $this->assertSame([], $this->messenger->getSearchableForCurrentProvider());
    }

    /** @test */
    public function it_allows_set_provider_to_message_given_provider_first()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $providerUser = new $user;
        $providerCompany = new $company;
        $this->messenger->setProvider($providerUser);

        $this->assertTrue($this->messenger->canMessageProviderFirst($providerUser));
        $this->assertTrue($this->messenger->canMessageProviderFirst($company));
        $this->assertTrue($this->messenger->canMessageProviderFirst($user));
        $this->assertTrue($this->messenger->canMessageProviderFirst($providerCompany));
    }

    /** @test */
    public function it_denies_set_provider_to_message_given_provider_first()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $providers = $this->getBaseProvidersConfig();
        $providers['user']['provider_interactions']['can_message'] = false;
        $this->messenger->setMessengerProviders($providers);
        $providerUser = new $user;
        $providerOtherUser = new OtherModel;
        $providerCompany = new $company;
        $this->messenger->setProvider($providerUser);

        $this->assertFalse($this->messenger->canMessageProviderFirst(OtherModel::class));
        $this->assertTrue($this->messenger->canMessageProviderFirst($providerUser));
        $this->assertFalse($this->messenger->canMessageProviderFirst($company));
        $this->assertFalse($this->messenger->canMessageProviderFirst($providerOtherUser));
        $this->assertFalse($this->messenger->canMessageProviderFirst($providerCompany));
        $this->assertFalse($this->messenger->canMessageProviderFirst());
    }

    /** @test */
    public function it_allows_given_provider_to_be_searched_by_set_provider()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $providerUser = new $user;
        $providerCompany = new $company;
        $this->messenger->setProvider($providerUser);

        $this->assertTrue($this->messenger->canSearchProvider($providerUser));
        $this->assertTrue($this->messenger->canSearchProvider($company));
        $this->assertTrue($this->messenger->canSearchProvider($user));
        $this->assertTrue($this->messenger->canSearchProvider($providerCompany));
    }

    /** @test */
    public function it_denies_given_provider_to_be_searched_by_set_provider()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $providers = $this->getBaseProvidersConfig();
        $providers['user']['provider_interactions']['can_search'] = false;
        $this->messenger->setMessengerProviders($providers);
        $providerOtherUser = new OtherModel;
        $providerCompany = new $company;
        $this->messenger->setProvider(new $user);

        $this->assertFalse($this->messenger->canSearchProvider(OtherModel::class));
        $this->assertFalse($this->messenger->canSearchProvider($providerOtherUser));
        $this->assertFalse($this->messenger->canSearchProvider($providerCompany));
        $this->assertFalse($this->messenger->canSearchProvider($company));
        $this->assertFalse($this->messenger->canSearchProvider());
    }

    /** @test */
    public function it_allows_set_provider_to_initiate_friend_request_with_given_provider()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $providerUser = new $user;
        $providerCompany = new $company;
        $this->messenger->setProvider($providerUser);

        $this->assertTrue($this->messenger->canFriendProvider($providerUser));
        $this->assertTrue($this->messenger->canFriendProvider($company));
        $this->assertTrue($this->messenger->canFriendProvider($user));
        $this->assertTrue($this->messenger->canFriendProvider($providerCompany));
    }

    /** @test */
    public function it_denies_set_provider_to_initiate_friend_request_with_given_provider()
    {
        $user = UserModel::class;
        $company = CompanyModel::class;
        $providers = $this->getBaseProvidersConfig();
        $providers['user']['provider_interactions']['can_friend'] = false;
        $this->messenger->setMessengerProviders($providers);
        $providerOtherUser = new OtherModel;
        $providerCompany = new $company;
        $this->messenger->setProvider(new $user);

        $this->assertFalse($this->messenger->canFriendProvider(OtherModel::class));
        $this->assertFalse($this->messenger->canFriendProvider($providerOtherUser));
        $this->assertFalse($this->messenger->canFriendProvider($providerCompany));
        $this->assertFalse($this->messenger->canFriendProvider($company));
        $this->assertFalse($this->messenger->canFriendProvider());
    }

    /** @test */
    public function it_can_get_system_features()
    {
        $expected = [
            'bots' => true,
            'calling' => true,
            'invitations' => true,
            'invitations_max' => 3,
            'knocks' => true,
            'audio_messages' => true,
            'document_messages' => true,
            'image_messages' => true,
            'message_edits' => true,
            'message_edits_view' => true,
            'message_reactions' => true,
            'message_reactions_max' => 10,
            'provider_avatars' => true,
            'thread_avatars' => true,
            'bot_avatars' => true,
        ];

        $this->assertSame($expected, $this->messenger->getSystemFeatures());
    }

    /** @test */
    public function it_can_get_configs()
    {
        $this->assertSame('/api/messenger', $this->messenger->getApiEndpoint());
        $this->assertTrue($this->messenger->isChannelRoutesEnabled());
        $this->assertFalse($this->messenger->isProviderSet());
        $this->assertNull($this->messenger->getProvider());
        $this->assertNull($this->messenger->getProvider(true));
        $this->assertFalse($this->messenger->isPushNotificationsEnabled());
        $this->assertSame('messenger', $this->messenger->getThreadStorage('disk'));
        $this->assertSame('threads', $this->messenger->getThreadStorage('directory'));
        $this->assertSame('public', $this->messenger->getAvatarStorage('disk'));
        $this->assertSame('images', $this->messenger->getAvatarStorage('directory'));
        $this->assertSame(4, $this->messenger->getOnlineCacheLifetime());
        $this->assertTrue($this->messenger->isCallingEnabled());
        $this->assertTrue($this->messenger->isBotsEnabled());
        $this->assertTrue($this->messenger->isSystemMessagesEnabled());
        $this->assertSame(5, $this->messenger->getKnockTimeout());
        $this->assertSame(5000, $this->messenger->getMessageSizeLimit());
        $this->assertTrue($this->messenger->isKnockKnockEnabled());
        $this->assertSame(25, $this->messenger->getSearchPageCount());
        $this->assertSame(25, $this->messenger->getThreadsPageCount());
        $this->assertSame(100, $this->messenger->getThreadsIndexCount());
        $this->assertSame(500, $this->messenger->getParticipantsIndexCount());
        $this->assertSame(50, $this->messenger->getParticipantsPageCount());
        $this->assertSame(50, $this->messenger->getMessagesPageCount());
        $this->assertSame(50, $this->messenger->getMessagesIndexCount());
        $this->assertSame(25, $this->messenger->getCallsIndexCount());
        $this->assertSame(25, $this->messenger->getCallsPageCount());
        $this->assertTrue($this->messenger->isOnlineStatusEnabled());
        $this->assertTrue($this->messenger->isThreadInvitesEnabled());
        $this->assertSame(3, $this->messenger->getThreadMaxInvitesCount());
        $this->assertTrue($this->messenger->isMessageDocumentUploadEnabled());
        $this->assertTrue($this->messenger->isMessageImageUploadEnabled());
        $this->assertTrue($this->messenger->isMessageAudioUploadEnabled());
        $this->assertTrue($this->messenger->isThreadAvatarEnabled());
        $this->assertTrue($this->messenger->isBotAvatarEnabled());
        $this->assertTrue($this->messenger->isProviderAvatarEnabled());
        $this->assertCount(3, $this->messenger->getMessengerProviders());
        $this->assertSame(1000, $this->messenger->getApiRateLimit());
        $this->assertSame(45, $this->messenger->getSearchRateLimit());
        $this->assertSame(60, $this->messenger->getMessageRateLimit());
        $this->assertSame(15, $this->messenger->getAttachmentRateLimit());
        $this->assertTrue($this->messenger->isMessageEditsEnabled());
        $this->assertTrue($this->messenger->isMessageEditsViewEnabled());
        $this->assertTrue($this->messenger->isMessageReactionsEnabled());
        $this->assertSame(10, $this->messenger->getMessageReactionsMax());
        $this->assertSame(10240, $this->messenger->getMessageDocumentSizeLimit());
        $this->assertSame(10240, $this->messenger->getMessageAudioSizeLimit());
        $this->assertSame(5120, $this->messenger->getMessageImageSizeLimit());
        $this->assertSame(5120, $this->messenger->getAvatarSizeLimit());
        $this->assertFalse($this->messenger->isCallingTemporarilyDisabled());
        $this->assertSame('csv,doc,docx,json,pdf,ppt,pptx,rar,rtf,txt,xls,xlsx,xml,zip,7z', $this->messenger->getMessageDocumentMimeTypes());
        $this->assertSame('jpg,jpeg,png,bmp,gif,webp', $this->messenger->getMessageImageMimeTypes());
        $this->assertSame('jpg,jpeg,png,bmp,gif,webp', $this->messenger->getAvatarMimeTypes());
        $this->assertSame('aac,mp3,oga,ogg,wav,weba,webm', $this->messenger->getMessageAudioMimeTypes());
        $this->assertTrue($this->messenger->getBotSubscriber('enabled'));
        $this->assertTrue($this->messenger->getCallSubscriber('enabled'));
        $this->assertTrue($this->messenger->getSystemMessageSubscriber('enabled'));
        $this->assertTrue($this->messenger->getBotSubscriber('queued'));
        $this->assertTrue($this->messenger->getCallSubscriber('queued'));
        $this->assertTrue($this->messenger->getSystemMessageSubscriber('queued'));
        $this->assertSame('messenger-bots', $this->messenger->getBotSubscriber('channel'));
        $this->assertSame('messenger', $this->messenger->getCallSubscriber('channel'));
        $this->assertSame('messenger', $this->messenger->getSystemMessageSubscriber('channel'));
    }

    /** @test */
    public function it_can_temporarily_disable_or_enable_calling()
    {
        $this->messenger->disableCallsTemporarily(1);

        $this->assertTrue($this->messenger->isCallingTemporarilyDisabled());

        $this->messenger->removeTemporaryCallShutdown();

        $this->assertFalse($this->messenger->isCallingTemporarilyDisabled());
    }

    /** @test */
    public function it_can_set_drivers()
    {
        $this->messenger->setBroadcastDriver(NullBroadcastBroker::class);
        $this->messenger->setVideoDriver(NullVideoBroker::class);

        $this->assertInstanceOf(NullBroadcastBroker::class, app(BroadcastDriver::class));
        $this->assertInstanceOf(NullVideoBroker::class, app(VideoDriver::class));

        $this->mock(VideoRoomService::class);
        $this->messenger->setBroadcastDriver(BroadcastBroker::class);
        $this->messenger->setVideoDriver(JanusBroker::class);

        $this->assertInstanceOf(BroadcastBroker::class, app(BroadcastDriver::class));
        $this->assertInstanceOf(JanusBroker::class, app(VideoDriver::class));
    }

    /** @test */
    public function it_throws_exception_if_invalid_broadcast_driver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given driver { RTippin\Messenger\Brokers\NullVideoBroker } must implement our interface RTippin\Messenger\Contracts\BroadcastDriver');
        $this->messenger->setBroadcastDriver(NullVideoBroker::class);
    }

    /** @test */
    public function it_throws_exception_if_invalid_video_driver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given driver { RTippin\Messenger\Brokers\NullBroadcastBroker } must implement our interface RTippin\Messenger\Contracts\VideoDriver');
        $this->messenger->setVideoDriver(NullBroadcastBroker::class);
    }

    /** @test */
    public function it_can_set_configs()
    {
        // Override config values.
        $this->messenger->setPushNotifications(true);
        $this->messenger->setOnlineCacheLifetime(10);
        $this->messenger->setCalling(false);
        $this->messenger->setBots(false);
        $this->messenger->setSystemMessages(false);
        $this->messenger->setKnockTimeout(10);
        $this->messenger->setKnockKnock(false);
        $this->messenger->setSearchPageCount(5);
        $this->messenger->setThreadsPageCount(5);
        $this->messenger->setThreadsIndexCount(5);
        $this->messenger->setParticipantsIndexCount(5);
        $this->messenger->setParticipantsPageCount(5);
        $this->messenger->setMessagesPageCount(5);
        $this->messenger->setMessagesIndexCount(5);
        $this->messenger->setCallsIndexCount(5);
        $this->messenger->setCallsPageCount(5);
        $this->messenger->setOnlineStatus(false);
        $this->messenger->setThreadInvites(false);
        $this->messenger->setThreadInvitesMaxCount(5);
        $this->messenger->setMessageDocumentUpload(false);
        $this->messenger->setMessageImageUpload(false);
        $this->messenger->setMessageAudioUpload(false);
        $this->messenger->setThreadAvatars(false);
        $this->messenger->setBotAvatars(false);
        $this->messenger->setProviderAvatars(false);
        $this->messenger->setMessageEdits(false);
        $this->messenger->setMessageEditsView(false);
        $this->messenger->setMessageReactions(false);
        $this->messenger->setMessageReactionsMax(5);
        $this->messenger->setApiRateLimit(5);
        $this->messenger->setSearchRateLimit(5);
        $this->messenger->setMessageRateLimit(5);
        $this->messenger->setAttachmentRateLimit(5);
        $this->messenger->setMessageDocumentSizeLimit(5);
        $this->messenger->setMessageImageSizeLimit(5);
        $this->messenger->setMessageAudioSizeLimit(5);
        $this->messenger->setAvatarSizeLimit(5);
        $this->messenger->setMessageSizeLimit(5);
        $this->messenger->setMessageDocumentMimeTypes('mov,mp3');
        $this->messenger->setMessageImageMimeTypes('jpeg,png');
        $this->messenger->setMessageAudioMimeTypes('mp3');
        $this->messenger->setAvatarMimeTypes('jpeg,png');
        $this->messenger->setBotSubscriber('enabled', false);
        $this->messenger->setCallSubscriber('enabled', false);
        $this->messenger->setSystemMessageSubscriber('enabled', false);
        $this->messenger->setBotSubscriber('queued', false);
        $this->messenger->setCallSubscriber('queued', false);
        $this->messenger->setSystemMessageSubscriber('queued', false);
        $this->messenger->setBotSubscriber('channel', 'test');
        $this->messenger->setCallSubscriber('channel', 'test');
        $this->messenger->setSystemMessageSubscriber('channel', 'test');
        $this->messenger->setMessengerProviders([
            'user' => [
                'model' => UserModel::class,
                'searchable' => false,
                'friendable' => false,
                'devices' => false,
                'default_avatar' => '/path/to/user.png',
                'provider_interactions' => [
                    'can_message' => false,
                    'can_search' => false,
                    'can_friend' => false,
                ],
            ],
        ]);

        // Now check values changed.
        $this->assertTrue($this->messenger->isPushNotificationsEnabled());
        $this->assertSame(10, $this->messenger->getOnlineCacheLifetime());
        $this->assertFalse($this->messenger->isCallingEnabled());
        $this->assertFalse($this->messenger->isBotsEnabled());
        $this->assertFalse($this->messenger->isSystemMessagesEnabled());
        $this->assertSame(10, $this->messenger->getKnockTimeout());
        $this->assertFalse($this->messenger->isKnockKnockEnabled());
        $this->assertSame(5, $this->messenger->getSearchPageCount());
        $this->assertSame(5, $this->messenger->getThreadsPageCount());
        $this->assertSame(5, $this->messenger->getThreadsIndexCount());
        $this->assertSame(5, $this->messenger->getParticipantsIndexCount());
        $this->assertSame(5, $this->messenger->getParticipantsPageCount());
        $this->assertSame(5, $this->messenger->getMessagesPageCount());
        $this->assertSame(5, $this->messenger->getMessagesIndexCount());
        $this->assertSame(5, $this->messenger->getCallsIndexCount());
        $this->assertSame(5, $this->messenger->getCallsPageCount());
        $this->assertSame(5, $this->messenger->getMessageSizeLimit());
        $this->assertFalse($this->messenger->isOnlineStatusEnabled());
        $this->assertFalse($this->messenger->isThreadInvitesEnabled());
        $this->assertSame(5, $this->messenger->getThreadMaxInvitesCount());
        $this->assertFalse($this->messenger->isMessageDocumentUploadEnabled());
        $this->assertFalse($this->messenger->isMessageImageUploadEnabled());
        $this->assertFalse($this->messenger->isMessageAudioUploadEnabled());
        $this->assertFalse($this->messenger->isThreadAvatarEnabled());
        $this->assertFalse($this->messenger->isProviderAvatarEnabled());
        $this->assertFalse($this->messenger->isBotAvatarEnabled());
        $this->assertSame(5, $this->messenger->getApiRateLimit());
        $this->assertSame(5, $this->messenger->getSearchRateLimit());
        $this->assertSame(5, $this->messenger->getMessageRateLimit());
        $this->assertSame(5, $this->messenger->getAttachmentRateLimit());
        $this->assertCount(2, $this->messenger->getMessengerProviders());
        $this->assertFalse($this->messenger->isMessageEditsEnabled());
        $this->assertFalse($this->messenger->isMessageEditsViewEnabled());
        $this->assertFalse($this->messenger->isMessageEditsEnabled());
        $this->assertFalse($this->messenger->isMessageReactionsEnabled());
        $this->assertSame(5, $this->messenger->getMessageReactionsMax());
        $this->assertSame(5, $this->messenger->getMessageDocumentSizeLimit());
        $this->assertSame(5, $this->messenger->getMessageImageSizeLimit());
        $this->assertSame(5, $this->messenger->getMessageAudioSizeLimit());
        $this->assertSame(5, $this->messenger->getAvatarSizeLimit());
        $this->assertSame('mov,mp3', $this->messenger->getMessageDocumentMimeTypes());
        $this->assertSame('jpeg,png', $this->messenger->getMessageImageMimeTypes());
        $this->assertSame('mp3', $this->messenger->getMessageAudioMimeTypes());
        $this->assertSame('jpeg,png', $this->messenger->getAvatarMimeTypes());
        $this->assertFalse($this->messenger->getBotSubscriber('enabled'));
        $this->assertFalse($this->messenger->getCallSubscriber('enabled'));
        $this->assertFalse($this->messenger->getSystemMessageSubscriber('enabled'));
        $this->assertFalse($this->messenger->getBotSubscriber('queued'));
        $this->assertFalse($this->messenger->getCallSubscriber('queued'));
        $this->assertFalse($this->messenger->getSystemMessageSubscriber('queued'));
        $this->assertSame('test', $this->messenger->getBotSubscriber('channel'));
        $this->assertSame('test', $this->messenger->getCallSubscriber('channel'));
        $this->assertSame('test', $this->messenger->getSystemMessageSubscriber('channel'));
    }
}
