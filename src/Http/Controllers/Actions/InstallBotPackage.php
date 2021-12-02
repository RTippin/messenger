<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\Events\InstallPackagedBotEvent;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Http\Request\InstallBotRequest;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;

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
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @param  Messenger  $messenger
     * @param  MessengerBots  $bots
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(Messenger $messenger,
                                MessengerBots $bots,
                                Dispatcher $dispatcher)
    {
        $this->messenger = $messenger;
        $this->bots = $bots;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param  InstallBotRequest  $request
     * @param  Thread  $thread
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function __invoke(InstallBotRequest $request, Thread $thread): JsonResponse
    {
        $this->authorize('create', [
            Bot::class,
            $thread,
        ]);

        $package = $this->bots->getPackagedBots(
            $request->validated()['alias']
        );

        $this->authorizePackagedBot($package);

        $this->fireInstallEvent($thread, $package);

        return new JsonResponse([
            'message' => 'success',
        ]);
    }

    /**
     * @param  PackagedBotDTO  $package
     * @throws AuthorizationException|BotException
     */
    private function authorizePackagedBot(PackagedBotDTO $package): void
    {
        if ($package->shouldAuthorize
            && ! $this->bots->initializePackagedBot($package->class)->authorize()) {
            throw new AuthorizationException('Not authorized to install that bot package.');
        }
    }

    /**
     * @param  Thread  $thread
     * @param  PackagedBotDTO  $package
     */
    private function fireInstallEvent(Thread $thread, PackagedBotDTO $package): void
    {
        $this->dispatcher->dispatch(new InstallPackagedBotEvent(
            $thread,
            $this->messenger->getProvider(true),
            $package,
        ));
    }
}
