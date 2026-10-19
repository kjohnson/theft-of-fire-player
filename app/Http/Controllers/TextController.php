<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class TextController extends Controller
{
    /**
     * Send a chapter's follow-along text and word timings to the player.
     */
    public function show(Request $request, string $book, int $chapter): Response
    {
        $this->chapterConfig($book, $chapter);

        abort_unless($this->isPlayerRequest($request, ['empty']), 403);

        $disk = Storage::disk(config('audiobook.disk'));
        $path = self::path($book, $chapter);

        abort_unless($disk->exists($path), 404);

        return response($disk->get($path), 200, [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'private, max-age=3600',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    /**
     * Whether a chapter (numbered from 1) has synced text available.
     *
     * The folder is listed once and remembered for a few minutes, so the
     * player doesn't check object storage for every chapter on every visit.
     */
    public static function exists(string $book, int $chapter): bool
    {
        $paths = Cache::remember("audiobook:text-files:{$book}", now()->addMinutes(5), fn () => Storage::disk(config('audiobook.disk'))->files("{$book}/text"));

        return in_array(self::path($book, $chapter), $paths, true);
    }

    /**
     * The disk path of a chapter's synced text (numbered from 1).
     */
    public static function path(string $book, int $chapter): string
    {
        return sprintf('%s/text/chapter-%02d.json', $book, $chapter);
    }
}
