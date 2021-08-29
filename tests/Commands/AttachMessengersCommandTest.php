<?php

namespace RTippin\Messenger\Tests\Commands;

use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;

class AttachMessengersCommandTest extends FeatureTestCase
{
    /** @test */
    public function it_does_nothing_if_not_confirmed()
    {
        $this->artisan('messenger:attach:messengers')
            ->expectsConfirmation('Really attach messenger models?', 'no')
            ->doesntExpectOutput('Finished attaching.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_stops_if_invalid_provider_supplied()
    {
        $this->artisan('messenger:attach:messengers', [
            '--provider' => 'App\Models\Invalid',
        ])
            ->expectsConfirmation('Really attach messenger models?', 'yes')
            ->expectsOutput('App\Models\Invalid is not a valid messenger provider.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_loops_through_existing_providers_without_creating_new_messengers()
    {
        UserModel::create([
            'name' => 'Name',
            'email' => 'user@example.org',
            'password' => 'password',
        ]);
        CompanyModel::create([
            'company_name' => 'Name',
            'company_email' => 'company@example.org',
            'password' => 'password',
        ]);

        $this->assertDatabaseCount('messengers', 3);

        $this->artisan('messenger:attach:messengers')
            ->expectsConfirmation('Really attach messenger models?', 'yes')
            ->expectsOutput('Attaching messenger\'s to RTippin\Messenger\Tests\Fixtures\UserModel.')
            ->expectsOutput('Completed RTippin\Messenger\Tests\Fixtures\UserModel.')
            ->expectsOutput('Attaching messenger\'s to RTippin\Messenger\Tests\Fixtures\CompanyModel.')
            ->expectsOutput('Completed RTippin\Messenger\Tests\Fixtures\CompanyModel.')
            ->expectsOutput('Finished attaching.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('messengers', 5);
    }

    /** @test */
    public function it_loops_through_existing_providers_creating_new_messengers_ignoring_existing()
    {
        UserModel::create([
            'name' => 'Name',
            'email' => 'user@example.org',
            'password' => 'password',
        ]);
        CompanyModel::create([
            'company_name' => 'Name',
            'company_email' => 'company@example.org',
            'password' => 'password',
        ]);

        $this->assertDatabaseCount('messengers', 3);

        $this->artisan('messenger:attach:messengers', [
            '--force' => true,
        ])
            ->expectsConfirmation('Really attach messenger models?', 'yes')
            ->expectsOutput('Attaching messenger\'s to RTippin\Messenger\Tests\Fixtures\UserModel.')
            ->expectsOutput('Completed RTippin\Messenger\Tests\Fixtures\UserModel.')
            ->expectsOutput('Attaching messenger\'s to RTippin\Messenger\Tests\Fixtures\CompanyModel.')
            ->expectsOutput('Completed RTippin\Messenger\Tests\Fixtures\CompanyModel.')
            ->expectsOutput('Finished attaching.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('messengers', 8);
    }

    /** @test */
    public function it_uses_given_provider_without_creating_new_messengers()
    {
        UserModel::create([
            'name' => 'Name',
            'email' => 'user@example.org',
            'password' => 'password',
        ]);

        $this->assertDatabaseCount('messengers', 3);

        $this->artisan('messenger:attach:messengers', [
            '--provider' => 'RTippin\Messenger\Tests\Fixtures\UserModel',
        ])
            ->expectsConfirmation('Really attach messenger models?', 'yes')
            ->expectsOutput('Attaching messenger\'s to RTippin\Messenger\Tests\Fixtures\UserModel.')
            ->expectsOutput('Completed RTippin\Messenger\Tests\Fixtures\UserModel.')
            ->doesntExpectOutput('Attaching messenger\'s to RTippin\Messenger\Tests\Fixtures\CompanyModel.')
            ->doesntExpectOutput('Completed RTippin\Messenger\Tests\Fixtures\CompanyModel.')
            ->expectsOutput('Finished attaching.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('messengers', 4);
    }

    /** @test */
    public function it_uses_given_provider_creating_new_messengers_ignoring_existing()
    {
        UserModel::create([
            'name' => 'Name',
            'email' => 'user@example.org',
            'password' => 'password',
        ]);

        $this->assertDatabaseCount('messengers', 3);

        $this->artisan('messenger:attach:messengers', [
            '--provider' => 'RTippin\Messenger\Tests\Fixtures\UserModel',
            '--force' => true,
        ])
            ->expectsConfirmation('Really attach messenger models?', 'yes')
            ->expectsOutput('Attaching messenger\'s to RTippin\Messenger\Tests\Fixtures\UserModel.')
            ->expectsOutput('Completed RTippin\Messenger\Tests\Fixtures\UserModel.')
            ->doesntExpectOutput('Attaching messenger\'s to RTippin\Messenger\Tests\Fixtures\CompanyModel.')
            ->doesntExpectOutput('Completed RTippin\Messenger\Tests\Fixtures\CompanyModel.')
            ->expectsOutput('Finished attaching.')
            ->assertExitCode(0);

        $this->assertDatabaseCount('messengers', 6);
    }
}
