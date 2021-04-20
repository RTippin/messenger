<?php

namespace RTippin\Messenger\Services;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Exceptions\FileServiceException;

class FileService
{
    /**
     * @var string|null
     */
    private ?string $type = null;

    /**
     * @var string|null
     */
    private ?string $disk = 'public';

    /**
     * @var string|null
     */
    private ?string $directory = null;

    /**
     * @var string|null
     */
    private ?string $name = null;

    /**
     * @var FilesystemManager
     */
    private FilesystemManager $filesystemManager;

    /**
     * FileService constructor.
     *
     * @param FilesystemManager $filesystemManager
     */
    public function __construct(FilesystemManager $filesystemManager)
    {
        $this->filesystemManager = $filesystemManager;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $disk
     * @return $this
     */
    public function setDisk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * @param string $directory
     * @return $this
     */
    public function setDirectory(string $directory): self
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param UploadedFile $file
     * @return $this
     * @throws FileServiceException
     */
    public function upload(UploadedFile $file): self
    {
        $this->fileUpload($file);

        return $this;
    }

    /**
     * @param string $file
     * @return bool
     */
    public function destroy(string $file): bool
    {
        if ($this->filesystemManager->disk($this->disk)->exists($file)) {
            return $this->filesystemManager
                ->disk($this->disk)
                ->delete($file);
        }

        return false;
    }

    /**
     * @return bool
     */
    public function destroyDirectory(): bool
    {
        if ($this->filesystemManager->disk($this->disk)->exists($this->directory)) {
            return $this->filesystemManager
                ->disk($this->disk)
                ->deleteDirectory($this->directory);
        }

        return false;
    }

    /**
     * @return string
     * @throws FileServiceException
     */
    public function getName(): string
    {
        if (is_null($this->name)) {
            $this->throwFileServiceException('File name was not set.');
        }

        return $this->name;
    }

    /**
     * @param UploadedFile $file
     * @return void
     * @throws FileServiceException
     */
    private function fileUpload(UploadedFile $file): void
    {
        $this->name = $this->nameFile($file);

        if (! $this->storeFile($file)) {
            $this->throwFileServiceException('File failed to upload.');
        }
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    private function nameFile(UploadedFile $file): string
    {
        $extension = $file->guessExtension();

        if (! $extension) {
            $this->throwFileServiceException('File extension was not found.');
        }

        if (! is_null($this->name)) {
            return "{$this->getName()}.$extension";
        }

        switch ($this->type) {
            case 'image':
                return uniqid('img_', true).".$extension";
            case 'document':
            case 'audio':
                return $this->getOriginalName($file).'_'.now()->timestamp.".$extension";
        }

        return $this->getOriginalName($file).$extension;
    }

    /**
     * @param UploadedFile $file
     * @return string
     */
    private function getOriginalName(UploadedFile $file): string
    {
        return pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    }

    /**
     * @param UploadedFile $file
     * @return bool
     */
    private function storeFile(UploadedFile $file): bool
    {
        return $file->storeAs($this->directory, $this->name, [
            'disk' => $this->disk,
        ]);
    }


    /**
     * @param string $message
     * @throws FileServiceException
     */
    private function throwFileServiceException(string $message): void
    {
        throw new FileServiceException($message);
    }
}
