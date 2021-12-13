<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\Events\PackagedBotInstalledEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Resources\BotResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\PackagedBotResolverService;
use Throwable;

class InstallPackagedBot extends BaseMessengerAction
{
    /**
     * @var DatabaseManager
     */
    private DatabaseManager $database;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var PackagedBotResolverService
     */
    private PackagedBotResolverService $resolver;

    /**
     * @var PackagedBotDTO
     */
    private PackagedBotDTO $package;

    /**
     * @var Collection
     */
    private Collection $resolvedHandlers;

    /**
     * InstallPackagedBot constructor.
     *
     * @param  Messenger  $messenger
     * @param  Dispatcher  $dispatcher
     * @param  DatabaseManager  $database
     * @param  PackagedBotResolverService  $resolver
     */
    public function __construct(Messenger $messenger,
                                Dispatcher $dispatcher,
                                DatabaseManager $database,
                                PackagedBotResolverService $resolver)
    {
        $this->messenger = $messenger;
        $this->dispatcher = $dispatcher;
        $this->database = $database;
        $this->resolver = $resolver;
    }

    /**
     * @param  Thread  $thread
     * @param  PackagedBotDTO  $package
     * @return $this
     *
     * @throws Throwable|FeatureDisabledException
     */
    public function execute(Thread $thread, PackagedBotDTO $package): self
    {
        $this->bailIfDisabled();

        $this->package = $package;
        $this->resolvedHandlers = $this->resolver->resolve(
            $thread,
            $package
        );

        $this->setThread($thread)
            ->process()
            ->generateResource()
            ->clearActionsCache()
            ->fireInstalledEvent();

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function bailIfDisabled(): void
    {
        if (! $this->messenger->isBotsEnabled()) {
            throw new FeatureDisabledException('Bots are currently disabled.');
        }
    }

    /**
     * @return void
     *
     * @throws Throwable
     */
    private function process(): self
    {
        $this->isChained()
            ? $this->handle()
            : $this->database->transaction(fn () => $this->handle());

        return $this;
    }

    /**
     * @return void
     */
    private function handle(): void
    {
        $this->storeBot();

        $this->resolvedHandlers->each(
            fn (ResolvedBotHandlerDTO $handler) => $this->storeBotAction($handler)
        );

        $this->storeBotAvatar();
    }

    /**
     * @return void
     */
    private function storeBot(): void
    {
        $bot = $this->chain(StoreBot::class)
            ->withoutDispatches()
            ->execute($this->getThread(), [
                'enabled' => $this->package->isEnabled,
                'name' => $this->package->name,
                'cooldown' => $this->package->cooldown,
                'hide_actions' => $this->package->shouldHideActions,
            ])
            ->getBot();

        $this->setBot($bot);
    }

    /**
     * @param  ResolvedBotHandlerDTO  $handler
     * @return void
     */
    private function storeBotAction(ResolvedBotHandlerDTO $handler): void
    {
        $this->chain(StoreBotAction::class)
            ->withoutDispatches()
            ->execute(
                $this->getThread(),
                $this->getBot(),
                $handler,
                true
            );
    }

    /**
     * @return void
     */
    private function storeBotAvatar(): void
    {
        if ($this->package->shouldInstallAvatar
            && $this->messenger->isBotAvatarEnabled()) {
            $this->chain(StoreBotAvatar::class)
                ->withoutDispatches()
                ->execute(
                    $this->getBot(),
                    $this->generateUploadedFile(),
                );
        }
    }

    /**
     * @return UploadedFile
     */
    private function generateUploadedFile(): UploadedFile
    {
        return new UploadedFile(
            $this->package->avatar,
            'avatar.'.$this->package->avatarExtension
        );
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new BotResource(
            $this->getBot()
        ));

        return $this;
    }

    /**
     * @return $this
     */
    private function clearActionsCache(): self
    {
        BotAction::clearActionsCacheForThread($this->getThread()->id);

        return $this;
    }

    /**
     * @return void
     */
    private function fireInstalledEvent(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new PackagedBotInstalledEvent(
                $this->package,
                $this->getThread(true),
                $this->messenger->getProvider(true)
            ));
        }
    }
}
