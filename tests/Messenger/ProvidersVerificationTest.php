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
    public function empty_providers_returns_empty_collection()
    {
        $emptyResult = $this->verify->formatValidProviders([]);

        $this->assertInstanceOf(Collection::class, $emptyResult);

        $this->assertCount(0, $emptyResult->toArray());
    }

    /** @test */
    public function provider_without_string_alias_key_returns_empty_collection()
    {
        $emptyResult = $this->verify->formatValidProviders([$this->getBaseProvidersConfig()['user']]);

        $this->assertInstanceOf(Collection::class, $emptyResult);

        $this->assertCount(0, $emptyResult->toArray());
    }

    /** @test */
    public function model_not_implementing_provider_ignored()
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
    public function user_passes_and_returns_formatted()
    {
        $result = $this->verify->formatValidProviders($this->defaultUserConfig());

        $expected = [
            'user' => [
                'model' => UserModel::class,
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

        $this->assertSame($expected, $result->toArray());
    }

    /** @test */
    public function company_passes_and_returns_formatted()
    {
        $result = $this->verify->formatValidProviders($this->defaultCompanyConfig());

        $expected = [
            'company' => [
                'model' => CompanyModel::class,
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

        $this->assertSame($expected, $result->toArray());
    }

    /** @test */
    public function user_and_company_pass_and_returns_formatted()
    {
        $providers['user'] = $this->defaultUserConfig()['user'];

        $providers['company'] = $this->defaultCompanyConfig()['company'];

        $result = $this->verify->formatValidProviders($providers);

        $expected = [
            'user' => [
                'model' => UserModel::class,
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

        $this->assertSame($expected, $result->toArray());
    }

    /** @test */
    public function user_and_company_formats_interactions()
    {
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

        $this->assertSame($user, $result->toArray()['user']['provider_interactions']);

        $this->assertSame($company, $result->toArray()['company']['provider_interactions']);
    }

    /** @test */
    public function searchable_and_friendable_override_interactions()
    {
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
