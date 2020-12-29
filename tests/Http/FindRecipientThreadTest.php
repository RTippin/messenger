<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\CompanyModel;
use RTippin\Messenger\Tests\stubs\UserModel;

class FindRecipientThreadTest extends FeatureTestCase
{
    private Thread $private;

    private Thread $privateWithCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupInitialThreads();
    }

    private function setupInitialThreads(): void
    {
        $this->private = $this->makePrivateThread(
            UserModel::find(1),
            UserModel::find(2)
        );

        $this->privateWithCompany = $this->makePrivateThread(
            UserModel::find(1),
            CompanyModel::find(1)
        );
    }

    /** @test */
    public function private_thread_locator_returns_not_found_on_invalid_user()
    {
        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'user',
            'id' => 404,
        ]))
            ->assertNotFound();

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'unknown',
            'id' => 2,
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function private_thread_locator_returns_user_with_existing_thread_id()
    {
        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'user',
            'id' => 2,
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => $this->private->id,
                'recipient' => [
                    'provider_id' => 2,
                    'provider_alias' => 'user',
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_company_with_existing_thread_id()
    {
        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'company',
            'id' => 1,
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => $this->privateWithCompany->id,
                'recipient' => [
                    'provider_id' => 1,
                    'provider_alias' => 'company',
                    'name' => 'Developers',
                ],
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_user_without_existing_thread_id()
    {
        $otherUser = $this->generateJaneSmith();

        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'user',
            'id' => $otherUser->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => null,
                'recipient' => [
                    'provider_id' => $otherUser->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Jane Smith',
                ],
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_company_without_existing_thread_id()
    {
        $otherCompany = $this->generateSomeCompany();

        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'company',
            'id' => $otherCompany->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => null,
                'recipient' => [
                    'provider_id' => $otherCompany->getKey(),
                    'provider_alias' => 'company',
                    'name' => 'Some Company',
                ],
            ]);
    }
}
