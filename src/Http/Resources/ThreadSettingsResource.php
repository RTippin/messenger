<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\Thread;

class ThreadSettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Thread $thread */
        $thread = $this->resource;

        return [
            'name' => $thread->name(),
            'api_avatar' => $thread->threadAvatar(true),
            'avatar' => $thread->threadAvatar(),
            'add_participants' => $thread->add_participants,
            'bots' => $thread->chat_bots,
            'invitations' => $thread->invitations,
            'calling' => $thread->calling,
            'messaging' => $thread->messaging,
            'knocks' => $thread->knocks,
            'system_features' => (new SystemFeaturesResource(null))->resolve(),
        ];
    }
}
