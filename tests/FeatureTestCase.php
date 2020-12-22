<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Contracts\Searchable;
use RTippin\Messenger\MessengerServiceProvider;
use RTippin\Messenger\Models\Messenger;
use RTippin\Messenger\Traits\Messageable;
use RTippin\Messenger\Traits\Search;

class FeatureTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations([
            '--database' => 'testbench',
        ]);

        $this->artisan('migrate', [
            '--database' => 'testbench',
        ])->run();

        Schema::table('users', function (Blueprint $table) {
            $table->string('picture')->nullable();
        });

        $this->storeBaseUsers();
    }

    protected function getPackageProviders($app): array
    {
        return [
            MessengerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $config = $app->get('config');

        $config->set('database.default', 'testbench');

        $config->set('database.connections.testbench', [
            'driver' => 'mysql',
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

        $config->set('messenger.site_name', 'Messenger-Testbench');
    }

    private function storeBaseUsers(): void
    {
        $userOne = UserModel::create([
            'name' => 'Richard Tippin',
            'email' => 'richard.tippin@gmail.com',
            'password' => 'secret',
        ]);

        Messenger::create([
            'owner_id' => $userOne->getKey(),
            'owner_type' => get_class($userOne),
        ]);

        $userTwo = UserModel::create([
            'name' => 'John Doe',
            'email' => 'doe@example.net',
            'password' => 'secret',
        ]);

        Messenger::create([
            'owner_id' => $userTwo->getKey(),
            'owner_type' => get_class($userTwo),
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
