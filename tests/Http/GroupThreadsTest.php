<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class GroupThreadsTest extends FeatureTestCase
{
    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.groups.index'))
            ->assertUnauthorized();

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group',
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function user_has_one_group()
    {
        $tippin = $this->userTippin();

        $group = $this->makeGroupThread($tippin);

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.groups.index'))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $group->id,
                        'type' => 2,
                        'type_verbose' => 'GROUP',
                        'group' => true,
                        'name' => 'First Test Group',
                        'options' => [
                            'admin' => true,
                        ],
                    ],
                ],
                'meta' => [
                    'final_page' => true,
                    'index' => true,
                    'per_page' => Messenger::getThreadsIndexCount(),
                    'results' => 1,
                    'total' => 1,
                ],
            ]);
    }

    /** @test */
    public function store_new_group_validates_request()
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => null,
        ])
            ->assertJsonValidationErrors('subject');

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => '12',
            'providers' => [],
        ])
            ->assertJsonValidationErrors([
                'subject',
                'providers',
            ]);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group',
            'providers' => [
                [
                    'id' => null,
                    'alias' => null,
                ],
                [
                    '404' => true,
                    'missing' => false,
                ],
            ],
        ])
            ->assertJsonValidationErrors([
                'providers.0.id',
                'providers.0.alias',
                'providers.1.id',
                'providers.1.alias',
            ]);
    }

    /** @test */
    public function store_group_without_extra_participants()
    {
        $tippin = $this->userTippin();

        Event::fake([
            NewThreadEvent::class,
            NewThreadBroadcast::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group',
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 2,
                'type_verbose' => 'GROUP',
                'group' => true,
                'options' => [
                    'admin' => true,
                    'invitations' => true,
                    'add_participants' => true,
                ],
                'resources' => [
                    'latest_message' => [
                        'type' => 93,
                        'type_verbose' => 'GROUP_CREATED',
                        'body' => 'created Test Group',
                    ],
                ],
            ]);

        Event::assertDispatched(function (NewThreadEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals('Test Group', $event->thread->subject);

            return true;
        });

        Event::assertNotDispatched(NewThreadBroadcast::class);

        $this->assertDatabaseHas('threads', [
            'subject' => 'Test Group',
        ]);
    }

    /** @test */
    public function store_group_with_extra_participants_will_ignore_participant_if_not_friend()
    {
        $tippin = $this->userTippin();

        Event::fake([
            NewThreadEvent::class,
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group',
            'providers' => [
                [
                    'id' => 2,
                    'alias' => 'user',
                ],
            ],
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 2,
                'type_verbose' => 'GROUP',
                'group' => true,
                'options' => [
                    'admin' => true,
                    'invitations' => true,
                    'add_participants' => true,
                ],
                'resources' => [
                    'latest_message' => [
                        'type' => 93,
                        'type_verbose' => 'GROUP_CREATED',
                        'body' => 'created Test Group',
                    ],
                ],
            ]);

        Event::assertNotDispatched(NewThreadBroadcast::class);

        Event::assertNotDispatched(ParticipantsAddedEvent::class);

        Event::assertDispatched(function (NewThreadEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals('Test Group', $event->thread->subject);

            return true;
        });

        $this->assertDatabaseHas('threads', [
            'subject' => 'Test Group',
        ]);
    }

    /** @test */
    public function store_group_with_one_added_participant_that_is_friend()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            NewThreadEvent::class,
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);

        $this->makeFriends(
            $tippin,
            $doe
        );

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group Participants',
            'providers' => [
                [
                    'id' => $doe->getKey(),
                    'alias' => 'user',
                ],
            ],
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 2,
                'type_verbose' => 'GROUP',
                'group' => true,
                'options' => [
                    'admin' => true,
                    'invitations' => true,
                    'add_participants' => true,
                ],
                'resources' => [
                    'latest_message' => [
                        'type' => 93,
                        'type_verbose' => 'GROUP_CREATED',
                        'body' => 'created Test Group Participants',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('threads', [
            'subject' => 'Test Group Participants',
        ]);

        Event::assertDispatched(function (NewThreadEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals('Test Group Participants', $event->thread->subject);

            return true;
        });

        Event::assertDispatched(function (NewThreadBroadcast $event) use ($doe) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertContains('Test Group Participants', $event->broadcastWith()['thread']);

            return true;
        });

        Event::assertDispatched(function (ParticipantsAddedEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals('Test Group Participants', $event->thread->subject);
            $this->assertCount(1, $event->participants);

            return true;
        });
    }

    /** @test */
    public function store_group_with_multiple_providers_added_as_participants_that_are_friends()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $developers = $this->companyDevelopers();

        Event::fake([
            NewThreadEvent::class,
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);

        $this->makeFriends($tippin, $doe);

        $this->makeFriends($tippin, $developers);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Many Participants',
            'providers' => [
                [
                    'id' => $doe->getKey(),
                    'alias' => 'user',
                ],
                [
                    'id' => $developers->getKey(),
                    'alias' => 'company',
                ],
            ],
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 2,
                'type_verbose' => 'GROUP',
                'group' => true,
                'options' => [
                    'admin' => true,
                    'invitations' => true,
                    'add_participants' => true,
                ],
                'resources' => [
                    'latest_message' => [
                        'type' => 93,
                        'type_verbose' => 'GROUP_CREATED',
                        'body' => 'created Test Many Participants',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('threads', [
            'subject' => 'Test Many Participants',
        ]);

        Event::assertDispatched(function (NewThreadEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals('Test Many Participants', $event->thread->subject);

            return true;
        });

        Event::assertDispatched(function (NewThreadBroadcast $event) use ($doe, $developers) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-company.'.$developers->getKey(), $event->broadcastOn());
            $this->assertContains('Test Many Participants', $event->broadcastWith()['thread']);

            return true;
        });

        Event::assertDispatched(function (ParticipantsAddedEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals('Test Many Participants', $event->thread->subject);
            $this->assertCount(2, $event->participants);

            return true;
        });
    }
}
