<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;

class MessageReactionResource extends JsonResource
{
    /**
     * The message instance.
     *
     * @var MessageReaction
     */
    protected MessageReaction $reaction;

    /**
     * @var Message|null
     */
    protected ?Message $message;

    /**
     * MessageReactionResource constructor.
     *
     * @param MessageReaction $reaction
     * @param Message|null $message
     */
    public function __construct(MessageReaction $reaction, ?Message $message = null)
    {
        parent::__construct($reaction);

        $this->reaction = $reaction;
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
            'id' => $this->reaction->id,
            'reaction' => $this->reaction->reaction,
            'message_id' => $this->reaction->message_id,
            'created_at' => $this->reaction->created_at,
            'owner_id' => $this->reaction->owner_id,
            'owner_type' => $this->reaction->owner_type,
            'owner' => (new ProviderResource($this->reaction->owner))->resolve(),
            'message' => $this->when(! is_null($this->message),
                fn () => $this->addMessage()
            ),
        ];
    }

    /**
     * @return array
     */
    private function addMessage(): array
    {
        return (new MessageResource(
            $this->message,
            $this->message->thread
        ))->resolve();
    }
}
