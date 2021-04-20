<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Services\FileService;

class FileServiceTest extends TestCase
{
    private FileService $fileService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileService = app(FileService::class);
        Storage::fake('messenger');
    }

    /** @test */
    public function it_stores_and_renames_image()
    {
        $image = UploadedFile::fake()->image('test.jpg');

        $name = $this->fileService->setDisk('messenger')
            ->setType('image')
            ->upload($image)
            ->getName();

        $this->assertStringContainsString('img_', $name);
        Storage::disk('messenger')->assertExists($name);
    }

    /** @test */
    public function it_stores_and_renames_document()
    {
        $document = UploadedFile::fake()->create('test_123_rev_2.pdf', 500, 'application/pdf');

        $name = $this->fileService->setDisk('messenger')
            ->setType('document')
            ->upload($document)
            ->getName();

        $this->assertNotSame('test_123_rev_2.pdf', $name);
        $this->assertStringContainsString('test_123_rev_2', $name);
        Storage::disk('messenger')->assertExists($name);
    }

    /** @test */
    public function it_stores_image_in_specified_directory()
    {
        $image = UploadedFile::fake()->image('test.jpg');

        $name = $this->fileService->setDisk('messenger')
            ->setType('image')
            ->setDirectory('test/1234')
            ->upload($image)
            ->getName();

        Storage::disk('messenger')->assertExists('test/1234/'.$name);
    }

    /** @test */
    public function it_can_name_file_with_given_name()
    {
        $document = UploadedFile::fake()->create('test_123_rev_2.pdf', 500, 'application/pdf');

        $name = $this->fileService->setDisk('messenger')
            ->setName('test_renamed')
            ->upload($document)
            ->getName();

        $this->assertSame('test_renamed.pdf', $name);
        Storage::disk('messenger')->assertExists($name);
    }

    /** @test */
    public function it_throws_exception_if_upload_extension_not_found()
    {
        $this->expectException(FileServiceException::class);
        $this->expectExceptionMessage('File extension was not found.');

        $badFile = UploadedFile::fake()->create('undefined', 0, 'undefined');

        $this->fileService->setDisk('messenger')->upload($badFile);
    }

    /** @test */
    public function it_throws_exception_if_name_null()
    {
        $this->expectException(FileServiceException::class);
        $this->expectExceptionMessage('File name was not set.');

        $this->fileService->getName();
    }
}
