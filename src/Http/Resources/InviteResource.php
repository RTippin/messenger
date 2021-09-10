<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Invite;

class InviteResource extends JsonResource
{
    /**
     * @var bool
     */
    private bool $joining;

    /**
     * InviteResource constructor.
     *
     * @param  Invite  $invite
     * @param  bool  $joining
     */
    public function __construct(Invite $invite, bool $joining = false)
    {
        parent::__construct($invite);

        $this->joining = $joining;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Invite $invite */
        $invite = $this->resource;

        return [
            'owner' => $this->when(! $this->joining,
                fn () => (new ProviderResource($invite->owner))->resolve()
            ),
            'options' => $this->when($this->joining,
                fn () => $this->joinOptions($invite)
            ),
            'route' => $invite->getInvitationRoute(),
            $this->merge($invite->withoutRelations()->toArray()),
        ];
    }

    /**
     * @param  Invite  $invite
     * @return array
     */
    private function joinOptions(Invite $invite): array
    {
        $isValid = $invite->isValid();

        return [
            'messenger_auth' => Messenger::isProviderSet(),
            'in_thread' => $isValid && $this->isAlreadyInThread($invite),
            'thread_name' => $isValid ? $invite->thread->name() : null,
            'is_valid' => $isValid,
            'avatar' => $isValid ? $invite->inviteAvatar() : null,
        ];
    }

    /**
     * @param  Invite  $invite
     * @return bool
     */
    private function isAlreadyInThread(Invite $invite): bool
    {
        return Messenger::isProviderSet() && $invite->thread->hasCurrentProvider();
    }
}
