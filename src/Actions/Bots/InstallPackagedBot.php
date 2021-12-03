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

        $this->setThread($thread);

        $this->silenceActions();

        $this->package = $package;

        $this->resolvedHandlers = $this->resolver->resolve(
            $thread,
            $package
        );

        $this->process();

        return $this;
    }

    /**
     * @return void
     */
    private function silenceActions(): void
    {
        $this->storeBot->withoutDispatches();
        $this->storeBotAvatar->withoutDispatches();
        $this->storeBotAction->withoutDispatches();
    }

    /**
     * @return void
     */
    private function process()
    {
        try {
            $this->isChained()
                ? $this->performActions()
                : $this->database->transaction(fn () => $this->performActions());
        } catch (Throwable $e) {
            //Fire events.
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

        $this->storeBotAvatar();

        $this->resolvedHandlers->each(
            fn (ResolvedBotHandlerDTO $handler) => $this->storeBotAction($handler)
        );
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
    private function storeBotAction(ResolvedBotHandlerDTO $handler)
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
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
//            $this->dispatcher->dispatch();
        }
    }
}
