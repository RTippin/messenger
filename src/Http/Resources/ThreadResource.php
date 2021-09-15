<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Http\Collections\MessageCollection;
use RTippin\Messenger\Http\Collections\ParticipantCollection;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\MessageRepository;

class ThreadResource extends JsonResource
{
    /**
     * @var Thread
     */
    private Thread $thread;

    /**
     * @var false
     */
    private bool $addSystemFeatures;

    /**
     * @var false
     */
    private bool $addResources;

    /**
     * ThreadResource constructor.
     *
     * @param  Thread  $thread
     * @param  bool  $addSystemFeatures
     * @param  bool  $addResources
     */
    public function __construct(Thread $thread,
                                bool $addSystemFeatures = false,
                                bool $addResources = false)
    {
        parent::__construct($thread);

        $this->thread = $thread;
        $this->addSystemFeatures = $addSystemFeatures;
        $this->addResources = $addResources;
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
            'id' => $this->thread->id,
            'type' => $this->thread->type,
            'type_verbose' => $this->thread->getTypeVerbose(),
            'has_call' => $this->thread->hasActiveCall(),
            'locked' => $this->thread->isLocked(),
            'pending' => $this->thread->isPending(),
            'name' => $this->thread->name(),
            'avatar' => $this->thread->threadAvatar(),
            'group' => $this->thread->isGroup(),
            'unread' => $this->thread->isUnread(),
            'unread_count' => $this->thread->unreadCount(),
            'created_at' => $this->thread->created_at,
            'updated_at' => $this->thread->updated_at,
            'options' => [
                'admin' => $this->thread->isAdmin(),
                'manage_bots' => $this->thread->canManageBots(),
                'chat_bots' => $this->thread->hasBotsFeature(),
                'muted' => $this->thread->isMuted(),
                'add_participants' => $this->thread->canAddParticipants(),
                'invitations' => $this->thread->canInviteParticipants(),
                'call' => $this->thread->canCall(),
                'message' => $this->thread->canMessage(),
                'knock' => $this->thread->canKnock(),
                'awaiting_my_approval' => $this->when($this->thread->isPending(),
                    fn () => $this->thread->isAwaitingMyApproval()
                ),
            ],
            'system_features' => $this->when($this->addSystemFeatures,
                fn () => Messenger::getSystemFeatures()
            ),
            'resources' => [
                'recipient' => $this->when($this->thread->isPrivate(),
                    fn () => $this->addRecipient()
                ),
                'active_call' => $this->when($this->thread->hasActiveCall(),
                    fn () => $this->addActiveCall()
                ),
                'participants' => $this->when($this->addResources,
                    fn () => $this->addParticipants()
                ),
                'messages' => $this->when($this->addResources,
                    fn () => $this->addMessages()
                ),
                'latest_message' => $this->addRecentMessage(),
            ],
        ];
    }

    /**
     * @return array|null
     */
    private function addRecentMessage(): ?array
    {
        if (! is_null($this->thread->latestMessage)) {
            return (new MessageResource(
                $this->thread->latestMessage,
                $this->thread
            ))->resolve();
        }

        return null;
    }

    /**
     * @return array|null
     */
    private function addRecipient(): ?array
    {
        return (new ProviderResource(
            $this->thread->recipient()->owner,
            true
        ))->resolve();
    }

    /**
     * @return array|null
     */
    private function addActiveCall(): ?array
    {
        return (new CallResource($this->thread->activeCall, $this->thread))->resolve();
    }

    /**
     * @return array|null
     */
    private function addMessages(): ?array
    {
        return (new MessageCollection(
            app(MessageRepository::class)->getThreadMessagesIndex($this->thread),
            $this->thread
        ))->resolve();
    }

    /**
     * @return array|null
     */
    private function addParticipants(): ?array
    {
        return (new ParticipantCollection(
            $this->thread->participants,
            $this->thread
        ))->resolve();
    }
}
