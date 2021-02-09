<?php

namespace RTippin\Messenger\Actions\Messages;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Services\FileService;

class PurgeImageMessages extends BaseMessengerAction
{
    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * PurgeImageMessages constructor.
     *
     * @param FileService $fileService
     */
    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Loop through the collection of image messages and remove
     * the image from storage, then force delete message itself
     * from database.
     *
     * @param mixed ...$parameters
     * @var Collection[0]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        /** @var Collection $images */
        $images = $parameters[0];

        $images->each(fn (Message $image) => $this->purge($image));

        return $this;
    }

    /**
     * @param Message $image
     */
    private function purge(Message $image): void
    {
        $this->destroyImage($image);

        $this->destroyMessage($image);
    }

    /**
     * @param Message $image
     */
    private function destroyImage(Message $image): void
    {
        $this->fileService
            ->setDisk($image->getStorageDisk())
            ->destroy($image->getImagePath());
    }

    /**
     * @param Message $image
     */
    private function destroyMessage(Message $image): void
    {
        $image->forceDelete();
    }
}
