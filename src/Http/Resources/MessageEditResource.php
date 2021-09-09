<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\MessageEdit;
use RTippin\Messenger\Support\MessageTransformer;

class MessageEditResource extends JsonResource
{
    /**
     * The message instance.
     *
     * @var MessageEdit
     */
    private MessageEdit $message;

    /**
     * MessageEditResource constructor.
     *
     * @param  MessageEdit  $message
     */
    public function __construct(MessageEdit $message)
    {
        parent::__construct($message);

        $this->message = $message;
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
            'body' => MessageTransformer::sanitizedBody($this->message->body),
            'edited_at' => $this->message->edited_at,
        ];
    }
}
