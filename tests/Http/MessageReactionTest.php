<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Database\Eloquent\Factories\Sequence;
use RTippin\Messenger\Broadcasting\ReactionAddedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ReactionAddedEvent;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
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

    /** @test */
    public function participant_can_view_reacts()
    {
        MessageReaction::factory()
            ->for($this->message)
            ->for($this->tippin, 'owner')
            ->state(new Sequence(
                ['reaction' => ':one:'],
                ['reaction' => ':two:'],
                ['reaction' => ':three:'],
                ['reaction' => ':four:'],
                ['reaction' => ':five:'],
            ))
            ->count(5)
            ->create();
        MessageReaction::factory()
            ->for($this->message)
            ->for($this->doe, 'owner')
            ->state(new Sequence(
                ['reaction' => ':one:'],
                ['reaction' => ':two:'],
                ['reaction' => ':three:'],
                ['reaction' => ':four:'],
                ['reaction' => ':five:'],
                ['reaction' => ':six:'],
            ))
            ->count(6)
            ->create();

        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.messages.reactions.index', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(6, 'data')
            ->assertJsonStructure([
                'data' => [
                    ':one:',
                    ':two:',
                    ':three:',
                    ':four:',
                    ':five:',
                    ':six:',
                ]
            ])
            ->assertJson([
                'meta' => [
                    'total' => 11,
                    'total_unique' => 6,
                ],
            ]);
    }
}
