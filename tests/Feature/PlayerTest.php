<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlayerTest extends TestCase
{
    public function test_player_lists_every_chapter(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Theft of Fire')
            ->assertSee('What the Cat Dragged In')
            ->assertSee('A List of Things That Won’t Happen')
            ->assertSee('We Don’t Go to Sedna')
            ->assertSee('Mutually Assured Destruction')
            ->assertSee('Barsoom')
            ->assertSee('The Edge of Sunlight');
    }

    public function test_part_titles_are_listed_with_roman_numerals_between_the_chapters(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSeeInOrder(['Introduction', 'Epigraph', 'One Way Ticket', 'What the Cat Dragged In', 'Barsoom', 'Two Player Game', 'Frog and Scorpion', 'Five Step Process', 'The Same Deep Water', 'The Edge of Sunlight', 'Outro'])
            ->assertViewHas('books', function ($books) {
                $numbers = $books->firstWhere('slug', 'theft-of-fire')['chapters']->pluck('number');

                $parts = $books->firstWhere('slug', 'theft-of-fire')['chapters']->pluck('part');

                return $numbers->take(5)->all() === [null, null, null, 1, 2]
                    && $numbers->filter()->values()->all() === range(1, 33)
                    && $numbers->last() === null
                    && $parts->filter()->values()->all() === ['Part One', 'Part Two', 'Part Three', 'Part Four', 'Part Five'];
            });

        $response->assertSeeInOrder(['>I<', 'One Way Ticket', '>II<', 'Two Player Game', '>V<', 'Five Step Process'], false);
    }

    public function test_the_whole_cast_is_credited(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Devon Eriksen')
            ->assertSee('Dain Little, Emma Rae, and Ashley Crossman');
    }

    public function test_the_book_selector_is_shown_even_with_one_book(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('role="group" aria-label="Books"', false)
            ->assertSee('data-show-book="theft-of-fire"', false);
    }

    public function test_a_book_without_chapters_is_listed_as_coming_soon(): void
    {
        config(['audiobook.books.book-three' => [
            'title' => 'Book Three',
            'label' => 'Book 3',
            'narrator' => 'Dain Little',
            'cover' => 'images/book-three.jpg',
            'ebook' => 'Book Three.epub',
            'chapters' => [],
            'upcoming_chapters' => ['First Light', 'Second Wind'],
        ]]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Coming soon')
            ->assertSeeInOrder(['First Light', 'Second Wind'])
            ->assertDontSee('data-book="book-three"', false);
    }

    public function test_each_chapter_has_an_options_menu_for_its_progress(): void
    {
        config(['audiobook.books.book-three' => [
            'title' => 'Book Three',
            'label' => 'Book 3',
            'narrator' => 'Dain Little',
            'cover' => 'images/book-three.jpg',
            'chapters' => [],
            'upcoming_chapters' => ['First Light'],
        ]]);

        $response = $this->get('/')->assertOk();
        $chapterCount = collect(config('audiobook.books'))->sum(fn (array $book): int => count($book['chapters']));

        $this->assertSame($chapterCount, substr_count($response->getContent(), 'data-chapter-options='));
        $this->assertSame($chapterCount, substr_count($response->getContent(), 'data-action="reset-progress"'));
        $response->assertSeeInOrder(['aria-label="Options for Introduction"', 'id="actions-theft-of-fire-0"', 'Reset progress'], false)
            ->assertDontSee('data-options-book="book-three"', false);
    }

    public function test_chapter_lengths_are_shown_in_minutes_and_seconds(): void
    {
        config(['audiobook.books' => [
            'first' => ['title' => 'First', 'label' => 'Book 1', 'narrator' => 'Reader', 'cover' => 'images/first.jpg', 'chapters' => [
                ['title' => 'Short', 'file' => 'short.mp3', 'duration' => 8.9],
                ['title' => 'Medium', 'file' => 'medium.mp3', 'duration' => 2250.5],
                ['title' => 'Long', 'file' => 'long.mp3', 'duration' => 4718.58],
            ]],
        ]]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Short', '00:08', 'Medium', '37:30', 'Long', '78:38']);
    }

    public function test_cover_urls_change_when_the_cover_does(): void
    {
        $hash = substr(hash_file('xxh3', public_path('images/theft-of-fire.jpg')), 0, 8);

        $this->get('/')
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books->firstWhere('slug', 'theft-of-fire')['cover'] === asset('images/theft-of-fire.jpg').'?v='.$hash);
    }

    public function test_player_links_to_signed_audio_urls_instead_of_files(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('.mp3')
            ->assertViewHas('books', function ($books) {
                $url = $books->first()['chapters']->first()['url'];

                return str_starts_with($url, url('/audio/theft-of-fire/1?expires='))
                    && str_contains($url, 'signature=');
            });
    }

    public function test_player_knows_each_chapters_download_size(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('theft-of-fire/audio/'.config('audiobook.books.theft-of-fire.chapters.0.file'), str_repeat('a', 1234));

        $this->get('/')
            ->assertOk()
            ->assertViewHas('books', function ($books) {
                $chapters = $books->firstWhere('slug', 'theft-of-fire')['chapters'];

                return $chapters[0]['size'] === 1234 && $chapters[1]['size'] === null;
            });
    }

    public function test_tracks_that_are_not_chapters_are_left_unnumbered(): void
    {
        config(['audiobook.books' => [
            'first' => ['title' => 'First', 'label' => 'Book 1', 'narrator' => 'Reader', 'cover' => 'images/first.jpg', 'ebook' => 'first.epub', 'chapters' => [
                ['title' => 'Opening Credits', 'file' => 'opening-credits.mp3', 'duration' => 10.0, 'numbered' => false],
                ['title' => 'One', 'file' => 'chapter-01-one.mp3', 'duration' => 10.0],
                ['title' => 'Two', 'file' => 'chapter-02-two.mp3', 'duration' => 10.0],
                ['title' => 'Credits', 'file' => 'credits.mp3', 'duration' => 10.0, 'numbered' => false],
            ]],
            'second' => ['title' => 'Second', 'label' => 'Book 2', 'narrator' => 'Reader', 'cover' => 'images/second.jpg', 'ebook' => 'second.epub', 'chapters' => [
                ['title' => 'One', 'file' => 'chapter-01-one.mp3', 'duration' => 10.0],
            ]],
        ]]);

        $this->get('/')
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books[0]['chapters']->pluck('number')->all() === [null, 1, 2, null]
                && $books[1]['chapters']->pluck('number')->all() === [1]);
    }

    public function test_every_configured_chapter_file_exists(): void
    {
        $disk = Storage::disk(config('audiobook.disk'));

        foreach (config('audiobook.books') as $book => $details) {
            if ($details['chapters'] === []) {
                continue;
            }

            if ($disk->files("{$book}/audio") === []) {
                $this->markTestSkipped("The audio is not in the repo; add it to storage/app/private/{$book}/audio to check it.");
            }

            foreach ($details['chapters'] as $chapter) {
                $this->assertTrue($disk->exists("{$book}/audio/{$chapter['file']}"), "Missing {$book}/audio/{$chapter['file']}");
            }
        }
    }

    public function test_audio_files_are_not_publicly_accessible(): void
    {
        foreach (config('audiobook.books') as $book => $details) {
            foreach ($details['chapters'] as $chapter) {
                $this->assertFileDoesNotExist(public_path("audio/{$chapter['file']}"));
                $this->assertFileDoesNotExist(public_path("{$book}/audio/{$chapter['file']}"));
            }
        }
    }
}
