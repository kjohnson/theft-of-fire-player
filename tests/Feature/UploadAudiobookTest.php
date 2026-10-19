<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadAudiobookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('s3');

        config(['audiobook.books' => [
            'first' => ['title' => 'First', 'ebook' => 'first.epub', 'chapters' => [
                ['title' => 'One', 'file' => 'chapter-01-one.mp3', 'duration' => 10.0],
                ['title' => 'Two', 'file' => 'chapter-02-two.mp3', 'duration' => 10.0],
            ]],
            'second' => ['title' => 'Second', 'ebook' => 'second.epub', 'chapters' => [
                ['title' => 'One', 'file' => 'chapter-01-one.mp3', 'duration' => 10.0],
            ]],
        ]]);

        Storage::disk('local')->put('first/audio/chapter-01-one.mp3', 'one');
        Storage::disk('local')->put('first/audio/chapter-02-two.mp3', 'two');
        Storage::disk('local')->put('first/text/chapter-01.json', '{}');
        Storage::disk('local')->put('first/transcripts/chapter-01-one.json', 'not for the bucket');
        Storage::disk('local')->put('first/first.epub', 'not for the bucket');
        Storage::disk('local')->put('second/audio/chapter-01-one.mp3', 'second one');
    }

    public function test_audio_and_synced_text_are_uploaded(): void
    {
        $this->artisan('audiobook:upload', ['disk' => 's3'])->assertSuccessful();

        $this->assertSame('one', Storage::disk('s3')->get('first/audio/chapter-01-one.mp3'));
        $this->assertSame('two', Storage::disk('s3')->get('first/audio/chapter-02-two.mp3'));
        $this->assertSame('{}', Storage::disk('s3')->get('first/text/chapter-01.json'));
        $this->assertSame('second one', Storage::disk('s3')->get('second/audio/chapter-01-one.mp3'));
        Storage::disk('s3')->assertMissing('first/text/chapter-02.json');
        Storage::disk('s3')->assertMissing('first/transcripts/chapter-01-one.json');
        Storage::disk('s3')->assertMissing('first/first.epub');
    }

    public function test_changed_files_are_replaced(): void
    {
        Storage::disk('s3')->put('first/text/chapter-01.json', '{"old":true}');

        $this->artisan('audiobook:upload', ['disk' => 's3'])->assertSuccessful();

        $this->assertSame('{}', Storage::disk('s3')->get('first/text/chapter-01.json'));
    }

    public function test_uploading_to_the_source_disk_fails(): void
    {
        $this->artisan('audiobook:upload', ['disk' => 'local'])->assertFailed();
    }
}
