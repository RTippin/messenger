<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\DataTransferObjects\ResolvedBotHandlerDTO;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\BotHandlerResolverService;

class InstallPackagedBot extends BaseMessengerAction
{
    /**
     * @var MessengerBots
     */
    private MessengerBots $bots;

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
     * @var BotHandlerResolverService
     */
    private BotHandlerResolverService $resolver;

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
     * InstallPackagedBot constructor.
     *
     * @param  Messenger  $messenger
     * @param  MessengerBots  $bots
     * @param  Dispatcher  $dispatcher
     * @param  DatabaseManager  $database
     * @param  BotHandlerResolverService  $resolver
     * @param  StoreBot  $storeBot
     * @param  StoreBotAction  $storeBotAction
     * @param  StoreBotAvatar  $storeBotAvatar
     */
    public function __construct(Messenger $messenger,
                                MessengerBots $bots,
                                Dispatcher $dispatcher,
                                DatabaseManager $database,
                                BotHandlerResolverService $resolver,
                                StoreBot $storeBot,
                                StoreBotAction $storeBotAction,
                                StoreBotAvatar $storeBotAvatar)
    {
        $this->messenger = $messenger;
        $this->bots = $bots;
        $this->dispatcher = $dispatcher;
        $this->database = $database;
        $this->resolver = $resolver;
        $this->storeBot = $storeBot;
        $this->storeBotAction = $storeBotAction;
        $this->storeBotAvatar = $storeBotAvatar;
    }

    public function execute(Thread $thread,
                            MessengerProvider $owner,
                            PackagedBotDTO $package): self
    {
        $this->messenger->setScopedProvider($owner);
        $this->setThread($thread)->silenceActions();
        $this->package = $package;

        return $this;
    }

    private function silenceActions(): void
    {
        $this->storeBot->withoutDispatches();
        $this->storeBotAvatar->withoutDispatches();
        $this->storeBotAction->withoutDispatches();
    }

    private function storeBot()
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

    private function storeBotAvatar()
    {
        if ($this->package->shouldInstallAvatar
            && $this->messenger->isBotAvatarEnabled()) {
            $this->storeBotAvatar->execute(
                $this->getBot(),
                new UploadedFile($this->package->avatar, 'avatar.png')
            );
        }
    }

    private function storeBotAction(ResolvedBotHandlerDTO $handler)
    {
        $this->storeBotAction->execute(
            $this->getThread(),
            $this->getBot(),
            $handler
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
