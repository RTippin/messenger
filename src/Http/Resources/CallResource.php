<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Http\Collections\CallParticipantCollection;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class CallResource extends JsonResource
{
    /**
     * @var bool
     */
    private bool $addParticipants;

    /**
     * @var Call
     */
    private Call $call;

    /**
     * @var Thread
     */
    private Thread $thread;

    /**
     * CallResource constructor.
     *
     * @param  Call  $call
     * @param  Thread  $thread
     * @param  bool  $addParticipants
     */
    public function __construct(Call $call,
                                Thread $thread,
                                bool $addParticipants = false)
    {
        parent::__construct($call);

        $this->addParticipants = $addParticipants;
        $this->call = $call;
        $this->thread = $thread;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->call->id,
            'active' => $this->call->isActive(),
            'type' => $this->call->type,
            'type_verbose' => $this->call->getTypeVerbose(),
            'thread_id' => $this->call->thread_id,
            'created_at' => $this->call->created_at,
            'updated_at' => $this->call->updated_at,
            'owner_id' => $this->call->owner_id,
            'owner_type' => $this->call->owner_type,
            'owner' => (new ProviderResource($this->call->owner))->resolve(),
            'meta' => [
                'thread_id' => $this->thread->id,
                'thread_type' => $this->thread->type,
                'thread_type_verbose' => $this->thread->getTypeVerbose(),
                'thread_name' => $this->thread->name(),
                'thread_avatar' => $this->thread->threadAvatar(),
            ],
            'options' => $this->when($this->call->isActive(),
                fn () => $this->addOptions()
            ),
            'participants' => $this->when($this->shouldAddParticipants(),
                fn () => $this->addParticipants()
            ),
        ];
    }

    /**
     * @return array
     */
    private function addOptions(): array
    {
        return [
            'admin' => $this->call->isCallAdmin($this->thread),
            'setup_complete' => $this->call->isSetup(),
            'in_call' => $this->call->isInCall(),
            'left_call' => $this->call->hasLeftCall(),
            'joined' => $this->call->hasJoinedCall(),
            'kicked' => $this->call->wasKicked(),
            $this->mergeWhen($this->shouldShowRoom(), fn () => [
                'room_id' => $this->call->room_id,
                'room_pin' => $this->call->room_pin,
                'payload' => $this->call->payload,
            ]),
        ];
    }

    /**
     * @return array
     */
    private function addParticipants(): array
    {
        return (new CallParticipantCollection(
            $this->call->participants->load('owner')
        ))->resolve();
    }

    /**
     * @return bool
     */
    private function shouldAddParticipants(): bool
    {
        return $this->addParticipants
            && ! $this->call->isActive();
    }

    /**
     * @return bool
     */
    private function shouldShowRoom(): bool
    {
        return $this->call->isSetup()
            && $this->call->hasJoinedCall()
            && ! $this->call->wasKicked();
    }
}
