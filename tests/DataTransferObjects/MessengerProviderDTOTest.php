<?php

namespace RTippin\Messenger\Tests\DataTransferObjects;

use RTippin\Messenger\DataTransferObjects\MessengerProviderDTO;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;
use RTippin\Messenger\Tests\MessengerTestCase;

class MessengerProviderDTOTest extends MessengerTestCase
{
    /** @test */
    public function it_sets_providers_properties()
    {
        $provider = new MessengerProviderDTO(UserModel::class);

        $this->assertSame(UserModel::class, $provider->class);
        $this->assertSame('user', $provider->alias);
        $this->assertSame((new UserModel)->getMorphClass(), $provider->morphClass);
        $this->assertTrue($provider->searchable);
        $this->assertTrue($provider->friendable);
        $this->assertTrue($provider->hasDevices);
        $this->assertSame('/path/to/user.png', $provider->defaultAvatarPath);
        $this->assertSame([], $provider->cantMessageFirst);
        $this->assertSame([], $provider->cantSearch);
        $this->assertSame([], $provider->cantFriend);
    }

    /** @test */
    public function it_overwrites_provider_properties()
    {
        CompanyModel::$alias = 'test_company';
        CompanyModel::$searchable = false;
        CompanyModel::$friendable = false;
        CompanyModel::$devices = false;
        CompanyModel::$cantMessage = [UserModel::class];
        CompanyModel::$cantSearch = [UserModel::class];
        CompanyModel::$cantFriend = [UserModel::class];

        $provider = new MessengerProviderDTO(CompanyModel::class);

        $this->assertSame(CompanyModel::class, $provider->class);
        $this->assertSame('test_company', $provider->alias);
        $this->assertSame((new CompanyModel)->getMorphClass(), $provider->morphClass);
        $this->assertFalse($provider->searchable);
        $this->assertFalse($provider->friendable);
        $this->assertFalse($provider->hasDevices);
        $this->assertSame('/path/to/company.png', $provider->defaultAvatarPath);
        $this->assertSame([UserModel::class], $provider->cantMessageFirst);
        $this->assertSame([UserModel::class], $provider->cantSearch);
        $this->assertSame([UserModel::class], $provider->cantFriend);
    }

    /** @test */
    public function it_uses_snake_class_name_for_alias_when_not_specified()
    {
        CompanyModel::$alias = null;

        $provider = new MessengerProviderDTO(CompanyModel::class);

        $this->assertSame('company_model', $provider->alias);
    }

    /** @test */
    public function it_returns_array()
    {
        $provider = new MessengerProviderDTO(UserModel::class);
        $expects = [
            'alias' => 'user',
            'morph_class' => (new UserModel)->getMorphClass(),
            'searchable' => true,
            'friendable' => true,
            'devices' => true,
            'default_avatar' => '/path/to/user.png',
            'cant_message_first' => [],
            'cant_search' => [],
            'cant_friend' => [],
        ];

        $this->assertSame($expects, $provider->toArray());
    }
}
