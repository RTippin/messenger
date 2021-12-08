<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\Events\PackagedBotInstalledEvent;
use RTippin\Messenger\Events\PackagedBotInstallFailedEvent;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\InvalidProviderException;
use RTippin\Messenger\Messenger;
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
     * @var StoreBot
     */
    private StoreBot $storeBot;

    /**
     * @var StoreBotAction
     */
    private StoreBotAction $storeBotAction;

    /**
     * @var StoreBotAvatar
     */
    private StoreBotAvatar $storeBotAvatar;

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
     * @param  StoreBot  $storeBot
     * @param  StoreBotAction  $storeBotAction
     * @param  StoreBotAvatar  $storeBotAvatar
     */
    public function __construct(Messenger $messenger,
                                Dispatcher $dispatcher,
                                DatabaseManager $database,
                                PackagedBotResolverService $resolver,
                                StoreBot $storeBot,
                                StoreBotAction $storeBotAction,
                                StoreBotAvatar $storeBotAvatar)
    {
        $this->messenger = $messenger;
        $this->dispatcher = $dispatcher;
        $this->database = $database;
        $this->resolver = $resolver;
        $this->storeBot = $storeBot;
        $this->storeBotAction = $storeBotAction;
        $this->storeBotAvatar = $storeBotAvatar;
    }

    /**
     * @param  Thread  $thread
     * @param  MessengerProvider  $owner
     * @param  PackagedBotDTO  $package
     * @return $this
     *
     * @throws InvalidProviderException
     */
    public function execute(Thread $thread,
                            MessengerProvider $owner,
                            PackagedBotDTO $package): self
    {
        $this->messenger->setScopedProvider($owner);
        $this->package = $package;
        $this->resolvedHandlers = $this->resolver->resolve(
            $thread,
            $package
        );

        $this->setThread($thread)
            ->silenceActions()
            ->process();

        return $this;
    }

    /**
     * @return $this
     */
    private function silenceActions(): self
    {
        $this->storeBot->withoutDispatches();
        $this->storeBotAvatar->withoutDispatches();
        $this->storeBotAction->withoutDispatches();

        return $this;
    }

    /**
     * @return void
     */
    private function process(): void
    {
        try {
            $this->isChained()
                ? $this->performActions()
                : $this->database->transaction(fn () => $this->performActions());

            $this->fireInstalledEvent();
        } catch (Throwable $e) {
            $this->fireFailedEvent($e);
        }
    }

    /**
     * @return void
     *
     * @throws FeatureDisabledException|BotException
     */
    private function performActions(): void
    {
        $this->storeBot();

        $this->resolvedHandlers->each(
            fn (ResolvedBotHandlerDTO $handler) => $this->storeBotAction($handler)
        );

        $this->storeBotAvatar();
    }

    /**
     * @throws FeatureDisabledException
     */
    private function storeBot(): void
    {
        $this->setBot(
            $this->storeBot->execute($this->getThread(), [
                'enabled' => $this->package->isEnabled,
                'name' => $this->package->name,
                'cooldown' => $this->package->cooldown,
                'hide_actions' => $this->package->shouldHideActions,
            ])->getBot()
        );
    }

    /**
     * @throws FeatureDisabledException
     */
    private function storeBotAvatar(): void
    {
        if ($this->package->shouldInstallAvatar
            && $this->messenger->isBotAvatarEnabled()) {
            $this->storeBotAvatar->execute(
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
     * @param  ResolvedBotHandlerDTO  $handler
     * @return void
     *
     * @throws BotException|FeatureDisabledException
     */
    private function storeBotAction(ResolvedBotHandlerDTO $handler): void
    {
        $this->storeBotAction->execute(
            $this->getThread(),
            $this->getBot(),
            $handler,
            true
        );
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

    /**
     * @param  Throwable  $e
     * @return void
     */
    private function fireFailedEvent(Throwable $e): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new PackagedBotInstallFailedEvent(
                $e,
                $this->package,
                $this->getThread(true),
                $this->messenger->getProvider(true)
            ));
        }
    }
}
