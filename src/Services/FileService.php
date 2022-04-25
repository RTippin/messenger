<?php

namespace RTippin\Messenger\Services;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RTippin\Messenger\Exceptions\FileServiceException;

class FileService
{
    /**
     * @deprecated removing in v2
     */
    const TYPE_IMAGE = 'image';

    /**
     * @deprecated removing in v2
     */
    const TYPE_DOCUMENT = 'document';

    /**
     * @deprecated removing in v2
     */
    const TYPE_AUDIO = 'audio';

    /**
     * @deprecated removing in v2
     */
    const TYPE_VIDEO = 'video';

    /**
     * @deprecated removing in v2
     */
    const TYPE_DEFAULT = null;

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
     * @param  FilesystemManager  $filesystemManager
     */
    public function __construct(FilesystemManager $filesystemManager)
    {
        $this->filesystemManager = $filesystemManager;
    }

    /**
     * @deprecated Removing in v2
     *
     * @param  string  $type
     * @return $this
     */
    public function setType(string $type): self
    {
        return $this;
    }

    /**
     * @param  string  $disk
     * @return $this
     */
    public function setDisk(string $disk): self
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * @param  string  $directory
     * @return $this
     */
    public function setDirectory(string $directory): self
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * @param  string  $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param  UploadedFile  $file
     * @return string
     *
     * @throws FileServiceException
     */
    public function upload(UploadedFile $file): string
    {
        $name = $this->nameFile($file);

        if (! $this->storeFile($file, $name)) {
            $this->throwFileServiceException('File failed to upload.');
        }

        $this->reset();

        return $name;
    }

    /**
     * @param  string  $file
     * @return bool
     */
    public function destroy(string $file): bool
    {
        if ($this->filesystemManager->disk($this->disk)->exists($file)) {
            $destroy = $this->filesystemManager
                ->disk($this->disk)
                ->delete($file);

            $this->reset();

            return $destroy;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function destroyDirectory(): bool
    {
        if ($this->filesystemManager->disk($this->disk)->exists($this->directory)) {
            $destroy = $this->filesystemManager
                ->disk($this->disk)
                ->deleteDirectory($this->directory);

            $this->reset();

            return $destroy;
        }

        return false;
    }

    /**
     * @param  UploadedFile  $file
     * @return string
     */
    private function nameFile(UploadedFile $file): string
    {
        $extension = $file->guessExtension();

        if (! $extension) {
            $this->throwFileServiceException('File extension was not found.');
        }

        if (! is_null($this->name)) {
            return "$this->name.$extension";
        }

        $originalName = preg_replace(
            '/[^\w\-]/',
            '',
            $this->getOriginalName($file)
        );

        return "{$originalName}_".Str::uuid()->toString().".$extension";
    }

    /**
     * @param  UploadedFile  $file
     * @return string
     */
    private function getOriginalName(UploadedFile $file): string
    {
        return pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    }

    /**
     * @param  UploadedFile  $file
     * @param  string  $name
     * @return bool
     */
    private function storeFile(UploadedFile $file, string $name): bool
    {
        return $file->storeAs($this->directory, $name, [
            'disk' => $this->disk,
        ]);
    }

    /**
     * @param  string  $message
     *
     * @throws FileServiceException
     */
    private function throwFileServiceException(string $message): void
    {
        throw new FileServiceException($message);
    }

    /**
     * After an upload, reset the properties that were set.
     *
     * @return void
     */
    private function reset(): void
    {
        $this->disk = null;
        $this->directory = null;
        $this->name = null;
    }
}
