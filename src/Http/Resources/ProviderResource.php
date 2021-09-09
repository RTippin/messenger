<?php

namespace RTippin\Messenger\Http\Resources;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\GhostUser;

class ProviderResource extends JsonResource
{
    /**
     * @var Model|MessengerProvider
     */
    private $provider;

    /**
     * @var bool
     */
    private bool $addOptions;

    /**
     * @var null|int
     */
    private ?int $forceFriendStatus;

    /**
     * @var bool
     */
    private bool $addBaseModel;

    /**
     * ProviderResource constructor.
     *
     * @param  mixed  $provider
     * @param  bool  $addOptions
     * @param  int|null  $forceFriendStatus
     * @param  bool  $addBaseModel
     */
    public function __construct($provider,
                                bool $addOptions = false,
                                ?int $forceFriendStatus = null,
                                bool $addBaseModel = true)
    {
        parent::__construct($provider);

        $this->addOptions = $addOptions;
        $this->forceFriendStatus = $forceFriendStatus;
        $this->addBaseModel = $addBaseModel;
        $this->setProvider($provider);
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
            'name' => $this->provider->getProviderName(),
            'route' => $this->provider->getProviderProfileRoute(),
            'provider_id' => $this->provider->getKey(),
            'provider_alias' => Messenger::findProviderAlias($this->provider) ?: 'ghost',
            'base' => $this->when($this->addBaseModel,
                fn () => $this->provider->withoutRelations()->toArray()
            ),
            'options' => $this->when($this->addOptions,
                fn () => $this->addOptions()
            ),
            $this->merge($this->addAvatar()),
        ];
    }

    /**
     * @param  mixed  $provider
     */
    private function setProvider($provider): void
    {
        if (Messenger::isValidMessengerProvider($provider)
            || $provider instanceof GhostUser) {
            $this->provider = $provider;
        } else {
            $this->provider = Messenger::getGhostProvider();
        }
    }

    /**
     * @return array
     */
    private function addAvatar(): array
    {
        return [
            'avatar' => [
                'sm' => $this->provider->getProviderAvatarRoute('sm'),
                'md' => $this->provider->getProviderAvatarRoute('md'),
                'lg' => $this->provider->getProviderAvatarRoute('lg'),
            ],
        ];
    }

    /**
     * @return array
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
            'online_status' => $this->provider->getProviderOnlineStatus(),
            'online_status_verbose' => MessengerProvider::ONLINE_STATUS[$this->provider->getProviderOnlineStatus()],
            'friend_status' => $friendStatus,
            'friend_status_verbose' => FriendDriver::STATUS[$friendStatus],
            'last_active' => $this->getLastActive($friendStatus),
            $this->mergeWhen($friendStatus === FriendDriver::FRIEND, fn () => [
                'friend_id' => $this->getFriendResourceId($friendStatus),
            ]),
            $this->mergeWhen($friendStatus === FriendDriver::SENT_FRIEND_REQUEST, fn () => [
                'sent_friend_id' => $this->getFriendResourceId($friendStatus),
            ]),
            $this->mergeWhen($friendStatus === FriendDriver::PENDING_FRIEND_REQUEST, fn () => [
                'pending_friend_id' => $this->getFriendResourceId($friendStatus),
            ]),
        ];
    }

    /**
     * @return bool
     */
    private function canMessageFirst(): bool
    {
        return Messenger::canMessageProviderFirst($this->provider)
            && Messenger::getProvider()->isNot($this->provider);
    }

    /**
     * @return bool
     */
    private function isFriendable(): bool
    {
        return Messenger::isProviderFriendable($this->provider)
            && Messenger::getProvider()->isNot($this->provider);
    }

    /**
     * @param  bool  $isFriendable
     * @return bool
     */
    private function canFriend(bool $isFriendable): bool
    {
        return $isFriendable && Messenger::canFriendProvider($this->provider);
    }

    /**
     * @return bool
     */
    private function isSearchable(): bool
    {
        return Messenger::isProviderSearchable($this->provider);
    }

    /**
     * @param  bool  $isSearchable
     * @return bool
     */
    private function canSearch(bool $isSearchable): bool
    {
        return $isSearchable && Messenger::canSearchProvider($this->provider);
    }

    /**
     * @param  int  $friendStatus
     * @return Carbon|string|null
     */
    private function getLastActive(int $friendStatus)
    {
        if (Messenger::isOnlineStatusEnabled()) {
            return $friendStatus === FriendDriver::FRIEND
                ? $this->provider->{$this->provider->getProviderLastActiveColumn()}
                : null;
        }

        return null;
    }

    /**
     * @return int
     */
    private function getFriendStatus(): int
    {
        if (is_null($this->forceFriendStatus)) {
            return app(FriendDriver::class)->friendStatus($this->provider);
        }

        return $this->forceFriendStatus;
    }

    /**
     * @param  int  $friendStatus
     * @return string|null
     */
    private function getFriendResourceId(int $friendStatus): ?string
    {
        return optional(
            app(FriendDriver::class)->getFriendResource(
                $friendStatus,
                $this->provider
            )
        )->id;
    }
}
