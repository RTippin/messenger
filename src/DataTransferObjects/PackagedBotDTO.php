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
    public string $alias;

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
     * @var int
     */
    public int $cooldown;

    /**
     * @var bool
     */
    public bool $isEnabled;

    /**
     * @var bool
     */
    public bool $shouldHideActions;

    /**
     * @var string
     */
    public string $previewAvatarRoute;

    /**
     * @var bool
     */
    public bool $shouldInstallAvatar;

    /**
     * @var bool
     */
    public bool $shouldAuthorize;

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
        $this->alias = $settings['alias'];
        $this->name = $settings['name'];
        $this->description = $settings['description'];
        $this->avatar = $settings['avatar'] ?? null;
        $this->cooldown = $settings['cooldown'] ?? 0;
        $this->isEnabled = $settings['enabled'] ?? true;
        $this->shouldHideActions = $settings['hide_actions'] ?? false;
        $this->shouldInstallAvatar = ! is_null($this->avatar);
        $this->shouldAuthorize = method_exists($packagedBot, 'authorize');
//        $this->previewAvatarRoute = Helpers::route('assets.messenger.packaged-bot.avatar.render', [
//
//        ]);
        $this->previewAvatarRoute = '';

        $this->registerHandlers($packagedBot::installs());

        $this->installs = $this->generateInstalls($packagedBot::installs());
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'alias' => $this->alias,
            'name' => $this->name,
            'description' => $this->description,
            'avatar' => $this->previewAvatarRoute,
            'installs' => $this->formatInstallsToArray(),
        ];
    }

    /**
     * @param  array  $installs
     */
    private function registerHandlers(array $installs): void
    {
        $registeredHandlers = MessengerBots::getHandlerClasses();
        $register = [];

        foreach ($installs as $key => $value) {
            $handler = is_string($key) ? $key : $value;

            if (! in_array($installs, $registeredHandlers)) {
                $register[] = $handler;
            }
        }

        MessengerBots::registerHandlers($register);
    }

    /**
     * @param  array  $installs
     * @return Collection
     */
    private function generateInstalls(array $installs): Collection
    {
        return Collection::make($installs)->map(fn ($value, $key) => [
            'handler' => MessengerBots::getHandlers(is_string($key) ? $key : $value),
            'data' => is_string($key) ? $value : null,
        ])->values();
    }

    /**
     * @return array
     */
    private function formatInstallsToArray(): array
    {
        return $this->installs
            ->map(fn (array $install) => $install['handler'])
            ->sortBy('name')
            ->values()
            ->toArray();
    }
}
