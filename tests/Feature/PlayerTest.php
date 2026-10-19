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
            ->assertSee('After Moses')
            ->assertSee('Europan Extraction')
            ->assertSee('Rings of Interferance')
            ->assertSee('Train to Churchill')
            ->assertSee('The Hawthorne Pact')
            ->assertSee('Bright Crater Diagnosis')
            ->assertSee('Islands in the Night');
    }

    public function test_player_lists_every_book_in_reading_order(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['After Moses', 'Splendid Sam', 'Prodigal'])
            ->assertViewHas('books', fn ($books) => $books->pluck('slug')->all() === ['after-moses', 'splendid-sam', 'prodigal']
                && $books->pluck('label')->all() === ['Book 1', 'Radio drama', 'Book 2']);
    }

    public function test_splendid_sam_is_listed_by_its_episode_number(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('For a Dozen Quid of Platinum')
            ->assertDontSee('Episode 387: For a Dozen Quid of Platinum')
            ->assertSee('Son of Sonnet')
            ->assertViewHas('books', function ($books) {
                $book = $books->firstWhere('slug', 'splendid-sam');

                return $book['chapterName'] === 'Episode'
                    && $book['chapters']->count() === 1
                    && $book['chapters']->first()['number'] === 387;
            });
    }

    public function test_player_lists_prodigals_chapters(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('A Rumor Bound in Journal')
            ->assertSee('The Prodigal of Ceres')
            ->assertDontSee('Coming soon');
    }

    public function test_a_book_without_chapters_is_listed_as_coming_soon(): void
    {
        config(['audiobook.books.book-three' => [
            'title' => 'Book Three',
            'label' => 'Book 3',
            'narrator' => 'Randy McCarten',
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
            'narrator' => 'Randy McCarten',
            'cover' => 'images/book-three.jpg',
            'chapters' => [],
            'upcoming_chapters' => ['First Light'],
        ]]);

        $response = $this->get('/')->assertOk();
        $chapterCount = collect(config('audiobook.books'))->sum(fn (array $book): int => count($book['chapters']));

        $this->assertSame($chapterCount, substr_count($response->getContent(), 'data-chapter-options='));
        $this->assertSame($chapterCount, substr_count($response->getContent(), 'data-action="reset-progress"'));
        $response->assertSeeInOrder(['aria-label="Options for Europan Extraction"', 'id="actions-after-moses-0"', 'Reset progress'], false)
            ->assertSee('aria-controls="actions-prodigal-0"', false)
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
        $hash = substr(hash_file('xxh3', public_path('images/splendid-sam.jpg')), 0, 8);

        $this->get('/')
            ->assertOk()
            ->assertViewHas('books', fn ($books) => $books->firstWhere('slug', 'splendid-sam')['cover'] === asset('images/splendid-sam.jpg').'?v='.$hash);
    }

    public function test_player_links_to_signed_audio_urls_instead_of_files(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('.mp3')
            ->assertViewHas('books', function ($books) {
                $url = $books->first()['chapters']->first()['url'];

                return str_starts_with($url, url('/audio/after-moses/1?expires='))
                    && str_contains($url, 'signature=');
            });
    }

    public function test_player_knows_each_chapters_download_size(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('after-moses/audio/'.config('audiobook.books.after-moses.chapters.0.file'), str_repeat('a', 1234));

        $this->get('/')
            ->assertOk()
            ->assertViewHas('books', function ($books) {
                $chapters = $books->firstWhere('slug', 'after-moses')['chapters'];

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
