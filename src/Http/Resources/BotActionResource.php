<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\BotAction;

class BotActionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var BotAction $action */
        $action = $this->resource;

        return [
            'id' => $action->id,
            'bot_id' => $action->bot_id,
            'owner_id' => $action->owner_id,
            'owner_type' => $action->owner_type,
            'created_at' => $action->created_at,
            'updated_at' => $action->updated_at,
            'enabled' => $action->enabled,
            'admin_only' => $action->admin_only,
            'cooldown' => $action->cooldown,
            'on_cooldown' => $action->isOnCooldown(),
            'match' => $action->getMatchMethod(),
            'match_description' => $action->getMatchDescription(),
            'triggers' => $action->getTriggers(),
            'payload' => $action->getPayload(),
            'handler' => $action->getHandler()->toArray(),
            'owner' => (new ProviderResource($action->owner))->resolve(),
        ];
    }
}
