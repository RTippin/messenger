<?php

namespace RTippin\Messenger\Http\Resources;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Definitions;

/**
 * @property-read Model|MessengerProvider $provider
 */

class ProviderResource extends JsonResource
{
    /**
     * @var Model|MessengerProvider
     */
    protected $provider;

    /**
     * @var bool
     */
    protected bool $addOptions;

    /**
     * @var null|int
     */
    protected ?int $forceFriendStatus;

    /**
     * @var bool
     */
    protected bool $addBaseModel;

    /**
     * ProviderResource constructor.
     *
     * @param mixed $provider
     * @param bool $addOptions
     * @param null $forceFriendStatus
     * @param bool $addBaseModel
     */
    public function __construct($provider,
                                $addOptions = false,
                                $forceFriendStatus = null,
                                $addBaseModel = true)
    {
        parent::__construct($provider);

        $this->addOptions = $addOptions;
        $this->forceFriendStatus = $forceFriendStatus;
        $this->addBaseModel = $addBaseModel;
        $this->provider = messenger()->isValidMessengerProvider($provider)
            ? $provider
            : messenger()->getGhostProvider();
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
            'name' => $this->provider->name(),
            'route' => $this->provider->getRoute(),
            'provider_id' => $this->provider->getKey(),
            'provider_alias' => messenger()->findProviderAlias($this->provider) ?: 'ghost',
            'base' => $this->when($this->addBaseModel,
                fn() => $this->provider->withoutRelations()->toArray()
            ),
            'options' => $this->when($this->addOptions,
                fn() => $this->addOptions()
            ),
            $this->merge($this->addAvatar())
        ];
    }

    /**
     * @return array
     */
    private function addAvatar(): array
    {
        return [
            'api_avatar' => [
                'sm' => $this->provider->getAvatarRoute('sm', true),
                'md' => $this->provider->getAvatarRoute('md', true),
                'lg' => $this->provider->getAvatarRoute('lg', true)
            ],
            'avatar' => [
                'sm' => $this->provider->getAvatarRoute('sm'),
                'md' => $this->provider->getAvatarRoute('md'),
                'lg' => $this->provider->getAvatarRoute('lg')
            ],
        ];
    }

    /**
     * @return array
     * @noinspection SpellCheckingInspection
     */
    private function addOptions(): array
    {
        $isFriendable = $this->isFriendable();
        $isSearchable = $this->isSearchable();
        $friendStatus = $this->getFriendStatus();

        return [
            'can_message_first' => $this->canMessageFirst(),
            'friendable' => $isFriendable,
            'can_friend' => $this->canFriend($isFriendable),
            'searchable' => $isSearchable,
            'can_search' => $this->canSearch($isSearchable),
            'online_status' => $this->provider->onlineStatus(),
            'online_status_verbose' => Definitions::OnlineStatus[$this->provider->onlineStatus()],
            'friend_status' => $friendStatus,
            'friend_status_verbose' => Definitions::FriendStatus[$friendStatus],
            'last_active' => $this->getLastActive($friendStatus),
            $this->mergeWhen($friendStatus === 1, fn() => [
                'friend_id' => $this->getFriendResourceId($friendStatus)
            ]),
            $this->mergeWhen($friendStatus === 2, fn() => [
                'sent_friend_id' => $this->getFriendResourceId($friendStatus)
            ]),
            $this->mergeWhen($friendStatus === 3, fn() => [
                'pending_friend_id' => $this->getFriendResourceId($friendStatus)
            ]),
        ];
    }

    /**
     * @return bool
     */
    private function canMessageFirst(): bool
    {
        return messenger()->canMessageProviderFirst($this->provider)
            && ! messenger()->getProvider()->is($this->provider);
    }

    /**
     * @return bool
     * @noinspection SpellCheckingInspection
     */
    private function isFriendable(): bool
    {
        return messenger()->isProviderFriendable($this->provider)
            && ! messenger()->getProvider()->is($this->provider);
    }

    /**
     * @param bool $isFriendable
     * @return bool
     * @noinspection SpellCheckingInspection
     */
    private function canFriend(bool $isFriendable): bool
    {
        return $isFriendable
            ? messenger()->canFriendProvider($this->provider)
            : false;
    }

    /**
     * @return bool
     */
    private function isSearchable(): bool
    {
        return messenger()->isProviderSearchable($this->provider);
    }

    /**
     * @param bool $isSearchable
     * @return bool
     */
    private function canSearch(bool $isSearchable): bool
    {
        return $isSearchable
            ? messenger()->canSearchProvider($this->provider)
            : false;
    }

    /**
     * @param int $friendStatus
     * @return Carbon|string|null
     */
    private function getLastActive(int $friendStatus)
    {
        if(messenger()->isOnlineStatusEnabled())
        {
            return $friendStatus === 1
                ? $this->provider->lastActiveDateTime()
                : null;
        }

        return null;
    }

    /**
     * @return int
     */
    private function getFriendStatus(): int
    {
        if(is_null($this->forceFriendStatus))
        {
            return messengerFriends()->friendStatus($this->provider);
        }

        return $this->forceFriendStatus;
    }

    /**
     * @param int $friendStatus
     * @return string|null
     */
    private function getFriendResourceId(int $friendStatus): ?string
    {
        return optional(
            messengerFriends()->getFriendResource(
                $friendStatus,
                $this->provider
            )
        )->id;
    }
}
