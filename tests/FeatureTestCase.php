<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Brokers\NullVideoBroker;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;

class FeatureTestCase extends MessengerTestCase
{
    /**
     * @var MessengerProvider|UserModel|Authenticatable
     */
    protected $tippin;

    /**
     * @var MessengerProvider|UserModel|Authenticatable
     */
    protected $doe;

    /**
     * @var MessengerProvider|CompanyModel|Authenticatable
     */
    protected $developers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
        $this->artisan('migrate', [
            '--database' => 'testbench',
        ])->run();
        $this->storeBaseUsers();
        $this->storeBaseCompanies();
        BaseMessengerAction::disableEvents();
        Storage::fake('public');
        Storage::fake('messenger');
        Messenger::setVideoDriver(NullVideoBroker::class);
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        BaseMessengerAction::enableEvents();

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $config = $app->get('config');

        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    private function storeBaseUsers(): void
    {
        $this->tippin = UserModel::factory()->create([
            'name' => 'Richard Tippin',
            'email' => 'tippindev@gmail.com',
        ]);
        $this->doe = UserModel::factory()->create([
            'name' => 'John Doe',
            'email' => 'doe@example.net',
        ]);
    }

    private function storeBaseCompanies(): void
    {
        $this->developers = CompanyModel::factory()->create([
            'company_name' => 'Developers',
            'company_email' => 'developers@example.net',
        ]);
    }
}
