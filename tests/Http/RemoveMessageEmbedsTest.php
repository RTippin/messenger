<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\EmbedsRemovedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\EmbedsRemovedEvent;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveMessageEmbedsTest extends FeatureTestCase
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
    public function guest_is_unauthorized()
    {
        $this->deleteJson(route('api.messenger.threads.messages.embeds.destroy', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertUnauthorized();
    }

    /** @test */
    public function non_participant_is_forbidden()
    {
        $this->actingAs($this->companyDevelopers());

        $this->deleteJson(route('api.messenger.threads.messages.embeds.destroy', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function sender_can_remove_message_embeds()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            EmbedsRemovedBroadcast::class,
            EmbedsRemovedEvent::class,
        ]);

        $this->deleteJson(route('api.messenger.threads.messages.embeds.destroy', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function recipient_forbidden_to_remove_message_embeds()
    {
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.messages.embeds.destroy', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_remove_message_embeds_when_thread_locked()
    {
        $this->doe->delete();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.messages.embeds.destroy', [
            'thread' => $this->private->id,
            'message' => $this->message->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function group_admin_can_remove_embeds_from_non_owned_message()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $message = $this->createMessage($group, $this->doe);
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            EmbedsRemovedBroadcast::class,
            EmbedsRemovedEvent::class,
        ]);

        $this->deleteJson(route('api.messenger.threads.messages.embeds.destroy', [
            'thread' => $group->id,
            'message' => $message->id,
        ]))
            ->assertSuccessful();
    }
}
