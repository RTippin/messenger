<?php

namespace RTippin\Messenger\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;

class FeatureTestCase extends MessengerTestCase
{
    use HelperTrait;

    /**
     * @var MessengerProvider|UserModel|Authenticatable
     */
    protected UserModel $tippin;

    /**
     * @var MessengerProvider|UserModel|Authenticatable
     */
    protected UserModel $doe;

    /**
     * @var MessengerProvider|CompanyModel|Authenticatable
     */
    protected CompanyModel $developers;

    /**
     * Setup feature test requirements.
     */
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
    }

    /**
     * Tear it down!
     */
    protected function tearDown(): void
    {
        Cache::flush();
        BaseMessengerAction::enableEvents();

        parent::tearDown();
    }

    /**
     * @param  Application  $app
     */
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

    /**
     * Store our two users.
     */
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

    /**
     * Store our one company.
     */
    private function storeBaseCompanies(): void
    {
        $this->developers = CompanyModel::factory()->create([
            'company_name' => 'Developers',
            'company_email' => 'developers@example.net',
        ]);
    }
}
