<?php

namespace Tests\Feature;

use App\Services\FileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected $fileService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileService = app(FileService::class);
    }

    public function testUploadFileToGroup()
    {
        // Mock necessary dependencies
        $mockGroup = \Mockery::mock(\App\Models\Group::class);
        $mockFile = \Mockery::mock(\App\Models\File::class);

        $this->fileService->shouldReceive('groupModel')->andReturn($mockGroup);
        $this->fileService->shouldReceive('fileModel')->andReturn($mockFile);

        // Call the method you want to test
        $result = $this->fileService->uploadFileToGroup([
            'group_id' => 1,
            'user_id' => 8,
            'file' => new \Symfony\Component\HttpFoundation\File\UploadedFile(
                fopen(__FILE__, 'r'),
                'test.jpg',
                'image/jpeg',
                null,
                true
            ),
        ]);

        // Assert the result
        $this->assertNotNull($result);
    }
}
