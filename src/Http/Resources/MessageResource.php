<?php

namespace RTippin\Messenger\Http\Resources;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
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
     * The Thread instance
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
    public function toArray($request)
    {
        return [
            'id' => $this->message->id,
            'thread_id' => $this->message->thread_id,
            'owner_id' => $this->message->owner_id,
            'owner_type' => $this->message->owner_type,
            'owner' => new ProviderResource($this->message->owner),
            'type' => $this->message->type,
            'type_verbose' => $this->message->getTypeVerbose(),
            'system_message' => $this->message->isSystemMessage(),
            'body' => $this->formatMessageBody(),
            'created_at' => $this->message->created_at,
            'updated_at' => $this->message->updated_at,
            'meta' => [
                'thread_id' => $this->message->thread_id,
                'thread_type' => $this->thread->type,
                'thread_type_verbose' => $this->thread->getTypeVerbose(),
                $this->mergeWhen($this->thread->isGroup(),
                    fn() => [
                        'thread_name' => $this->thread->name(),
                        'api_thread_avatar' => $this->thread->threadAvatar(true),
                        'thread_avatar' => $this->thread->threadAvatar()
                    ]
                )
            ],
            'temporary_id' => $this->when($this->message->hasTemporaryId(),
                fn() => $this->message->temporaryId()
            ),
            $this->mergeWhen($this->message->isImage(),
                fn() => $this->linksForImage()
            ),
            $this->mergeWhen($this->message->isDocument(),
                fn() => $this->linksForDocument()
            )
        ];
    }

    /**
     * @return string
     */
    public function formatMessageBody()
    {
        if(! $this->message->isSystemMessage())
        {
            return $this->sanitizedBody();
        }

        try{
            $bodyJson = $this->decodeBodyJson();

            switch($this->message->type)
            {
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
        }catch(Exception $e){
            report($e);
        }

        return 'system message';
    }

    /**
     * @return string
     */
    public function sanitizedBody()
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
    public function formatParticipantsAdded(array $bodyJson)
    {
        $names = 'added ';

        foreach($bodyJson as $key => $owner)
        {
            if(count($bodyJson) === 1)
            {
                $names .= "{$this->locateContentOwner($owner)->name()}";
            }
            else if(count($bodyJson) > 1
                && $key === array_key_last($bodyJson))
            {
                $names .= "and {$this->locateContentOwner($owner)->name()}";
            }
            else{
                $names .= "{$this->locateContentOwner($owner)->name()}, ";
            }
        }
        return "{$names} to the group";
    }

    /**
     * @param array $bodyJson
     * @return string
     */
    public function formatAdminAdded(array $bodyJson)
    {
        return "promoted {$this->locateContentOwner($bodyJson)->name()} to administrator";
    }

    /**
     * @param array $bodyJson
     * @return string
     */
    public function formatAdminRemoved(array $bodyJson)
    {
        return "demoted {$this->locateContentOwner($bodyJson)->name()} from administrator";
    }

    /**
     * @param array $bodyJson
     * @return string
     */
    public function formatParticipantRemoved(array $bodyJson)
    {
        return "removed {$this->locateContentOwner($bodyJson)->name()}";
    }

    /**
     * @param array $bodyJson
     * @return string
     */
    public function formatVideoCall(array $bodyJson)
    {
        /** @var Call $call */

        $call = $this->thread->calls()
            ->videoCall()
            ->with('participants.owner')
            ->firstWhere('id', $bodyJson['call_id']);

        if($call)
        {
            $names = '';

            /** @var CallParticipant|Collection $participants */

            $participants = $call->participants->reject(function($value){
                return $value->owner_id === $this->message->owner_id
                    && $value->owner_type === $this->message->owner_type;
            });

            if($participants->count())
            {
                foreach($participants as $participant)
                {
                    if($participants->count() === 1
                        || ($participants->count() === 2
                            && $participants->first()->id === $participant->id))
                    {
                        $names .= "{$participant->owner->name()}";
                    }
                    else if($participants->count() > 1
                        && $participants->last()->id === $participant->id)
                    {
                        $names .= " and {$participant->owner->name()}";
                    }
                    else{
                        $names .= " {$participant->owner->name()},";
                    }
                }

                return "was in a video call with {$names}";
            }
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

        if($participant && messenger()->isValidMessengerProvider($participant->owner))
        {
            return $participant->owner;
        }

        /** @var MessengerProvider|null $owner */

        $owner = null;

        if(messenger()->isValidMessengerProvider($data['owner_type']))
        {
            $owner = $data['owner_type']::find($data['owner_id']);
        }

        return $owner ?: messenger()->getGhostProvider();
    }

    /**
     * @return array
     */
    public function linksForImage()
    {
        return [
            'api_image' => [
                'sm' => $this->message->getImageViewRoute('sm', true),
                'md' => $this->message->getImageViewRoute('md', true),
                'lg' => $this->message->getImageViewRoute('lg', true)
            ],
            'image' => [
                'sm' => $this->message->getImageViewRoute('sm'),
                'md' => $this->message->getImageViewRoute('md'),
                'lg' => $this->message->getImageViewRoute('lg')
            ]
        ];
    }

    /**
     * @return array
     */
    public function linksForDocument()
    {
        return [
            'api_document' => $this->message->getDocumentDownloadRoute(true),
            'document' => $this->message->getDocumentDownloadRoute()
        ];
    }
}
