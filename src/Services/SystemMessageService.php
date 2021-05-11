<?php

namespace RTippin\Messenger\Services;

use Illuminate\Support\Collection;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

class SystemMessageService
{
    /**
     * @var Thread
     */
    private Thread $thread;

    /**
     * @var MessengerProvider
     */
    private MessengerProvider $owner;

    /**
     * @param Thread $thread
     * @param MessengerProvider $provider
     * @return $this
     */
    public function setStoreData(Thread $thread, MessengerProvider $provider): self
    {
        $this->thread = $thread;
        $this->owner = $provider;

        return $this;
    }

    /**
     * @return array
     */
    public function makeJoinedWithInvite(): array
    {
        return $this->generateStoreResponse('joined', 'PARTICIPANT_JOINED_WITH_INVITE');
    }

    /**
     * @param Call $call
     * @return array
     */
    public function makeVideoCall(Call $call): array
    {
        $body = (new Collection(['call_id' => $call->id]))->toJson();

        return $this->generateStoreResponse($body, 'VIDEO_CALL');
    }

    /**
     * @return array
     */
    public function makeGroupAvatarChanged(): array
    {
        return $this->generateStoreResponse('updated the avatar', 'GROUP_AVATAR_CHANGED');
    }

    /**
     * @return array
     */
    public function makeThreadArchived(): array
    {
        $body = $this->thread->isGroup() ? 'archived the group' : 'archived the conversation';

        return $this->generateStoreResponse($body, 'THREAD_ARCHIVED');
    }

    /**
     * @param string $subject
     * @return array
     */
    public function makeGroupCreated(string $subject): array
    {
        return $this->generateStoreResponse("created $subject", 'GROUP_CREATED');
    }

    /**
     * @param string $subject
     * @return array
     */
    public function makeGroupRenamed(string $subject): array
    {
        return $this->generateStoreResponse("renamed the group to $subject", 'GROUP_RENAMED');
    }

    /**
     * @param Participant $participant
     * @return array
     */
    public function makeParticipantDemoted(Participant $participant): array
    {
        return $this->generateStoreResponse($this->generateParticipantJson($participant), 'DEMOTED_ADMIN');
    }

    /**
     * @param Participant $participant
     * @return array
     */
    public function makeParticipantPromoted(Participant $participant): array
    {
        return $this->generateStoreResponse($this->generateParticipantJson($participant), 'PROMOTED_ADMIN');
    }

    /**
     * @return array
     */
    public function makeGroupLeft(): array
    {
        return $this->generateStoreResponse('left', 'PARTICIPANT_LEFT_GROUP');
    }

    /**
     * @param Participant $participant
     * @return array
     */
    public function makeRemovedFromGroup(Participant $participant): array
    {
        return $this->generateStoreResponse($this->generateParticipantJson($participant), 'PARTICIPANT_REMOVED');
    }

    /**
     * @param Collection $participants
     * @return array
     */
    public function makeParticipantsAdded(Collection $participants): array
    {
        $body = $participants->transform(fn (Participant $participant) => [
            'owner_id' => $participant->owner_id,
            'owner_type' => $participant->owner_type,
        ])->toJson();

        return $this->generateStoreResponse($body, 'PARTICIPANTS_ADDED');
    }

    /**
     * @param Participant $participant
     * @return string
     */
    private function generateParticipantJson(Participant $participant): string
    {
        return (new Collection([
            'owner_id' => $participant->owner_id,
            'owner_type' => $participant->owner_type,
        ]))->toJson();
    }

    /**
     * @param string $body
     * @param string $type
     * @return array
     */
    private function generateStoreResponse(string $body, string $type): array
    {
        return [
            $this->thread,
            $this->owner,
            $body,
            $type,
        ];
    }
}
