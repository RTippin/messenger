<?php

namespace RTippin\Messenger\DataTransferObjects;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Helpers;
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
     * @var string
     */
    public string $avatarExtension;

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
     * @var bool
     */
    public bool $shouldInstallAvatar;

    /**
     * @var bool
     */
    public bool $shouldAuthorize;

    /**
     * @var Collection|PackagedBotInstallDTO[]
     */
    public Collection $installs;

    /**
     * @var Collection|PackagedBotInstallDTO[]
     */
    public Collection $canInstall;

    /**
     * @var Collection|PackagedBotInstallDTO[]
     */
    public Collection $alreadyInstalled;

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
        $this->avatarExtension = $this->getAvatarExtension();
        $this->shouldAuthorize = method_exists($packagedBot, 'authorize');
        $this->canInstall = Collection::make();
        $this->alreadyInstalled = Collection::make();

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
            'avatar' => $this->generateAvatarPreviewRoutes(),
            'installs' => $this->sortCanInstall(),
            'already_installed' => $this->sortAlreadyInstalled(),
        ];
    }

    /**
     * Apply all authorization and unique checks against
     * the handlers defined to be installed.
     *
     * @param  Thread|null  $thread
     * @return void
     */
    public function applyInstallFilters(?Thread $thread = null): void
    {
        $authorized = $this->filterAuthorizedInstalls();

        $unique = ! is_null($thread) ? BotAction::getUniqueHandlersInThread($thread) : [];

        $this->canInstall = $authorized->reject(
            fn (PackagedBotInstallDTO $install) => in_array($install->handler->class, $unique)
        )->values();

        $this->alreadyInstalled = $authorized->filter(
            fn (PackagedBotInstallDTO $install) => in_array($install->handler->class, $unique)
        )->values();
    }

    /**
     * Register any defined bot handlers that are not already registered.
     *
     * @param  array  $installs
     */
    private function registerHandlers(array $installs): void
    {
        $registeredHandlers = MessengerBots::getHandlerClasses();
        $register = [];

        foreach ($installs as $key => $value) {
            $handler = is_string($key) ? $key : $value;

            if (! in_array($handler, $registeredHandlers)) {
                $register[] = $handler;
            }
        }

        MessengerBots::registerHandlers($register);
    }

    /**
     * Transform the install's array to a collection of PackagedBotInstallDTO's.
     *
     * @param  array  $installs
     * @return Collection
     */
    private function generateInstalls(array $installs): Collection
    {
        return Collection::make($installs)->map(
            fn ($value, $key) => new PackagedBotInstallDTO(
                is_string($key) ? $key : $value,
                is_string($key) ? $value : []
            )
        )->values();
    }

    /**
     * @return string
     */
    private function getAvatarExtension(): string
    {
        return pathinfo(
            $this->shouldInstallAvatar
                ? $this->avatar
                : Messenger::getDefaultBotAvatar(),
            PATHINFO_EXTENSION
        );
    }

    /**
     * @return array
     */
    private function generateAvatarPreviewRoutes(): array
    {
        return [
            'sm' => $this->makeAvatarPreviewRoute('sm'),
            'md' => $this->makeAvatarPreviewRoute('md'),
            'lg' => $this->makeAvatarPreviewRoute('lg'),
        ];
    }

    /**
     * @param  string  $size
     * @return string
     */
    private function makeAvatarPreviewRoute(string $size): string
    {
        return Helpers::route('assets.messenger.bot-package.avatar.render', [
            'size' => $size,
            'alias' => $this->alias,
            'image' => 'avatar.'.$this->avatarExtension,
        ]);
    }

    /**
     * @return Collection
     */
    private function filterAuthorizedInstalls(): Collection
    {
        $authorized = MessengerBots::getAuthorizedHandlers();

        return $this->installs
            ->filter(fn (PackagedBotInstallDTO $install) => $authorized->contains($install->handler))
            ->values();
    }

    /**
     * @return array
     */
    private function sortCanInstall(): array
    {
        return $this->canInstall
            ->sortBy(fn (PackagedBotInstallDTO $install) => $install->handler->name)
            ->values()
            ->toArray();
    }

    /**
     * @return array
     */
    private function sortAlreadyInstalled(): array
    {
        return $this->alreadyInstalled
            ->sortBy(fn (PackagedBotInstallDTO $install) => $install->handler->name)
            ->values()
            ->toArray();
    }
}
