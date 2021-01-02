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

        $this->createFriends(
            $tippin,
            $doe
        );

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $doe->getKey(),
        ])
            ->assertSuccessful();

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

        Event::assertDispatched(function (NewThreadBroadcast $event){
            return $event->broadcastWith()['thread']['pending'] === false;
        });

        Event::assertDispatched(NewThreadEvent::class);
    }

    /** @test */
    public function creating_new_private_thread_with_non_friend_company_is_pending()
    {
        $tippin = $this->userTippin();

        $developers = $this->companyDevelopers();

        $this->expectsEvents([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'company',
            'recipient_id' => $developers->getKey(),
        ])
            ->assertSuccessful();

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
    }

    /** @test */
    public function creating_new_private_forbidden_when_one_exist()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->createPrivateThread(
            $tippin,
            $doe
        );

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $doe->getKey(),
        ])
            ->assertForbidden();
    }

    /**
     * @test
     * @dataProvider messageValidation
     * @param $messageValue
     */
    public function create_new_private_checks_message($messageValue)
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => $messageValue,
            'recipient_alias' => 'user',
            'recipient_id' => 1,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    /**
     * @test
     * @dataProvider recipientValidation
     * @param $aliasValue
     * @param $idValue
     * @param $errors
     */
    public function create_new_private_checks_recipient_values($aliasValue, $idValue, $errors)
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello!',
            'recipient_alias' => $aliasValue,
            'recipient_id' => $idValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors($errors);
    }

    public function messageValidation(): array
    {
        return [
            'Message cannot be empty' => [''],
            'Message cannot be integers' => [5],
            'Message cannot be boolean' => [true],
            'Message cannot be null' => [null],
            'Message cannot be an array' => [[1, 2]],
        ];
    }

    public function recipientValidation(): array
    {
        return [
            'Alias and ID cannot be empty' => ['', '', ['recipient_alias', 'recipient_id']],
            'Alias and ID cannot be boolean' => [true, true, ['recipient_alias', 'recipient_id']],
            'Alias and ID cannot be null' => [null, null, ['recipient_alias', 'recipient_id']],
            'Alias must be string' => [5, 1, ['recipient_alias']],
            'ID cannot be array' => ['user', [1, 2], ['recipient_id']],
        ];
    }
}
