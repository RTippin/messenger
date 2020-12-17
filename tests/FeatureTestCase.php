<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Foundation\Auth\User;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Traits\Messageable;
use RTippin\Messenger\Traits\Search;

class FeatureTestCase extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations([
            '--database' => 'testbench',
        ]);

        $this->artisan('migrate', [
            '--database' => 'testbench',
        ])->run();

        $this->storeBaseUsers();
    }

    protected function getPackageProviders($app): array
    {
        return [
            MessengerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $config = $app->get('config');

        $config->set('database.default', 'testbench');

        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $config->set('messenger.providers', [
            'user' => [
                'model' => UserModel::class,
                'searchable' => true,
                'friendable' => true,
                'devices' => false,
                'default_avatar' => public_path('vendor/messenger/images/users.png'),
                'provider_interactions' => [
                    'can_message' => true,
                    'can_search' => true,
                    'can_friend' => true,
                ],
            ],
        ]);
    }

    private function storeBaseUsers()
    {
        UserModel::create([
            'name' => 'Richard Tippin',
            'email' => 'richard.tippin@gmail.com',
            'password' => 'secret',
        ]);

        UserModel::create([
            'name' => 'John Doe',
            'email' => 'doe@example.net',
            'password' => 'secret',
        ]);
    }
}

class UserModel extends User implements MessengerProvider, Searchable
{
    use Messageable;
    use Search;

    protected $table = 'users';

    protected $guarded = [];
}

class OtherModel extends User
{
    //random model that is not a valid provider for our package

}