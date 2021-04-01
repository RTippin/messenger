<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Database\Eloquent\Factories\Sequence;
use RTippin\Messenger\Actions\Messages\AddReaction;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\ReactionException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class AddReactionTest extends FeatureTestCase
{
    private Thread $group;
    private Message $message;
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->group = $this->createGroupThread($this->tippin, $this->doe);
        $this->message = $this->createMessage($this->group, $this->tippin);
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setMessageReactions(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Message reactions are currently disabled.');

        app(AddReaction::class)->withoutDispatches()->execute(
            $this->group,
            $this->message,
            ':joy:'
        );
    }

    /** @test */
    public function it_throws_exception_if_no_valid_emojis()
    {
        $this->expectException(ReactionException::class);
        $this->expectExceptionMessage('No valid reactions found.');

        app(AddReaction::class)->withoutDispatches()->execute(
            $this->group,
            $this->message,
            ':unknown:'
        );
    }

    /** @test */
    public function it_throws_exception_if_reaction_already_exist()
    {
        MessageReaction::factory()
            ->for($this->message)
            ->for($this->tippin, 'owner')
            ->create([
                'reaction' => ':joy:',
            ]);

        $this->expectException(ReactionException::class);
        $this->expectExceptionMessage('You have already used that reaction.');

        app(AddReaction::class)->withoutDispatches()->execute(
            $this->group,
            $this->message,
            ':joy:'
        );
    }

    /** @test */
    public function it_throws_exception_if_reaction_exceeds_max_unique()
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
                ['reaction' => ':six:'],
                ['reaction' => ':seven:'],
                ['reaction' => ':eight:'],
                ['reaction' => ':nine:'],
                ['reaction' => ':ten:'],
            ))
            ->count(10)
            ->create();

        $this->expectException(ReactionException::class);
        $this->expectExceptionMessage('We appreciate the enthusiasm, but there are already too many reactions on this message.');

        app(AddReaction::class)->withoutDispatches()->execute(
            $this->group,
            $this->message,
            ':joy:'
        );
    }

    /** @test */
    public function it_stores_reaction_and_marks_messages_reacted()
    {
        app(AddReaction::class)->withoutDispatches()->execute(
            $this->group,
            $this->message,
            ':joy:'
        );

        $this->assertDatabaseHas('message_reactions', [
            'message_id' => $this->message->id,
            'reaction' => ':joy:',
        ]);
        $this->assertDatabaseHas('messages', [
            'id' => $this->message->id,
            'reacted' => true,
        ]);
    }
}
