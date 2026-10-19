<?php

namespace App\Console\Commands;

use App\Http\Controllers\AudioController;
use App\Http\Controllers\TextController;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Foundation\CloudBootstrapper;
use Illuminate\Support\Facades\Storage;

#[Signature('audiobook:upload {disk : The disk to upload to, e.g. "private" for the Laravel Cloud bucket} {--from=local : The disk holding the files}')]
#[Description('Copy every book\'s chapter audio and synced text to object storage')]
class UploadAudiobook extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Laravel only reads the bucket from LARAVEL_CLOUD_DISK_CONFIG on Cloud;
        // read it here too, so a copy in the local .env is enough to upload.
        if (! laravel_cloud()) {
            CloudBootstrapper::configureDisks($this->laravel);
        }

        $from = Storage::disk($this->option('from'));
        $to = Storage::disk($this->argument('disk'));

        if ($this->option('from') === $this->argument('disk')) {
            $this->components->error('The source and destination disks are the same.');

            return self::FAILURE;
        }

        $paths = [];

        foreach (config('audiobook.books') as $book => $details) {
            foreach (array_values($details['chapters']) as $index => $chapter) {
                $paths[] = AudioController::path($book, $chapter['file']);

                if ($from->exists(TextController::path($book, $index + 1))) {
                    $paths[] = TextController::path($book, $index + 1);
                }
            }
        }

        foreach ($paths as $path) {
            if (! $from->exists($path)) {
                $this->components->warn("Missing [{$path}], skipping.");

                continue;
            }

            if ($to->exists($path) && $to->size($path) === $from->size($path)) {
                $this->components->twoColumnDetail($path, 'already uploaded');

                continue;
            }

            $this->components->task($path, function () use ($from, $to, $path) {
                $stream = $from->readStream($path);

                try {
                    return $to->writeStream($path, $stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            });
        }

        return self::SUCCESS;
    }
}
