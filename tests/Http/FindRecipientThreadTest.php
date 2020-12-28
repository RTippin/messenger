<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Definitions;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class FindRecipientThreadTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupInitialThreads();
    }

    private function setupInitialThreads(): void
    {
        $private = Thread::create(Definitions::DefaultThread);

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => 1,
                'owner_type' => self::UserModelType,
            ]));

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => 2,
                'owner_type' => self::UserModelType,
            ]));
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
                'thread_id' => Thread::private()->first()->id,
                'recipient' => [
                    'provider_id' => 2,
                    'name' => UserModel::find(2)->name(),
                ],
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_user_without_existing_thread_id()
    {
        $otherUser = UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);

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
                    'name' => $otherUser->name(),
                ],
            ]);
    }
}
