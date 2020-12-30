<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class FindRecipientThreadTest extends FeatureTestCase
{
    private Thread $private;

    private Thread $privateWithCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->makePrivateThread(
            $this->userTippin(),
            $this->userDoe()
        );

        $this->privateWithCompany = $this->makePrivateThread(
            $this->userTippin(),
            $this->companyDevelopers()
        );
    }

    /** @test */
    public function private_thread_locator_returns_not_found_on_invalid_user()
    {
        $this->actingAs($this->userTippin());

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
        $doe = $this->userDoe();

        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'user',
            'id' => $doe->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => $this->private->id,
                'recipient' => [
                    'provider_id' => $doe->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_company_with_existing_thread_id()
    {
        $developers = $this->companyDevelopers();

        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'company',
            'id' => $developers->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => $this->privateWithCompany->id,
                'recipient' => [
                    'provider_id' => $developers->getKey(),
                    'provider_alias' => 'company',
                    'name' => 'Developers',
                ],
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_user_without_existing_thread_id()
    {
        $otherUser = $this->generateJaneSmith();

        $this->actingAs($this->userTippin());

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

        $this->actingAs($this->userTippin());

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
