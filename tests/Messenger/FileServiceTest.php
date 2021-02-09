<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use RTippin\Messenger\Exceptions\UploadFailedException;
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
    public function service_has_null_name_when_no_action_took_place()
    {
        $this->assertNull($this->fileService->getName());
    }

    /** @test */
    public function service_stores_image_and_renames_file()
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
    public function service_stores_document_and_renames_file()
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
    public function service_stores_image_in_specified_directory()
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
    public function service_names_file_given_name_if_supplied()
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
    public function service_throws_exception_when_upload_fails()
    {
        $this->expectException(UploadFailedException::class);

        $this->expectExceptionMessage('File failed to upload.');

        $badFile = UploadedFile::fake()->create('undefined', 0, 'undefined');

        $this->fileService->setDisk('messenger')->upload($badFile);
    }
}
