<?php

namespace RTippin\Messenger\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Support\PackagedBot;

class PackagedBotDTO implements Arrayable
{
    /**
     * @var string|PackagedBot
     */
    public string $class;

    /**
     * @var string
     */
    public string $name;

    /**
     * @var string
     */
    public string $description;

    /**
     * @var string|null
     */
    public ?string $avatar;

    /**
     * @var string
     */
    public string $previewAvatarRoute;

    /**
     * @var bool
     */
    public bool $shouldInstallAvatar;

    /**
     * @var Collection
     */
    public Collection $installs;

    /**
     * @param  string  $packagedBot
     *
     * @see PackagedBot
     */
    public function __construct(string $packagedBot)
    {
        /** @var PackagedBot $packagedBot */
        $settings = $packagedBot::getSettings();

        $this->class = $packagedBot;
        $this->name = $settings['name'];
        $this->description = $settings['description'];
        $this->avatar = $settings['avatar'] ?? null;
        $this->shouldInstallAvatar = ! is_null($this->avatar);
//        $this->previewAvatarRoute = Helpers::route('assets.messenger.packaged-bot.avatar.render', [
//
//        ]);
        $this->previewAvatarRoute = '';

        $this->registerHandlers(array_keys($packagedBot::installs()));

        $this->installs = $this->generateInstalls($packagedBot::installs());
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'avatar' => $this->previewAvatarRoute,
            'installs' => $this->installs->map(
                fn (array $install) => $install['handler']
            )->toArray(),
        ];
    }

    /**
     * @param  array  $handlers
     */
    private function registerHandlers(array $handlers): void
    {
        $registeredHandlers = MessengerBots::getHandlerClasses();

        $registers = (new Collection($handlers))->reject(
            fn (string $handler) => in_array($handler, $registeredHandlers)
        )->toArray();

        MessengerBots::registerHandlers($registers);
    }

    /**
     * @param  array  $installs
     * @return Collection
     */
    private function generateInstalls(array $installs): Collection
    {
        return (new Collection($installs))->transform(fn (array $data, string $handler) => [
                'handler' => MessengerBots::getHandlersDTO($handler),
                'data' => $data,
        ])->values();
    }
}
