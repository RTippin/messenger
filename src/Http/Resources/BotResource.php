<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Http\Collections\BotActionCollection;
use RTippin\Messenger\Models\Bot;

class BotResource extends JsonResource
{
    /**
     * @var bool
     */
    private bool $addActions;

    /**
     * BotResource constructor.
     */
    public function __construct(Bot $bot, bool $addActions = false)
    {
        parent::__construct($bot);

        $this->addActions = $addActions;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Bot $bot */
        $bot = $this->resource;

        return [
            'id' => $bot->id,
            'thread_id' => $bot->thread_id,
            'owner_id' => $bot->owner_id,
            'owner_type' => $bot->owner_type,
            'owner' => (new ProviderResource($bot->owner))->resolve(),
            'created_at' => $bot->created_at,
            'updated_at' => $bot->updated_at,
            'name' => $bot->getProviderName(),
            'enabled' => $bot->enabled,
            'hide_actions' => $bot->hide_actions,
            'cooldown' => $bot->cooldown,
            'on_cooldown' => $bot->isOnCooldown(),
            'actions_count' => $this->when(! $this->addActions,
                fn () => $this->addValidActionsCount($bot)
            ),
            'actions' => $this->when($this->addActions,
                fn () => $this->addValidActions($bot)
            ),
            $this->merge($this->addAvatar($bot)),
        ];
    }

    /**
     * @param  Bot  $bot
     * @return array
     */
    private function addAvatar(Bot $bot): array
    {
        return [
            'avatar' => [
                'sm' => $bot->getProviderAvatarRoute('sm'),
                'md' => $bot->getProviderAvatarRoute('md'),
                'lg' => $bot->getProviderAvatarRoute('lg'),
            ],
        ];
    }

    /**
     * @param  Bot  $bot
     * @return int
     */
    private function addValidActionsCount(Bot $bot): int
    {
        return $bot->valid_actions_count ?? $bot->validActions()->count();
    }

    /**
     * @param  Bot  $bot
     * @return array
     */
    private function addValidActions(Bot $bot): array
    {
        return (new BotActionCollection(
            $bot->validActions()
                ->with('owner')
                ->get()
        ))->resolve();
    }
}
