<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\UserModel;

class PrivateThreadsTest extends FeatureTestCase
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
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.privates.index'))
            ->assertUnauthorized();

        $this->postJson(route('api.messenger.privates.store'), [
            'recipient_id' => 2,
            'recipient_alias' => 'user',
            'message' => 'Hello!',
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function creating_new_private_thread_with_non_friend_is_pending()
    {
        $this->expectsEvents([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $otherUser = $this->generateJaneSmith();

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $otherUser->getKey(),
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 1,
                'type_verbose' => 'PRIVATE',
                'pending' => true,
                'group' => false,
                'unread' => true,
                'name' => 'Jane Smith',
                'options' => [
                    'awaiting_my_approval' => false,
                ],
                'resources' => [
                    'latest_message' => [
                        'body' => 'Hello World!',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $otherUser->getKey(),
            'pending' => true,
        ]);
    }

    /** @test */
    public function creating_new_private_thread_with_friend_is_not_pending()
    {
        $this->expectsEvents([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $otherUser = $this->generateJaneSmith();

        Friend::create([
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'party_id' => $otherUser->getKey(),
            'party_type' => self::UserModelType,
        ]);

        Friend::create([
            'owner_id' => $otherUser->getKey(),
            'owner_type' => self::UserModelType,
            'party_id' => 1,
            'party_type' => self::UserModelType,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $otherUser->getKey(),
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 1,
                'type_verbose' => 'PRIVATE',
                'pending' => false,
                'group' => false,
                'unread' => true,
                'name' => 'Jane Smith',
                'resources' => [
                    'latest_message' => [
                        'body' => 'Hello World!',
                    ],
                    'recipient' => [
                        'name' => 'Jane Smith',
                        'options' => [
                            'friend_status' => 1,
                            'friend_status_verbose' => 'FRIEND',
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $otherUser->getKey(),
            'pending' => false,
        ]);
    }

    /** @test */
    public function creating_new_private_forbidden_when_one_exist()
    {
        $this->doesntExpectEvents([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => 2,
        ])
            ->assertForbidden();
    }
}
