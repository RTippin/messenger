<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Events\NewThreadEvent;
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
        $this->makePrivateThread(
            UserModel::find(1),
            UserModel::find(2)
        );
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
        Event::fake([
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
            'owner_type' => self::UserModelType,
            'pending' => true,
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'pending' => false,
        ]);

        Event::assertDispatched(function (NewThreadBroadcast $event) use ($otherUser) {
            $this->assertContains('private-user.'.$otherUser->getKey(), $event->broadcastOn());
            $this->assertTrue($event->broadcastWith()['thread']['pending']);

            return true;
        });

        Event::assertDispatched(function (NewThreadEvent $event) {
            $this->assertEquals(1, $event->provider->getKey());
            $this->assertEquals(1, $event->thread->type);

            return true;
        });
    }

    /** @test */
    public function creating_new_private_thread_with_friend_is_not_pending()
    {
        Event::fake([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $otherUser = $this->generateJaneSmith();

        $this->makeFriends(
            UserModel::find(1),
            $otherUser
        );

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
            'owner_type' => self::UserModelType,
            'pending' => false,
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'pending' => false,
        ]);

        Event::assertDispatched(function (NewThreadBroadcast $event) use ($otherUser) {
            $this->assertContains('private-user.'.$otherUser->getKey(), $event->broadcastOn());
            $this->assertFalse($event->broadcastWith()['thread']['pending']);

            return true;
        });

        Event::assertDispatched(function (NewThreadEvent $event) {
            $this->assertEquals(1, $event->provider->getKey());
            $this->assertEquals(1, $event->thread->type);

            return true;
        });
    }

    /** @test */
    public function creating_new_private_thread_with_non_friend_company_is_pending()
    {
        Event::fake([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'company',
            'recipient_id' => 1,
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 1,
                'type_verbose' => 'PRIVATE',
                'pending' => true,
                'group' => false,
                'unread' => true,
                'name' => 'Developers',
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
            'owner_id' => 1,
            'owner_type' => self::CompanyModelType,
            'pending' => true,
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'pending' => false,
        ]);

        Event::assertDispatched(function (NewThreadBroadcast $event) {
            $this->assertContains('private-company.1', $event->broadcastOn());
            $this->assertTrue($event->broadcastWith()['thread']['pending']);

            return true;
        });

        Event::assertDispatched(function (NewThreadEvent $event) {
            $this->assertEquals(1, $event->provider->getKey());
            $this->assertEquals(1, $event->thread->type);

            return true;
        });
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
