<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Tests\FeatureTestCase;

class PrivateThreadsTest extends FeatureTestCase
{
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

        $doe = $this->userDoe();

        Event::fake([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $doe->getKey(),
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 1,
                'type_verbose' => 'PRIVATE',
                'pending' => true,
                'group' => false,
                'unread' => true,
                'name' => 'John Doe',
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
            'owner_id' => $doe->getKey(),
            'owner_type' => get_class($doe),
            'pending' => true,
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'pending' => false,
        ]);

        Event::assertDispatched(function (NewThreadBroadcast $event) use ($doe) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
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

        $doe = $this->userDoe();

        Event::fake([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $this->makeFriends(
            $tippin,
            $doe
        );

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $doe->getKey(),
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 1,
                'type_verbose' => 'PRIVATE',
                'pending' => false,
                'group' => false,
                'unread' => true,
                'name' => 'John Doe',
                'resources' => [
                    'latest_message' => [
                        'body' => 'Hello World!',
                    ],
                    'recipient' => [
                        'name' => 'John Doe',
                        'options' => [
                            'friend_status' => 1,
                            'friend_status_verbose' => 'FRIEND',
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $doe->getKey(),
            'owner_type' => get_class($doe),
            'pending' => false,
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'pending' => false,
        ]);

        Event::assertDispatched(function (NewThreadBroadcast $event) use ($doe) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
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
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->makePrivateThread(
            $tippin,
            $doe
        );

        $this->doesntExpectEvents([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $doe->getKey(),
        ])
            ->assertForbidden();
    }
}
