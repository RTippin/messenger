<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PrivateMessageTest extends FeatureTestCase
{
    private Thread $private;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->makePrivateThread(
            $this->userTippin(),
            $this->userDoe()
        );
    }

    /** @test */
    public function user_can_send_message()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'temporary_id' => '123-456-789',
                'type' => 0,
                'type_verbose' => 'MESSAGE',
                'body' => 'Hello!',
                'owner' => [
                    'provider_id' => $tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);

        Event::assertDispatched(function (NewMessageBroadcast $event) use ($doe, $tippin) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertEquals($this->private->id, $event->broadcastWith()['thread_id']);
            $this->assertEquals('123-456-789', $event->broadcastWith()['temporary_id']);

            return true;
        });

        Event::assertDispatched(function (NewMessageEvent $event) {
            return $this->private->id === $event->message->thread_id;
        });
    }

    /** @test */
    public function non_participant_forbidden_to_send_message()
    {
        $this->actingAs($this->companyDevelopers());

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            'message' => 'Hello!',
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /**
     * @test
     * @dataProvider messageValidation
     * @dataProvider temporaryIdValidation
     * @param $messageInput
     * @param $messageValue
     */
    public function send_message_validates_request($messageInput, $messageValue)
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.messages.store', [
            'thread' => $this->private->id,
        ]), [
            $messageInput => $messageValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors($messageInput);
    }

    public function messageValidation(): array
    {
        return [
            'Message cannot be empty' => ['message', ''],
            'Message cannot be integer' => ['message', 5],
            'Message cannot be null' => ['message', null],
            'Message cannot be an array' => ['message', [1, 2]],
        ];
    }

    public function temporaryIdValidation(): array
    {
        return [
            'Temp ID cannot be empty' => ['temporary_id', ''],
            'Temp ID cannot be integer' => ['temporary_id', 5],
            'Temp ID cannot be null' => ['temporary_id', null],
            'Temp ID cannot be an array' => ['temporary_id', [1, 2]],
        ];
    }
}
