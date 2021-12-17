<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Bots\InstallPackagedBot;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Http\Request\InstallBotRequest;
use RTippin\Messenger\Http\Resources\BotResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;
use Throwable;

class InstallBotPackage
{
    use AuthorizesRequests;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var MessengerBots
     */
    private MessengerBots $bots;

    /**
     * @var InstallPackagedBot
     */
    private InstallPackagedBot $installer;

    /**
     * @param  Messenger  $messenger
     * @param  MessengerBots  $bots
     * @param  InstallPackagedBot  $installer
     */
    public function __construct(Messenger $messenger,
                                MessengerBots $bots,
                                InstallPackagedBot $installer)
    {
        $this->messenger = $messenger;
        $this->bots = $bots;
        $this->installer = $installer;
    }

    /**
     * @param  InstallBotRequest  $request
     * @param  Thread  $thread
     * @return BotResource
     *
     * @throws AuthorizationException|Throwable
     */
    public function __invoke(InstallBotRequest $request, Thread $thread): BotResource
    {
        $this->authorize('create', [
            Bot::class,
            $thread,
        ]);

        $package = $this->bots->getPackagedBot(
            $request->validated()['alias']
        );

        $this->bailIfAuthorizationFails($package);

        return $this->installer->execute(
            $thread,
            $package
        )->getJsonResource();
    }

    /**
     * @param  PackagedBotDTO  $package
     *
     * @throws AuthorizationException
     * @throws BotException
     */
    private function bailIfAuthorizationFails(PackagedBotDTO $package): void
    {
        if ($package->shouldAuthorize
            && ! $this->bots->initializePackagedBot($package->class)->authorize()) {
            throw new AuthorizationException('Not authorized to install that bot package.');
        }
    }
}
