<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Events\Dispatcher;
use RTippin\Messenger\Events\InstallPackagedBotEvent;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\BotActionMessageHandler;
use RTippin\Messenger\Jobs\ProcessPackagedBotInstall;

class BotSubscriber
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param  Dispatcher  $events
     * @return void
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(NewMessageEvent::class, [BotSubscriber::class, 'newMessage']);
        $events->listen(InstallPackagedBotEvent::class, [BotSubscriber::class, 'installBotPackage']);
    }

    /**
     * @param  NewMessageEvent  $event
     */
    public function newMessage(NewMessageEvent $event): void
    {
        if ($this->shouldDispatchMessageHandler($event)) {
            Messenger::getBotSubscriber('queued')
                ? BotActionMessageHandler::dispatch($event)->onQueue(Messenger::getBotSubscriber('channel'))
                : BotActionMessageHandler::dispatchSync($event);
        }
    }

    /**
     * @param  InstallPackagedBotEvent  $event
     */
    public function installBotPackage(InstallPackagedBotEvent $event): void
    {
        if (Messenger::getBotSubscriber('enabled')) {
            Messenger::getBotSubscriber('queued')
                ? ProcessPackagedBotInstall::dispatch($event)->onQueue(Messenger::getBotSubscriber('channel'))
                : ProcessPackagedBotInstall::dispatchSync($event);
        }
    }

    /**
     * @param  NewMessageEvent  $event
     * @return bool
     */
    private function shouldDispatchMessageHandler(NewMessageEvent $event): bool
    {
        return Messenger::getBotSubscriber('enabled')
            && $event->message->isText()
            && $event->message->notFromBot()
            && $event->thread->hasBotsFeature();
    }
}
