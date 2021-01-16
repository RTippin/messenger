<?php

namespace RTippin\Messenger\Actions\Messenger;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\FileService;
use RTippin\Messenger\Messenger;
use Symfony\Component\HttpKernel\Exception\HttpException;

class StoreMessengerAvatar extends MessengerAvatarAction
{
    /**
     * StoreMessengerAvatar constructor.
     *
     * @param Messenger $messenger
     * @param FileService $fileService
     */
    public function __construct(Messenger $messenger,
                                FileService $fileService)
    {
        parent::__construct($messenger, $fileService);
    }

    /**
     * @param mixed ...$parameters
     * @var UploadedFile[0]['image']
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $file = $this->upload($parameters[0]['image']);

        $this->removeOldIfExist()
            ->updateProviderAvatar($file);

        return $this;
    }

    /**
     * @param UploadedFile $file
     * @return string
     * @throws HttpException
     */
    private function upload(UploadedFile $file): ?string
    {
        return $this->fileService
            ->setType('image')
            ->setDisk($this->messenger->getAvatarStorage('disk'))
            ->setDirectory($this->getDirectory())
            ->upload($file)
            ->getName();
    }
}
