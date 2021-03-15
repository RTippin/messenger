<?php

namespace RTippin\Messenger\Http\Resources;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

class MessageResource extends JsonResource
{
    /**
     * The message instance.
     *
     * @var Message
     */
    protected Message $message;

    /**
     * The Thread instance.
     *
     * @var Thread
     */
    protected Thread $thread;

    /**
     * MessageResource constructor.
     *
     * @param Message $message
     * @param Thread $thread
     */
    public function __construct(Message $message, Thread $thread)
    {
        parent::__construct($message);

        $this->thread = $thread;
        $this->message = $message;
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
            'id' => $this->message->id,
            'thread_id' => $this->message->thread_id,
            'owner_id' => $this->message->owner_id,
            'owner_type' => $this->message->owner_type,
            'owner' => (new ProviderResource($this->message->owner))->resolve(),
            'type' => $this->message->type,
            'type_verbose' => $this->message->getTypeVerbose(),
            'system_message' => $this->message->isSystemMessage(),
            'body' => $this->formatMessageBody(),
            'edited' => $this->message->isEdited(),
            'created_at' => $this->message->created_at,
            'updated_at' => $this->message->updated_at,
            'meta' => [
                'thread_id' => $this->message->thread_id,
                'thread_type' => $this->thread->type,
                'thread_type_verbose' => $this->thread->getTypeVerbose(),
                $this->mergeWhen($this->thread->isGroup(),
                    fn () => [
                        'thread_name' => $this->thread->name(),
                        'api_thread_avatar' => $this->thread->threadAvatar(true),
                        'thread_avatar' => $this->thread->threadAvatar(),
                    ]
                ),
            ],
            'temporary_id' => $this->when($this->message->hasTemporaryId(),
                fn () => $this->message->temporaryId()
            ),
            'edited_history_route' => $this->when($this->message->isEdited(),
                fn () => $this->message->getEditHistoryRoute()
            ),
            $this->mergeWhen($this->message->isImage(),
                fn () => $this->linksForImage()
            ),
            $this->mergeWhen($this->message->isDocument(),
                fn () => $this->linksForDocument()
            ),
            $this->mergeWhen($this->message->isAudio(),
                fn () => $this->linksForAudio()
            ),
        ];
    }

    /**
     * @return string
     */
    public function formatMessageBody(): string
    {
        if (! $this->message->isSystemMessage()) {
            return $this->sanitizedBody();
        }

        try {
            $bodyJson = $this->decodeBodyJson();

            switch ($this->message->type) {
                case 90:
                    return $this->formatVideoCall($bodyJson);
                case 88: //participant joined with invite link
                case 91: //group avatar updated
                case 92: //group archived
                case 93: //created group
                case 94: //renamed group
                case 97: //participant left group
                    return $this->sanitizedBody();
                case 95:
                    return $this->formatAdminRemoved($bodyJson);
                case 96:
                    return $this->formatAdminAdded($bodyJson);
                case 98:
                    return $this->formatParticipantRemoved($bodyJson);
                case 99:
                    return $this->formatParticipantsAdded($bodyJson);
            }
        } catch (Exception $e) {
            report($e);
        }

        return 'system message';
    }

    /**
     * @return string
     */
    public function sanitizedBody(): string
    {
        return htmlspecialchars($this->message->body);
    }

    /**
     * @return mixed
     */
    public function decodeBodyJson()
    {
        return json_decode($this->message->body, true);
    }

    /**
     * @param array $bodyJson
     * @return string
     */
    public function formatParticipantsAdded(array $bodyJson): string
    {
        $names = 'added ';

        if (count($bodyJson) > 3) {
            $remaining = count($bodyJson) - 3;
            foreach (array_slice($bodyJson, 0, 3) as $key => $owner) {
                $names .= "{$this->locateContentOwner($owner)->name()}, ";
            }
            $names .= "and {$remaining} others";
        } else {
            foreach ($bodyJson as $key => $owner) {
                if (count($bodyJson) === 1) {
                    $names .= "{$this->locateContentOwner($owner)->name()}";
                } elseif ($key === array_key_last($bodyJson)) {
                    $names .= "and {$this->locateContentOwner($owner)->name()}";
                } else {
                    $names .= "{$this->locateContentOwner($owner)->name()}, ";
                }
            }
        }

        return $names;
    }

    /**
     * @param array $bodyJson
     * @return string
     */
    public function formatAdminAdded(array $bodyJson): string
    {
        return "promoted {$this->locateContentOwner($bodyJson)->name()}";
    }

    /**
     * @param array $bodyJson
     * @return string
     */
    public function formatAdminRemoved(array $bodyJson): string
    {
        return "demoted {$this->locateContentOwner($bodyJson)->name()}";
    }

    /**
     * @param array $bodyJson
     * @return string
     */
    public function formatParticipantRemoved(array $bodyJson): string
    {
        return "removed {$this->locateContentOwner($bodyJson)->name()}";
    }

    /**
     * @param array $bodyJson
     * @return string
     */
    public function formatVideoCall(array $bodyJson): string
    {
        /** @var Call $call */
        $call = $this->thread->calls()
            ->videoCall()
            ->withCount('participants')
            ->find($bodyJson['call_id']);

        if ($call && $call->participants_count > 1) {
            $names = '';
            $participants = $call->participants()
                ->where('owner_id', '!=', $this->message->owner_id)
                ->with('owner')
                ->limit(3)
                ->get();

            if ($call->participants_count > 4) {
                $remaining = $call->participants_count - 4;
                foreach ($participants as $participant) {
                    if ($participants->last()->id === $participant->id) {
                        $names .= " {$participant->owner->name()} and {$remaining} others";
                    } else {
                        $names .= " {$participant->owner->name()},";
                    }
                }
            } else {
                foreach ($participants as $participant) {
                    if ($participants->count() === 1
                        || ($participants->count() === 2
                            && $participants->first()->id === $participant->id)) {
                        $names .= "{$participant->owner->name()}";
                    } elseif ($participants->last()->id === $participant->id) {
                        $names .= " and {$participant->owner->name()}";
                    } else {
                        $names .= " {$participant->owner->name()},";
                    }
                }
            }

            return "was in a video call with {$names}";
        }

        return 'was in a video call';
    }

    /**
     * @param array $data
     * @return GhostUser|MessengerProvider
     */
    public function locateContentOwner(array $data)
    {
        /** @var Participant $participant */
        $participant = $this->thread->participants
            ->where('owner_id', '=', $data['owner_id'])
            ->where('owner_type', '=', $data['owner_type'])
            ->first();

        if ($participant && Messenger::isValidMessengerProvider($participant->owner)) {
            return $participant->owner;
        }

        /** @var MessengerProvider|null $owner */
        $owner = null;

        if (Messenger::isValidMessengerProvider($data['owner_type'])) {
            $owner = $data['owner_type']::find($data['owner_id']);
        }

        return $owner ?: Messenger::getGhostProvider();
    }

    /**
     * @return array
     */
    public function linksForImage(): array
    {
        return [
            'api_image' => [
                'sm' => $this->message->getImageViewRoute('sm', true),
                'md' => $this->message->getImageViewRoute('md', true),
                'lg' => $this->message->getImageViewRoute('lg', true),
            ],
            'image' => [
                'sm' => $this->message->getImageViewRoute('sm'),
                'md' => $this->message->getImageViewRoute('md'),
                'lg' => $this->message->getImageViewRoute('lg'),
            ],
        ];
    }

    /**
     * @return array
     */
    public function linksForAudio(): array
    {
        return [
            'api_audio' => $this->message->getAudioDownloadRoute(true),
            'audio' => $this->message->getAudioDownloadRoute(),
        ];
    }

    /**
     * @return array
     */
    public function linksForDocument(): array
    {
        return [
            'api_document' => $this->message->getDocumentDownloadRoute(true),
            'document' => $this->message->getDocumentDownloadRoute(),
        ];
    }
}
