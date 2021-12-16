<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Support\Str;
use InvalidArgumentException;
use RTippin\Messenger\Brokers\BroadcastBroker;
use RTippin\Messenger\Brokers\FriendBroker;
use RTippin\Messenger\Brokers\NullBroadcastBroker;
use RTippin\Messenger\Brokers\NullFriendBroker;
use RTippin\Messenger\Brokers\NullVideoBroker;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Facades\Messenger as MessengerFacade;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
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
    public function it_throws_exception_if_provider_doesnt_implement_our_interface()
    {
        $invalid = OtherModel::class;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The given provider { $invalid } must implement the interface ".MessengerProvider::class);

        $this->messenger->registerProviders([$invalid]);
    }

    /** @test */
    public function it_sets_providers_including_bot()
    {
        // Providers already set from our master test case.
        $this->assertCount(3, $this->messenger->getRawProviders());
        $this->assertArrayHasKey(UserModel::class, $this->messenger->getRawProviders());
        $this->assertArrayHasKey(CompanyModel::class, $this->messenger->getRawProviders());
        $this->assertArrayHasKey(Bot::class, $this->messenger->getRawProviders());
    }

    /** @test */
    public function it_overwrites_providers_and_resets_bot()
    {
        $this->messenger->registerProviders([UserModel::class], true);

        $this->assertCount(2, $this->messenger->getRawProviders());
        $this->assertArrayHasKey(UserModel::class, $this->messenger->getRawProviders());
        $this->assertArrayHasKey(Bot::class, $this->messenger->getRawProviders());
    }

    /** @test */
    public function it_checks_objects_and_class_strings_for_valid_provider()
    {
        $providerUser = new UserModel;
        $providerOtherUser = new OtherModel;
        $providerCompany = new CompanyModel;

        $this->assertTrue($this->messenger->isValidMessengerProvider(UserModel::class));
        $this->assertTrue($this->messenger->isValidMessengerProvider(CompanyModel::class));
        $this->assertTrue($this->messenger->isValidMessengerProvider($providerUser));
        $this->assertTrue($this->messenger->isValidMessengerProvider($providerCompany));
        $this->assertFalse($this->messenger->isValidMessengerProvider($providerOtherUser));
        $this->assertFalse($this->messenger->isValidMessengerProvider(OtherModel::class));
        $this->assertFalse($this->messenger->isValidMessengerProvider());
    }

    /** @test */
    public function it_returns_valid_provider_alias_using_objects_and_class_strings()
    {
        $providerUser = new UserModel;
        $providerOtherUser = new OtherModel;
        $providerCompany = new CompanyModel;

        $this->assertSame('user', $this->messenger->findProviderAlias(UserModel::class));
        $this->assertSame('company', $this->messenger->findProviderAlias(CompanyModel::class));
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
    public function it_returns_valid_provider_alias_using_morph_alias_if_morph_maps_set()
    {
        if (! $this->useMorphMap) {
            $this->markTestSkipped('Morph maps are not in use.');
        }

        $this->assertSame('user', $this->messenger->findProviderAlias('users'));
        $this->assertSame('company', $this->messenger->findProviderAlias('companies'));
    }

    /** @test */
    public function it_returns_valid_provider_class_using_morph_alias_if_morph_maps_set()
    {
        if (! $this->useMorphMap) {
            $this->markTestSkipped('Morph maps are not in use.');
        }

        $this->assertSame(UserModel::class, $this->messenger->findAliasProvider('users'));
        $this->assertSame(CompanyModel::class, $this->messenger->findAliasProvider('companies'));
    }

    /** @test */
    public function it_allows_given_provider_objects_and_class_strings_to_be_searched()
    {
        $providerUser = new UserModel;
        $providerCompany = new CompanyModel;

        $this->assertTrue($this->messenger->isProviderSearchable(UserModel::class));
        $this->assertTrue($this->messenger->isProviderSearchable(CompanyModel::class));
        $this->assertTrue($this->messenger->isProviderSearchable($providerUser));
        $this->assertTrue($this->messenger->isProviderSearchable($providerCompany));
    }

    /** @test */
    public function it_denies_given_provider_objects_and_class_strings_to_be_searched()
    {
        UserModel::$searchable = false;
        CompanyModel::$searchable = false;
        $this->messenger->registerProviders([UserModel::class, CompanyModel::class]);
        $providerUser = new UserModel;
        $providerOtherUser = new OtherModel;
        $providerCompany = new CompanyModel;

        $this->assertFalse($this->messenger->isProviderSearchable(UserModel::class));
        $this->assertFalse($this->messenger->isProviderSearchable(CompanyModel::class));
        $this->assertFalse($this->messenger->isProviderSearchable($providerUser));
        $this->assertFalse($this->messenger->isProviderSearchable($providerCompany));
        $this->assertFalse($this->messenger->isProviderSearchable($providerOtherUser));
        $this->assertFalse($this->messenger->isProviderSearchable(OtherModel::class));
    }

    /** @test */
    public function it_allows_given_provider_objects_and_class_strings_to_be_friended()
    {
        $providerUser = new UserModel;
        $providerCompany = new CompanyModel;

        $this->assertTrue($this->messenger->isProviderFriendable(UserModel::class));
        $this->assertTrue($this->messenger->isProviderFriendable(CompanyModel::class));
        $this->assertTrue($this->messenger->isProviderFriendable($providerUser));
        $this->assertTrue($this->messenger->isProviderFriendable($providerCompany));
    }

    /** @test */
    public function it_denies_given_provider_objects_and_class_strings_to_be_friended()
    {
        UserModel::$friendable = false;
        CompanyModel::$friendable = false;
        $this->messenger->registerProviders([UserModel::class, CompanyModel::class]);
        $providerUser = new UserModel;
        $providerOtherUser = new OtherModel;
        $providerCompany = new CompanyModel;

        $this->assertFalse($this->messenger->isProviderFriendable(UserModel::class));
        $this->assertFalse($this->messenger->isProviderFriendable(CompanyModel::class));
        $this->assertFalse($this->messenger->isProviderFriendable($providerUser));
        $this->assertFalse($this->messenger->isProviderFriendable($providerCompany));
        $this->assertFalse($this->messenger->isProviderFriendable($providerOtherUser));
        $this->assertFalse($this->messenger->isProviderFriendable(OtherModel::class));
    }

    /** @test */
    public function it_returns_all_providers_except_bot_using_morph_map_class()
    {
        $expected = [
            (new UserModel)->getMorphClass(),
            (new CompanyModel)->getMorphClass(),
        ];

        $this->assertSame($expected, $this->messenger->getAllProviders());
    }

    /** @test */
    public function it_returns_all_providers_except_bot_using_full_class_name()
    {
        $expected = [
            UserModel::class,
            CompanyModel::class,
        ];

        $this->assertSame($expected, $this->messenger->getAllProviders(true));
    }

    /** @test */
    public function it_returns_provider_classes_that_can_be_searched_using_morph_map()
    {
        $expected = [
            (new UserModel)->getMorphClass(),
            (new CompanyModel)->getMorphClass(),
        ];

        $this->assertSame($expected, $this->messenger->getAllSearchableProviders());
    }

    /** @test */
    public function it_returns_provider_classes_that_can_be_searched_using_full_class_name()
    {
        $expected = [
            UserModel::class,
            CompanyModel::class,
        ];

        $this->assertSame($expected, $this->messenger->getAllSearchableProviders(true));
    }

    /** @test */
    public function it_doesnt_return_provider_classes_that_cant_be_searched()
    {
        CompanyModel::$searchable = false;
        $this->messenger->registerProviders([UserModel::class, CompanyModel::class]);
        $expected = [
            (new UserModel)->getMorphClass(),
        ];

        $this->assertSame($expected, $this->messenger->getAllSearchableProviders());
    }

    /** @test */
    public function it_returns_provider_classes_that_can_be_friended_using_morph_map()
    {
        $expected = [
            (new UserModel)->getMorphClass(),
            (new CompanyModel)->getMorphClass(),
        ];

        $this->assertSame($expected, $this->messenger->getAllFriendableProviders());
    }

    /** @test */
    public function it_returns_provider_classes_that_can_be_friended_using_full_class_name()
    {
        $expected = [
            UserModel::class,
            CompanyModel::class,
        ];

        $this->assertSame($expected, $this->messenger->getAllFriendableProviders(true));
    }

    /** @test */
    public function it_doesnt_return_provider_classes_that_cant_be_friended()
    {
        CompanyModel::$friendable = false;
        $this->messenger->registerProviders([UserModel::class, CompanyModel::class]);
        $expected = [
            (new UserModel)->getMorphClass(),
        ];

        $this->assertSame($expected, $this->messenger->getAllFriendableProviders());
    }

    /** @test */
    public function it_returns_provider_classes_that_have_devices_using_morph_map()
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
    public function it_returns_provider_classes_that_have_devices_using_full_class_name()
    {
        $expected = [
            UserModel::class,
            CompanyModel::class,
        ];

        $this->assertSame($expected, $this->messenger->getAllProvidersWithDevices(true));
    }

    /** @test */
    public function it_doesnt_return_provider_classes_that_dont_have_devices()
    {
        CompanyModel::$devices = false;
        $this->messenger->registerProviders([UserModel::class, CompanyModel::class]);
        $expected = [
            (new UserModel)->getMorphClass(),
        ];

        $this->assertSame($expected, $this->messenger->getAllProvidersWithDevices());
    }

    /** @test */
    public function it_returns_default_provider_avatar_path_using_provider_alias()
    {
        $this->assertSame('/path/to/user.png', $this->messenger->getProviderDefaultAvatarPath('user'));
        $this->assertSame('/path/to/company.png', $this->messenger->getProviderDefaultAvatarPath('company'));
        $this->assertNotNull($this->messenger->getProviderDefaultAvatarPath('bot'));
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
    public function it_sets_provider()
    {
        $friends = app(FriendDriver::class);
        $provider = UserModel::factory()->make([
            'id' => $this->useUUID ? Str::orderedUuid()->toString() : 1,
        ]);
        $this->messenger->setProvider($provider);
        $expected = [
            (new UserModel)->getMorphClass(),
            (new CompanyModel)->getMorphClass(),
        ];

        $this->assertSame($provider, $this->messenger->getProvider());
        $this->assertNotSame($provider, $this->messenger->getProvider(true));
        $this->assertNotSame($friends, app(FriendDriver::class));
        $this->assertSame('user', $this->messenger->getProviderAlias());
        $this->assertSame($provider->getKey(), $this->messenger->getProvider()->getKey());
        $this->assertSame(UserModel::class, get_class($this->messenger->getProvider()));
        $this->assertTrue(app()->bound(MessengerProvider::class));
        $this->assertSame($provider, app(MessengerProvider::class));
        $this->assertTrue($this->messenger->providerHasFriends());
        $this->assertTrue($this->messenger->providerHasDevices());
        $this->assertTrue($this->messenger->isProviderSet());
        $this->assertSame($expected, $this->messenger->getSearchableForCurrentProvider());
    }

    /** @test */
    public function it_sets_scoped_provider()
    {
        $friends = app(FriendDriver::class);
        $scoped = UserModel::factory()->make([
            'id' => $this->useUUID ? Str::orderedUuid()->toString() : 1,
        ]);
        $this->messenger->setScopedProvider($scoped);
        $expected = [
            (new UserModel)->getMorphClass(),
            (new CompanyModel)->getMorphClass(),
        ];

        $this->assertSame($scoped, $this->messenger->getProvider());
        $this->assertNotSame($scoped, $this->messenger->getProvider(true));
        $this->assertNotSame($friends, app(FriendDriver::class));
        $this->assertSame('user', $this->messenger->getProviderAlias());
        $this->assertSame($scoped->getKey(), $this->messenger->getProvider()->getKey());
        $this->assertSame(UserModel::class, get_class($this->messenger->getProvider()));
        $this->assertTrue(app()->bound(MessengerProvider::class));
        $this->assertSame($scoped, app(MessengerProvider::class));
        $this->assertTrue($this->messenger->providerHasFriends());
        $this->assertTrue($this->messenger->providerHasDevices());
        $this->assertTrue($this->messenger->isProviderSet());
        $this->assertTrue($this->messenger->isScopedProviderSet());
        $this->assertSame($expected, $this->messenger->getSearchableForCurrentProvider());
    }

    /** @test */
    public function it_returns_scoped_provider_instead_of_first_provider_when_set()
    {
        $provider = UserModel::factory()->make([
            'id' => $this->useUUID ? Str::orderedUuid()->toString() : 1,
        ]);
        $scoped = CompanyModel::factory()->make([
            'id' => $this->useUUID ? Str::orderedUuid()->toString() : 1,
        ]);
        $this->messenger->setProvider($provider)->setScopedProvider($scoped);

        $this->assertSame($scoped, $this->messenger->getProvider());
        $this->assertNotSame($provider, $this->messenger->getProvider());
    }

    /** @test */
    public function it_filters_searchable_for_current_provider()
    {
        UserModel::$cantSearch = [CompanyModel::class];
        $this->messenger->registerProviders([UserModel::class, CompanyModel::class]);
        $provider = UserModel::factory()->make([
            'id' => $this->useUUID ? Str::orderedUuid()->toString() : 1,
        ]);
        $this->messenger->setProvider($provider);
        $expected = [
            (new UserModel)->getMorphClass(),
        ];

        $this->assertSame($expected, $this->messenger->getSearchableForCurrentProvider());
    }

    /** @test */
    public function it_unsets_provider()
    {
        $provider = UserModel::factory()->make([
            'id' => $this->useUUID ? Str::orderedUuid()->toString() : 1,
        ]);

        $this->messenger->setProvider($provider);
        $friends = app(FriendDriver::class);

        $this->assertSame($provider, $this->messenger->getProvider());
        $this->assertSame($friends, app(FriendDriver::class));

        $this->messenger->unsetProvider();

        $this->assertNull($this->messenger->getProviderAlias());
        $this->assertFalse(app()->bound(MessengerProvider::class));
        $this->assertNotSame($friends, app(FriendDriver::class));
        $this->assertFalse($this->messenger->providerHasFriends());
        $this->assertFalse($this->messenger->providerHasDevices());
        $this->assertFalse($this->messenger->isProviderSet());
        $this->assertSame([], $this->messenger->getSearchableForCurrentProvider());
    }

    /** @test */
    public function it_unsets_scoped_provider()
    {
        $provider = UserModel::factory()->make([
            'id' => $this->useUUID ? Str::orderedUuid()->toString() : 1,
        ]);

        $this->messenger->setScopedProvider($provider);
        $friends = app(FriendDriver::class);

        $this->assertSame($friends, app(FriendDriver::class));
        $this->assertSame($provider, $this->messenger->getProvider());

        $this->messenger->unsetScopedProvider();

        $this->assertNotSame($friends, app(FriendDriver::class));
        $this->assertNull($this->messenger->getProviderAlias());
        $this->assertFalse(app()->bound(MessengerProvider::class));
        $this->assertFalse($this->messenger->providerHasFriends());
        $this->assertFalse($this->messenger->providerHasDevices());
        $this->assertFalse($this->messenger->isProviderSet());
        $this->assertFalse($this->messenger->isScopedProviderSet());
        $this->assertSame([], $this->messenger->getSearchableForCurrentProvider());
    }

    /** @test */
    public function it_unsets_scoped_provider_and_sets_previous_provider()
    {
        $provider = UserModel::factory()->make([
            'id' => $this->useUUID ? Str::orderedUuid()->toString() : 1,
        ]);
        $scoped = CompanyModel::factory()->make([
            'id' => $this->useUUID ? Str::orderedUuid()->toString() : 1,
        ]);
        $this->messenger->setProvider($provider)->setScopedProvider($scoped);
        $friends = app(FriendDriver::class);

        $this->assertSame($friends, app(FriendDriver::class));
        $this->assertSame($scoped, $this->messenger->getProvider());
        $this->assertNotSame($provider, $this->messenger->getProvider());

        $this->messenger->unsetScopedProvider();

        $this->assertTrue($this->messenger->isProviderSet());
        $this->assertFalse($this->messenger->isScopedProviderSet());
        $this->assertSame($provider, $this->messenger->getProvider());
        $this->assertNotSame($friends, app(FriendDriver::class));
    }

    /** @test */
    public function it_flushes_messenger()
    {
        $this->messenger->setProvider(UserModel::factory()->make())
            ->setScopedProvider(CompanyModel::factory()->make())
            ->setCalling(false)
            ->setBots(false)
            ->setSystemMessages(false);
        $friends = app(FriendDriver::class);
        Messenger::shouldUseAbsoluteRoutes(true);

        $this->assertSame($friends, app(FriendDriver::class));
        $this->assertTrue($this->messenger->isProviderSet());
        $this->assertTrue($this->messenger->isScopedProviderSet());
        $this->assertFalse($this->messenger->isCallingEnabled());
        $this->assertFalse($this->messenger->isBotsEnabled());
        $this->assertFalse($this->messenger->isSystemMessagesEnabled());
        $this->assertTrue(Messenger::shouldUseAbsoluteRoutes());

        $this->messenger->flush();

        $this->assertFalse($this->messenger->isProviderSet());
        $this->assertFalse($this->messenger->isScopedProviderSet());
        $this->assertTrue($this->messenger->isCallingEnabled());
        $this->assertTrue($this->messenger->isBotsEnabled());
        $this->assertTrue($this->messenger->isSystemMessagesEnabled());
        $this->assertNotSame($friends, app(FriendDriver::class));
        $this->assertFalse(Messenger::shouldUseAbsoluteRoutes());
    }

    /** @test */
    public function it_allows_set_provider_to_message_given_provider_first()
    {
        $providerUser = new UserModel;
        $providerCompany = new CompanyModel;
        $this->messenger->setProvider($providerUser);

        $this->assertTrue($this->messenger->canMessageProviderFirst($providerUser));
        $this->assertTrue($this->messenger->canMessageProviderFirst($providerCompany));
    }

    /** @test */
    public function it_denies_set_provider_to_message_given_provider_first()
    {
        UserModel::$cantMessage = [CompanyModel::class];
        $this->messenger->registerProviders([UserModel::class, CompanyModel::class]);
        $providerUser = new UserModel;
        $providerOtherUser = new OtherModel;
        $providerCompany = new CompanyModel;
        $this->messenger->setProvider($providerUser);

        $this->assertTrue($this->messenger->canMessageProviderFirst($providerUser));
        $this->assertFalse($this->messenger->canMessageProviderFirst(OtherModel::class));
        $this->assertFalse($this->messenger->canMessageProviderFirst(UserModel::class));
        $this->assertFalse($this->messenger->canMessageProviderFirst(CompanyModel::class));
        $this->assertFalse($this->messenger->canMessageProviderFirst($providerOtherUser));
        $this->assertFalse($this->messenger->canMessageProviderFirst($providerCompany));
        $this->assertFalse($this->messenger->canMessageProviderFirst());
    }

    /** @test */
    public function it_allows_given_provider_to_be_searched_by_set_provider()
    {
        $providerUser = new UserModel;
        $providerCompany = new CompanyModel;
        $this->messenger->setProvider($providerUser);

        $this->assertTrue($this->messenger->canSearchProvider($providerUser));
        $this->assertTrue($this->messenger->canSearchProvider($providerCompany));
        $this->assertFalse($this->messenger->canSearchProvider(CompanyModel::class));
        $this->assertFalse($this->messenger->canSearchProvider(UserModel::class));
    }

    /** @test */
    public function it_denies_given_provider_to_be_searched_by_set_provider()
    {
        UserModel::$cantSearch = [CompanyModel::class];
        $this->messenger->registerProviders([UserModel::class, CompanyModel::class]);
        $providerUser = new UserModel;
        $providerOtherUser = new OtherModel;
        $providerCompany = new CompanyModel;
        $this->messenger->setProvider($providerUser);

        $this->assertTrue($this->messenger->canSearchProvider($providerUser));
        $this->assertFalse($this->messenger->canSearchProvider(OtherModel::class));
        $this->assertFalse($this->messenger->canSearchProvider($providerOtherUser));
        $this->assertFalse($this->messenger->canSearchProvider($providerCompany));
        $this->assertFalse($this->messenger->canSearchProvider(UserModel::class));
        $this->assertFalse($this->messenger->canSearchProvider(CompanyModel::class));
        $this->assertFalse($this->messenger->canSearchProvider());
    }

    /** @test */
    public function it_allows_set_provider_to_initiate_friend_request_with_given_provider()
    {
        $providerUser = new UserModel;
        $providerCompany = new CompanyModel;
        $providerOtherUser = new OtherModel;
        $this->messenger->setProvider($providerUser);

        $this->assertTrue($this->messenger->canFriendProvider($providerUser));
        $this->assertTrue($this->messenger->canFriendProvider($providerCompany));
        $this->assertFalse($this->messenger->canFriendProvider(CompanyModel::class));
        $this->assertFalse($this->messenger->canFriendProvider(UserModel::class));
        $this->assertFalse($this->messenger->canFriendProvider($providerOtherUser));
    }

    /** @test */
    public function it_denies_set_provider_to_initiate_friend_request_with_given_provider()
    {
        UserModel::$cantFriend = [CompanyModel::class];
        $this->messenger->registerProviders([UserModel::class, CompanyModel::class]);
        $providerUser = new UserModel;
        $providerOtherUser = new OtherModel;
        $providerCompany = new CompanyModel;
        $this->messenger->setProvider($providerUser);

        $this->assertTrue($this->messenger->canFriendProvider($providerUser));
        $this->assertFalse($this->messenger->canFriendProvider(OtherModel::class));
        $this->assertFalse($this->messenger->canFriendProvider($providerOtherUser));
        $this->assertFalse($this->messenger->canFriendProvider($providerCompany));
        $this->assertFalse($this->messenger->canFriendProvider(CompanyModel::class));
        $this->assertFalse($this->messenger->canFriendProvider(UserModel::class));
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
        $this->assertTrue($this->messenger->shouldVerifyPrivateThreadFriendship());
        $this->assertTrue($this->messenger->shouldVerifyGroupThreadFriendship());
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
        $this->assertTrue($this->messenger->isMessageVideoUploadEnabled());
        $this->assertTrue($this->messenger->isThreadAvatarEnabled());
        $this->assertTrue($this->messenger->isBotAvatarEnabled());
        $this->assertTrue($this->messenger->isProviderAvatarEnabled());
        $this->assertCount(2, $this->messenger->getAllProviders());
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
        $this->assertSame(15360, $this->messenger->getMessageVideoSizeLimit());
        $this->assertSame(5120, $this->messenger->getMessageImageSizeLimit());
        $this->assertSame(5120, $this->messenger->getAvatarSizeLimit());
        $this->assertFalse($this->messenger->isCallingTemporarilyDisabled());
        $this->assertSame('csv,doc,docx,json,pdf,ppt,pptx,rar,rtf,txt,xls,xlsx,xml,zip,7z', $this->messenger->getMessageDocumentMimeTypes());
        $this->assertSame('jpg,jpeg,png,bmp,gif,webp', $this->messenger->getMessageImageMimeTypes());
        $this->assertSame('jpg,jpeg,png,bmp,gif,webp', $this->messenger->getAvatarMimeTypes());
        $this->assertSame('aac,mp3,oga,ogg,wav,weba,webm', $this->messenger->getMessageAudioMimeTypes());
        $this->assertSame('avi,mp4,ogv,webm,3gp,3g2,wmv,mov', $this->messenger->getMessageVideoMimeTypes());
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
        $this->messenger->setVideoDriver(TestVideoBroker::class);

        $this->assertInstanceOf(NullBroadcastBroker::class, app(BroadcastDriver::class));
        $this->assertInstanceOf(TestVideoBroker::class, app(VideoDriver::class));
        $this->assertInstanceOf(FriendBroker::class, app(FriendDriver::class));

        $this->messenger->setBroadcastDriver(BroadcastBroker::class);
        $this->messenger->setVideoDriver(NullVideoBroker::class);
        $this->messenger->setFriendDriver(NullFriendBroker::class);

        $this->assertInstanceOf(BroadcastBroker::class, app(BroadcastDriver::class));
        $this->assertInstanceOf(NullVideoBroker::class, app(VideoDriver::class));
        $this->assertInstanceOf(NullFriendBroker::class, app(FriendDriver::class));
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
    public function it_throws_exception_if_invalid_friend_driver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given driver { RTippin\Messenger\Brokers\NullBroadcastBroker } must implement our interface RTippin\Messenger\Contracts\FriendDriver');
        $this->messenger->setFriendDriver(NullBroadcastBroker::class);
    }

    /** @test */
    public function it_can_set_configs()
    {
        // Override config values.
        $this->messenger->setPushNotifications(true);
        $this->messenger->setOnlineCacheLifetime(10);
        $this->messenger->setCalling(false);
        $this->messenger->setBots(false);
        $this->messenger->setVerifyPrivateThreadFriendship(false);
        $this->messenger->setVerifyGroupThreadFriendship(false);
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
        $this->messenger->setMessageVideoUpload(false);
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
        $this->messenger->setMessageVideoSizeLimit(5);
        $this->messenger->setAvatarSizeLimit(5);
        $this->messenger->setMessageSizeLimit(5);
        $this->messenger->setMessageDocumentMimeTypes('mov,mp3');
        $this->messenger->setMessageImageMimeTypes('jpeg,png');
        $this->messenger->setMessageAudioMimeTypes('mp3');
        $this->messenger->setMessageVideoMimeTypes('mov');
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

        // Now check values changed.
        $this->assertTrue($this->messenger->isPushNotificationsEnabled());
        $this->assertSame(10, $this->messenger->getOnlineCacheLifetime());
        $this->assertFalse($this->messenger->isCallingEnabled());
        $this->assertFalse($this->messenger->isBotsEnabled());
        $this->assertFalse($this->messenger->isSystemMessagesEnabled());
        $this->assertFalse($this->messenger->shouldVerifyPrivateThreadFriendship());
        $this->assertFalse($this->messenger->shouldVerifyGroupThreadFriendship());
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
        $this->assertFalse($this->messenger->isMessageVideoUploadEnabled());
        $this->assertFalse($this->messenger->isThreadAvatarEnabled());
        $this->assertFalse($this->messenger->isProviderAvatarEnabled());
        $this->assertFalse($this->messenger->isBotAvatarEnabled());
        $this->assertSame(5, $this->messenger->getApiRateLimit());
        $this->assertSame(5, $this->messenger->getSearchRateLimit());
        $this->assertSame(5, $this->messenger->getMessageRateLimit());
        $this->assertSame(5, $this->messenger->getAttachmentRateLimit());
        $this->assertFalse($this->messenger->isMessageEditsEnabled());
        $this->assertFalse($this->messenger->isMessageEditsViewEnabled());
        $this->assertFalse($this->messenger->isMessageEditsEnabled());
        $this->assertFalse($this->messenger->isMessageReactionsEnabled());
        $this->assertSame(5, $this->messenger->getMessageReactionsMax());
        $this->assertSame(5, $this->messenger->getMessageDocumentSizeLimit());
        $this->assertSame(5, $this->messenger->getMessageImageSizeLimit());
        $this->assertSame(5, $this->messenger->getMessageAudioSizeLimit());
        $this->assertSame(5, $this->messenger->getMessageVideoSizeLimit());
        $this->assertSame(5, $this->messenger->getAvatarSizeLimit());
        $this->assertSame('mov,mp3', $this->messenger->getMessageDocumentMimeTypes());
        $this->assertSame('jpeg,png', $this->messenger->getMessageImageMimeTypes());
        $this->assertSame('mp3', $this->messenger->getMessageAudioMimeTypes());
        $this->assertSame('mov', $this->messenger->getMessageVideoMimeTypes());
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

    /** @test */
    public function it_can_set_new_feature_configs_if_missing_from_main_config()
    {
        config([
            'messenger.files.message_videos' => null,
        ]);
        $this->messenger->flush();

        $this->assertFalse($this->messenger->isMessageVideoUploadEnabled());
        $this->assertSame(0, $this->messenger->getMessageVideoSizeLimit());
        $this->assertSame('NaN', $this->messenger->getMessageVideoMimeTypes());
    }
}

class TestVideoBroker implements VideoDriver
{
    public function create(Thread $thread, Call $call): bool
    {
        return true;
    }

    public function destroy(Call $call): bool
    {
        return true;
    }

    public function getRoomId(): ?string
    {
        return null;
    }

    public function getRoomPin(): ?string
    {
        return null;
    }

    public function getRoomSecret(): ?string
    {
        return null;
    }

    public function getExtraPayload(): ?string
    {
        return null;
    }
}
