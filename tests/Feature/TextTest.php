<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TextTest extends TestCase
{
    private const BOOK = 'after-moses';

    private const SYNCED_TEXT = '{"blocks":[{"tag":"p","words":["Hello","world."]}],"timings":[0,0.5,0.5,1]}';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::disk('local')->put(self::BOOK.'/text/chapter-01.json', self::SYNCED_TEXT);
    }

    /**
     * @return array<string, string>
     */
    private function playerHeaders(): array
    {
        return ['Sec-Fetch-Site' => 'same-origin', 'Sec-Fetch-Dest' => 'empty'];
    }

    public function test_player_can_fetch_synced_text(): void
    {
        $response = $this->get('/text/'.self::BOOK.'/1', $this->playerHeaders());

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->assertSame(self::SYNCED_TEXT, $response->getContent());
    }

    public function test_chapter_without_synced_text_is_not_found(): void
    {
        $this->get('/text/'.self::BOOK.'/2', $this->playerHeaders())->assertNotFound();
    }

    public function test_another_books_chapter_has_its_own_text(): void
    {
        $this->get('/text/prodigal/1', $this->playerHeaders())->assertNotFound();
    }

    public function test_unknown_chapter_is_not_found(): void
    {
        $this->get('/text/'.self::BOOK.'/99', $this->playerHeaders())->assertNotFound();
    }

    public function test_opening_the_text_directly_in_the_browser_is_rejected(): void
    {
        $this->get('/text/'.self::BOOK.'/1', ['Sec-Fetch-Site' => 'none', 'Sec-Fetch-Dest' => 'document'])->assertForbidden();
    }

    public function test_fetching_from_another_site_is_rejected(): void
    {
        $this->get('/text/'.self::BOOK.'/1', ['Sec-Fetch-Site' => 'cross-site', 'Sec-Fetch-Dest' => 'empty'])->assertForbidden();
    }

    public function test_player_only_offers_text_for_synced_chapters(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertViewHas('books', function ($books) {
                $chapters = $books->flatMap(fn (array $book) => $book['chapters']);

                return $chapters->first()['hasText'] && ! $chapters->skip(1)->contains('hasText', true);
            });
    }
}
