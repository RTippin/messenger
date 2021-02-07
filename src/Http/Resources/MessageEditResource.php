<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\MessageEdit;

class MessageEditResource extends JsonResource
{
    /**
     * The message instance.
     *
     * @var MessageEdit
     */
    protected MessageEdit $message;

    /**
     * MessageResource constructor.
     *
     * @param MessageEdit $message
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
     * @noinspection PhpMissingParamTypeInspection
     */
    public function toArray($request): array
    {
        return [
            'body' => $this->sanitizedBody(),
            'created_at' => $this->message->created_at,
        ];
    }

    /**
     * @return string
     */
    public function sanitizedBody(): string
    {
        return htmlspecialchars($this->message->body);
    }
}
