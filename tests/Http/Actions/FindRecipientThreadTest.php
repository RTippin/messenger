<?php

namespace RTippin\Messenger\Tests\Http\Actions;

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
        $users = UserModel::all();

        $private = Thread::create(Definitions::DefaultThread);

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $users->first()->getKey(),
                'owner_type' => get_class($users->first()),
            ]));

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $users->last()->getKey(),
                'owner_type' => get_class($users->last()),
            ]));
    }

    /** @test */
    public function private_thread_locator_returns_not_found_on_invalid_user()
    {
        $this->actingAs(UserModel::first());

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
        $users = UserModel::all();

        $this->actingAs($users->first());

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'user',
            'id' => $users->last()->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => Thread::private()->first()->id,
                'recipient' => [
                    'provider_id' => $users->last()->getKey(),
                    'name' => $users->last()->name(),
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

        $this->actingAs(UserModel::first());

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
