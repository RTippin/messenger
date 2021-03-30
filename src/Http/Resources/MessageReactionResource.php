<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
     * MessageReactionResource constructor.
     *
     * @param MessageReaction $reaction
     */
    public function __construct(MessageReaction $reaction)
    {
        parent::__construct($reaction);

        $this->reaction = $reaction;
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
            'reaction' => $this->reaction->reaction,
            'created_at' => $this->reaction->created_at,
        ];
    }
}
