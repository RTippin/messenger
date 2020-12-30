<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Tests\FeatureTestCase;

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
            $this->userTippin(),
            $this->userDoe()
        );
    }

    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.privates.index'))
            ->assertUnauthorized();

        $this->postJson(route('api.messenger.privates.store'), [
            'recipient_id' => $this->userDoe()->getKey(),
            'recipient_alias' => 'user',
            'message' => 'Hello!',
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function creating_new_private_thread_with_non_friend_is_pending()
    {
        $tippin = $this->userTippin();

        $smith = $this->generateJaneSmith();

        Event::fake([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $smith->getKey(),
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
            'owner_id' => $smith->getKey(),
            'owner_type' => get_class($smith),
            'pending' => true,
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'pending' => false,
        ]);

        Event::assertDispatched(function (NewThreadBroadcast $event) use ($smith) {
            $this->assertContains('private-user.'.$smith->getKey(), $event->broadcastOn());
            $this->assertTrue($event->broadcastWith()['thread']['pending']);

            return true;
        });

        Event::assertDispatched(function (NewThreadEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals(1, $event->thread->type);

            return true;
        });
    }

    /** @test */
    public function creating_new_private_thread_with_friend_is_not_pending()
    {
        $tippin = $this->userTippin();

        $smith = $this->generateJaneSmith();

        Event::fake([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $this->makeFriends(
            $tippin,
            $smith
        );

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $smith->getKey(),
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
            'owner_id' => $smith->getKey(),
            'owner_type' => get_class($smith),
            'pending' => false,
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'pending' => false,
        ]);

        Event::assertDispatched(function (NewThreadBroadcast $event) use ($smith) {
            $this->assertContains('private-user.'.$smith->getKey(), $event->broadcastOn());
            $this->assertFalse($event->broadcastWith()['thread']['pending']);

            return true;
        });

        Event::assertDispatched(function (NewThreadEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals(1, $event->thread->type);

            return true;
        });
    }

    /** @test */
    public function creating_new_private_thread_with_non_friend_company_is_pending()
    {
        $tippin = $this->userTippin();

        $developers = $this->companyDevelopers();

        Event::fake([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'company',
            'recipient_id' => $developers->getKey(),
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
            'owner_id' => $developers->getKey(),
            'owner_type' => get_class($developers),
            'pending' => true,
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'pending' => false,
        ]);

        Event::assertDispatched(function (NewThreadBroadcast $event) use ($developers) {
            $this->assertContains('private-company.'.$developers->getKey(), $event->broadcastOn());
            $this->assertTrue($event->broadcastWith()['thread']['pending']);

            return true;
        });

        Event::assertDispatched(function (NewThreadEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
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

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $this->userDoe()->getKey(),
        ])
            ->assertForbidden();
    }
}
