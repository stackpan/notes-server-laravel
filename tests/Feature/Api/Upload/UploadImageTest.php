<?php

namespace Tests\Feature\Api\Upload;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Mockery\MockInterface;
use Tests\TestCase;

class UploadImageTest extends TestCase
{

    public function testUploadSuccess()
    {
        Storage::fake();
        $file = UploadedFile::fake()->image('dummy.jpg');

        $response = $this->post('/api/upload/images', [
            'data' => $file,
        ]);

        $response
            ->assertCreated()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'success')
                ->whereType('data.fileLocation', 'string')
            );
    }

    public function testUploadWithNonImageFile()
    {
        Storage::fake();

        $file = UploadedFile::fake()->create('dummy.pdf', 64);

        $response = $this->post('/api/upload/images', [
            'data' => $file,
        ]);

        $response
            ->assertBadRequest()
            ->assertHeader('Content-Type', 'application/json; charset=utf-8')
            ->assertJson(fn(AssertableJson $json) => $json
                ->where('status', 'fail')
                ->has('message')
            );
    }

}
