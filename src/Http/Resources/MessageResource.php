<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Http\Collections\MessageReactionCollection;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\MessageTransformer;

class MessageResource extends JsonResource
{
    /**
     * @var Message
     */
    private Message $message;

    /**
     * @var Thread
     */
    private Thread $thread;

    /**
     * @var bool
     */
    private bool $addRelatedItems;

    /**
     * MessageResource constructor.
     *
     * @param Message $message
     * @param Thread $thread
     * @param bool $addRelatedItems
     */
    public function __construct(Message $message,
                                Thread $thread,
                                bool $addRelatedItems = false)
    {
        parent::__construct($message);

        $this->thread = $thread;
        $this->message = $message;
        $this->addRelatedItems = $addRelatedItems;
        $this->message->setRelation('thread', $this->thread);
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
            'id' => $this->message->id,
            'thread_id' => $this->message->thread_id,
            'owner_id' => $this->message->owner_id,
            'owner_type' => $this->message->owner_type,
            'owner' => (new ProviderResource($this->message->owner))->resolve(),
            'type' => $this->message->type,
            'type_verbose' => $this->message->getTypeVerbose(),
            'system_message' => $this->message->isSystemMessage(),
            'from_bot' => $this->message->isFromBot(),
            'body' => MessageTransformer::transform($this->message),
            'edited' => $this->message->isEdited(),
            'reacted' => $this->message->isReacted(),
            'embeds' => $this->message->showEmbeds(),
            'extra' => $this->message->extra,
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
            'reactions' => $this->when($this->addRelatedItems && $this->message->isReacted(),
                fn () => $this->addReactions()
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
            $this->mergeWhen($this->addRelatedItems && ! is_null($this->message->reply_to_id),
                fn () => $this->addReplyToMessage()
            ),
        ];
    }

    /**
     * @return array
     */
    public function addReplyToMessage(): array
    {
        return [
            'reply_to_id' => $this->message->reply_to_id,
            'reply_to' => ! is_null($this->message->replyTo)
                ? (new MessageResource(
                    $this->message->replyTo,
                    $this->thread
                ))->resolve()
                : null,
        ];
    }

    /**
     * @return array
     */
    public function addReactions(): array
    {
        return (new MessageReactionCollection(
            $this->message->reactions()->with('owner')->get()
        ))->resolve();
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
