<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

class ParticipantResource extends JsonResource
{
    /**
     * @var Thread
     */
    protected Thread $thread;

    /**
     * @var Participant
     */
    protected Participant $participant;

    /**
     * ParticipantResource constructor.
     *
     * @param $participant
     * @param Thread $thread
     */
    public function __construct(Participant $participant, Thread $thread)
    {
        parent::__construct($participant);

        $this->thread = $thread;
        $this->participant = $participant;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->participant->id,
            'admin' => $this->participant->admin,
            'pending' => $this->participant->pending,
            'send_knocks' => $this->participant->send_knocks,
            'send_messages' => $this->participant->send_messages,
            'add_participants' => $this->participant->add_participants,
            'manage_invites' => $this->participant->manage_invites,
            'start_calls' => $this->participant->start_calls,
            'owner_id' => $this->participant->owner_id,
            'owner_type' => $this->participant->owner_type,
            'owner' => new ProviderResource($this->participant->owner, true),
            'created_at' => $this->participant->created_at,
            'updated_at' => $this->participant->updated_at,
            'last_read' => [
                'time' => $this->participant->last_read,
                'message_id' => $this->lastSeenMessageId()
            ]
        ];
    }

    /**
     * @return Message|null
     */
    private function lastSeenMessageId(): ?Message
    {
        if(is_null($this->participant->last_read))
        {
            return null;
        }

        if($this->thread->updated_at <= $this->participant->last_read)
        {
            return optional($this->thread->recentMessage)->id;
        }

        return optional($this->participant->lastSeenMessage)->id;
    }
}
