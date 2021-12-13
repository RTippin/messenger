<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Services\FileService;

class PurgeBots extends BaseMessengerAction
{
    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * PurgeBots constructor.
     *
     * @param  FileService  $fileService
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Loop through the collection of bots and remove their
     * storage directory files, then force delete the bot
     * itself from database.
     *
     * @param  Collection  $bots
     * @return $this
     */
    public function execute(Collection $bots): self
    {
        $bots->each(fn (Bot $bot) => $this->purge($bot));

        return $this;
    }

    /**
     * @param  Bot  $bot
     * @return void
     */
    private function purge(Bot $bot): void
    {
        $this->destroyBotDirectory($bot);

        $this->destroyBot($bot);
    }

    /**
     * @param  Bot  $bot
     * @return void
     */
    private function destroyBotDirectory(Bot $bot): void
    {
        $this->fileService
            ->setDisk($bot->getStorageDisk())
            ->setDirectory($bot->getStorageDirectory())
            ->destroyDirectory();
    }

    /**
     * @param  Bot  $bot
     * @return void
     */
    private function destroyBot(Bot $bot): void
    {
        $bot->forceDelete();
    }
}
