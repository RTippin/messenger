<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ReactionAddedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ReactionAddedEvent;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageReactionTest extends FeatureTestCase
{
    private Thread $private;
    private Message $message;
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
        $this->message = $this->createMessage($this->private, $this->tippin);
    }

    /** @test */
    public function non_participant_is_forbidden_to_react()
    {
        $this->actingAs($this->createJaneSmith());

        $this->postJson(route('api.messenger.threads.messages.reactions.store', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]), [
            'reaction' => ':joy:',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_is_forbidden_to_view_reacts()
    {
        $this->actingAs($this->createJaneSmith());

        $this->getJson(route('api.messenger.threads.messages.reactions.index', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_react_to_message()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            ReactionAddedBroadcast::class,
            ReactionAddedEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.messages.reactions.store', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]), [
            'reaction' => ':joy:',
        ])
            ->assertSuccessful()
            ->assertJson([
                'message_id' => $this->message->id,
                'reaction' => ':joy:',
                'owner' => [
                    'name' => 'Richard Tippin',
                    'provider_id' => $this->tippin->getKey(),
                ],
            ]);
    }
}
