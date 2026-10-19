<?php

namespace Tests\Feature;

use App\Http\Controllers\AudioController;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AudioTest extends TestCase
{
    private const BOOK = 'theft-of-fire';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::disk('local')->put(self::BOOK.'/audio/'.config('audiobook.books.'.self::BOOK.'.chapters.0.file'), str_repeat('a', 1000));
    }

    /**
     * @return array<string, string>
     */
    private function playerHeaders(): array
    {
        return ['Sec-Fetch-Site' => 'same-origin', 'Sec-Fetch-Dest' => 'audio'];
    }

    public function test_signed_url_streams_audio_to_the_player(): void
    {
        $response = $this->get(AudioController::signedUrl(self::BOOK, 1), $this->playerHeaders());

        $response->assertOk()
            ->assertHeader('Content-Type', 'audio/mpeg')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_range_requests_are_supported_for_seeking(): void
    {
        $this->get(AudioController::signedUrl(self::BOOK, 1), [...$this->playerHeaders(), 'Range' => 'bytes=0-99'])
            ->assertStatus(206)
            ->assertHeader('Content-Range', 'bytes 0-99/1000');
    }

    public function test_unsigned_url_is_rejected(): void
    {
        $this->get('/audio/'.self::BOOK.'/1', $this->playerHeaders())->assertForbidden();
    }

    public function test_tampered_url_is_rejected(): void
    {
        $url = str_replace('/audio/'.self::BOOK.'/1?', '/audio/'.self::BOOK.'/2?', AudioController::signedUrl(self::BOOK, 1));

        $this->get($url, $this->playerHeaders())->assertForbidden();
    }

    /**
     * Add a second book, with a chapter unless it's coming soon.
     */
    private function addBookTwo(bool $released = true): void
    {
        config(['audiobook.books.book-two' => [
            'title' => 'Book Two',
            'label' => 'Book 2',
            'narrator' => 'Dain Little',
            'cover' => 'images/book-two.jpg',
            'chapters' => $released ? [
                ['title' => 'First Light', 'file' => 'chapter-01-first-light.mp3', 'duration' => 2276.60],
            ] : [],
        ]]);
    }

    public function test_url_moved_to_another_book_is_rejected(): void
    {
        $this->addBookTwo();
        Storage::disk('local')->put('book-two/audio/chapter-01-first-light.mp3', 'b');

        $url = str_replace('/audio/'.self::BOOK.'/1?', '/audio/book-two/1?', AudioController::signedUrl(self::BOOK, 1));

        $this->get($url, $this->playerHeaders())->assertForbidden();
    }

    public function test_each_book_streams_its_own_audio(): void
    {
        $this->addBookTwo();
        Storage::disk('local')->put('book-two/audio/chapter-01-first-light.mp3', str_repeat('b', 500));

        $this->get(AudioController::signedUrl('book-two', 1), $this->playerHeaders())
            ->assertOk()
            ->assertHeader('Content-Length', '500');
    }

    public function test_a_book_that_is_coming_soon_has_no_audio(): void
    {
        $this->addBookTwo(released: false);

        $this->get(AudioController::signedUrl('book-two', 1), $this->playerHeaders())->assertNotFound();
    }

    public function test_expired_url_is_rejected(): void
    {
        $url = AudioController::signedUrl(self::BOOK, 1);

        $this->travel(config('audiobook.url_lifetime') + 1)->minutes();

        $this->get($url, $this->playerHeaders())->assertForbidden();
    }

    public function test_opening_the_url_directly_in_the_browser_is_rejected(): void
    {
        $this->get(AudioController::signedUrl(self::BOOK, 1), ['Sec-Fetch-Site' => 'none', 'Sec-Fetch-Dest' => 'document'])
            ->assertForbidden();
    }

    public function test_hotlinking_from_another_site_is_rejected(): void
    {
        $this->get(AudioController::signedUrl(self::BOOK, 1), ['Sec-Fetch-Site' => 'cross-site', 'Sec-Fetch-Dest' => 'audio'])
            ->assertForbidden();
    }

    public function test_unknown_chapter_is_not_found(): void
    {
        $this->get(AudioController::signedUrl(self::BOOK, 99), $this->playerHeaders())->assertNotFound();
    }

    public function test_unknown_book_is_not_found(): void
    {
        $this->getJson('/audio/the-hobbit/1/url', ['Sec-Fetch-Site' => 'same-origin', 'Sec-Fetch-Dest' => 'empty'])
            ->assertNotFound();
    }

    public function test_requests_are_rate_limited(): void
    {
        $url = AudioController::signedUrl(self::BOOK, 1);

        for ($i = 0; $i < 60; $i++) {
            $this->get($url, $this->playerHeaders())->assertOk();
        }

        $this->get($url, $this->playerHeaders())->assertTooManyRequests();
    }

    public function test_player_can_refresh_an_expired_url(): void
    {
        $response = $this->getJson('/audio/'.self::BOOK.'/1/url', ['Sec-Fetch-Site' => 'same-origin', 'Sec-Fetch-Dest' => 'empty']);

        $response->assertOk();

        $this->get($response->json('url'), $this->playerHeaders())->assertOk();
    }

    public function test_refresh_is_rejected_from_another_site(): void
    {
        $this->getJson('/audio/'.self::BOOK.'/1/url', ['Sec-Fetch-Site' => 'cross-site', 'Sec-Fetch-Dest' => 'empty'])
            ->assertForbidden();
    }
}
