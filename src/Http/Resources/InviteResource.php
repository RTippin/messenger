<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\Invite;

class InviteResource extends JsonResource
{
    /**
     * @var bool
     */
    private bool $joining;

    /**
     * InviteResource constructor.
     * @param Invite $invite
     * @param bool $joining
     */
    public function __construct(Invite $invite, $joining = false)
    {
        parent::__construct($invite);

        $this->joining = $joining;
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
        /** @var Invite $invite */
        $invite = $this->resource;

        return [
            'owner' => $this->when(! $this->joining,
                fn () => new ProviderResource($invite->owner)
            ),
            'options' => $this->when($this->joining,
                fn () => $this->joinOptions($invite)
            ),
            'route' => $invite->getInvitationRoute(),
            $this->merge($invite->withoutRelations()),
        ];
    }

    /**
     * @param Invite $invite
     * @return array
     */
    private function joinOptions(Invite $invite): array
    {
        $isValid = $invite->isValid();

        return [
            'messenger_auth' => messenger()->isProviderSet(),
            'in_thread' => $isValid ? $this->isAlreadyInThread($invite) : false,
            'thread_name' => $isValid ? $invite->thread->name() : null,
            'is_valid' => $isValid,
        ];
    }

    /**
     * @param Invite $invite
     * @return bool
     */
    private function isAlreadyInThread(Invite $invite): bool
    {
        return messenger()->isProviderSet()
            ? $invite->thread->hasCurrentProvider()
            : false;
    }
}
