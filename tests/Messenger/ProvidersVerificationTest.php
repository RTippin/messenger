<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Support\Collection;
use RTippin\Messenger\Support\ProvidersVerification;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\OtherModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;
use RTippin\Messenger\Tests\MessengerTestCase;

class ProvidersVerificationTest extends MessengerTestCase
{
    private ProvidersVerification $verify;

    protected function setUp(): void
    {
        parent::setUp();

        $this->verify = new ProvidersVerification;
    }

    /** @test */
    public function it_returns_empty_collection()
    {
        $emptyResult = $this->verify->formatValidProviders([]);

        $this->assertInstanceOf(Collection::class, $emptyResult);
        $this->assertCount(0, $emptyResult->toArray());
    }

    /** @test */
    public function it_returns_empty_collection_if_alias_not_found()
    {
        $emptyResult = $this->verify->formatValidProviders([$this->getBaseProvidersConfig()['user']]);

        $this->assertInstanceOf(Collection::class, $emptyResult);
        $this->assertCount(0, $emptyResult->toArray());
    }

    /** @test */
    public function it_ignores_model_not_implementing_provider_contract()
    {
        $result = $this->verify->formatValidProviders([
            'other' => [
                'model' => OtherModel::class,
                'searchable' => false,
                'friendable' => false,
                'devices' => false,
                'default_avatar' => '/path/to/some.png',
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
        ]);

        $this->assertCount(0, $result->toArray());
    }

    /** @test */
    public function it_formats_user()
    {
        $expected = [
            'user' => [
                'model' => UserModel::class,
                'morph_class' => (new UserModel)->getMorphClass(),
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => '/path/to/user.png',
                'provider_interactions' => [
                    'can_message' => ['user'],
                    'can_search' => ['user'],
                    'can_friend' => ['user'],
                ],
            ],
        ];

        $result = $this->verify->formatValidProviders($this->defaultUserConfig());

        $this->assertSame($expected, $result->toArray());
    }

    /** @test */
    public function it_formats_company()
    {
        $expected = [
            'company' => [
                'model' => CompanyModel::class,
                'morph_class' => (new CompanyModel)->getMorphClass(),
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => '/path/to/company.png',
                'provider_interactions' => [
                    'can_message' => ['company'],
                    'can_search' => ['company'],
                    'can_friend' => ['company'],
                ],
            ],
        ];

        $result = $this->verify->formatValidProviders($this->defaultCompanyConfig());

        $this->assertSame($expected, $result->toArray());
    }

    /** @test */
    public function it_formats_user_and_company()
    {
        $providers['user'] = $this->defaultUserConfig()['user'];
        $providers['company'] = $this->defaultCompanyConfig()['company'];
        $expected = [
            'user' => [
                'model' => UserModel::class,
                'morph_class' => (new UserModel)->getMorphClass(),
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => '/path/to/user.png',
                'provider_interactions' => [
                    'can_message' => ['user', 'company'],
                    'can_search' => ['user', 'company'],
                    'can_friend' => ['user', 'company'],
                ],
            ],
            'company' => [
                'model' => CompanyModel::class,
                'morph_class' => (new CompanyModel)->getMorphClass(),
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => '/path/to/company.png',
                'provider_interactions' => [
                    'can_message' => ['user', 'company'],
                    'can_search' => ['user', 'company'],
                    'can_friend' => ['user', 'company'],
                ],
            ],
        ];

        $result = $this->verify->formatValidProviders($providers);

        $this->assertSame($expected, $result->toArray());
    }

    /** @test */
    public function it_formats_user_and_company_interactions()
    {
        $user = [
            'can_message' => ['user'],
            'can_search' => ['user'],
            'can_friend' => ['company', 'user'],
        ];
        $company = [
            'can_message' => ['company'],
            'can_search' => ['user', 'company'],
            'can_friend' => ['user', 'company'],
        ];

        $result = $this->verify->formatValidProviders([
            'user' => [
                'model' => UserModel::class,
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => '/path/to/user.png',
                'provider_interactions' => [
                    'can_message' => false,
                    'can_search' => 'user',
                    'can_friend' => 'company',
                ],
            ],
            'company' => [
                'model' => CompanyModel::class,
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => '/path/to/company.png',
                'provider_interactions' => [
                    'can_message' => null,
                    'can_search' => 'user|company',
                    'can_friend' => 'company|user|other',
                ],
            ],
        ]);

        $this->assertSame($user, $result->toArray()['user']['provider_interactions']);
        $this->assertSame($company, $result->toArray()['company']['provider_interactions']);
    }

    /** @test */
    public function searchable_and_friendable_override_interactions()
    {
        $user = [
            'can_message' => ['user'],
            'can_search' => [],
            'can_friend' => [],
        ];
        $company = [
            'can_message' => ['company'],
            'can_search' => [],
            'can_friend' => [],
        ];

        $result = $this->verify->formatValidProviders([
            'user' => [
                'model' => UserModel::class,
                'searchable' => false,
                'friendable' => false,
                'devices' => true,
                'default_avatar' => '/path/to/user.png',
                'provider_interactions' => [
                    'can_message' => false,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
            'company' => [
                'model' => CompanyModel::class,
                'searchable' => false,
                'friendable' => false,
                'devices' => true,
                'default_avatar' => '/path/to/company.png',
                'provider_interactions' => [
                    'can_message' => false,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
        ]);

        $this->assertSame($user, $result->toArray()['user']['provider_interactions']);
        $this->assertSame($company, $result->toArray()['company']['provider_interactions']);
    }

    private function defaultUserConfig(): array
    {
        return [
            'user' => [
                'model' => UserModel::class,
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => '/path/to/user.png',
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
        ];
    }

    private function defaultCompanyConfig(): array
    {
        return [
            'company' => [
                'model' => CompanyModel::class,
                'searchable' => true,
                'friendable' => true,
                'devices' => true,
                'default_avatar' => '/path/to/company.png',
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
        ];
    }
}
