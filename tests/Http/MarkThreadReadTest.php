<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class MarkThreadReadTest extends HttpTestCase
{
    /** @test */
    public function mark_read_cannot_be_a_post()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.mark.read', [
            'thread' => $thread->id,
        ]))
            ->assertStatus(405);
    }

    /** @test */
    public function non_participant_forbidden_to_mark_read()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function unread_participant_can_mark_read()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function read_participant_can_mark_read()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->read()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function thread_pending_participant_approval_will_change_nothing()
    {
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();

        $this->assertNull($participant->fresh()->last_read);
    }

    /** @test */
    public function thread_awaiting_participant_approval_can_mark_read()
    {
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.mark.read', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();

        $this->assertNotNull($participant->fresh()->last_read);
    }
}
