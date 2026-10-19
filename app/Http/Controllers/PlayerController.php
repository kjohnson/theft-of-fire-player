<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class PlayerController extends Controller
{
    /**
     * Show the audiobook player.
     */
    public function __invoke(): View
    {
        $books = collect(config('audiobook.books'))
            ->map(function (array $book, string $slug): array {
                $number = 0;

                return [
                    'slug' => $slug,
                    'title' => $book['title'],
                    'label' => $book['label'],
                    'chapterName' => $book['chapter_name'] ?? 'Chapter',
                    'narrator' => $book['narrator'],
                    'cover' => self::versionedAsset($book['cover']),
                    'upcomingChapters' => $book['upcoming_chapters'] ?? [],
                    'chapters' => collect($book['chapters'])
                        ->values()
                        ->map(function (array $chapter, int $index) use ($slug, &$number): array {
                            return [
                                'number' => $chapter['number'] ?? (($chapter['numbered'] ?? ! isset($chapter['part'])) ? ++$number : null),
                                'part' => isset($chapter['part']) ? 'Part '.self::spell($chapter['part']) : null,
                                'title' => $chapter['title'],
                                'duration' => $chapter['duration'],
                                'url' => AudioController::signedUrl($slug, $index + 1),
                                'size' => AudioController::size($slug, $index + 1),
                                'hasText' => TextController::exists($slug, $index + 1),
                            ];
                        }),
                ];
            })
            ->values();

        return view('player', [
            'series' => [
                'title' => config('audiobook.series'),
                'author' => config('audiobook.author'),
            ],
            'books' => $books,
        ]);
    }

    /**
     * The URL of a public file, with a short hash of its contents so a
     * changed file gets a new URL. The service worker keeps images it has
     * already saved, so an unchanged URL would keep showing the old one.
     */
    private static function versionedAsset(string $path): string
    {
        $file = public_path($path);

        return is_file($file) ? asset($path).'?v='.substr(hash_file('xxh3', $file), 0, 8) : asset($path);
    }

    /**
     * A part's number in words ("Part One"), as the book's contents give it.
     */
    private static function spell(int $number): string
    {
        return [1 => 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten'][$number] ?? (string) $number;
    }
}
