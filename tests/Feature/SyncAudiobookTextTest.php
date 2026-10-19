<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class SyncAudiobookTextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['audiobook.books' => [
            'test-book' => [
                'title' => 'Test Book',
                'ebook' => 'Test Book.epub',
                'chapters' => [
                    ['title' => 'Europan Extraction', 'file' => 'chapter-01-europan-extraction.mp3', 'duration' => 20.0],
                    ['title' => 'Rings of Interference', 'file' => 'chapter-02-rings-of-interferance.mp3', 'duration' => 20.0],
                ],
            ],
            'sequel' => [
                'title' => 'Sequel',
                'ebook' => 'sequel.epub',
                'chapters' => [
                    ['title' => 'Return', 'file' => 'chapter-01-return.mp3', 'duration' => 20.0],
                ],
            ],
        ]]);

        Storage::disk('local')->put('sequel/sequel.epub', $this->makeEpub([
            'one.xhtml' => $this->xhtml('Chapter 1: Return', '<p>Back again.</p>'),
        ]));

        Storage::disk('local')->put('test-book/Test Book.epub', $this->makeEpub([
            'front.xhtml' => $this->xhtml('Contents', '<p>Chapter 1 starts on page one.</p>'),
            'one.xhtml' => $this->xhtml('Chapter 1: Europan Extraction', implode('', [
                '<h2>Chapter 1: Europan Extraction</h2>',
                '<blockquote><em>We lived on.</em><br/><br/><b>Alexei</b></blockquote>',
                '<span class="ornamental-break"><img src="break.png" alt=""/></span>',
                '<p><span class="lead_word">A</span> <span class="lead_word">SLEEK</span> craft drifted&#x2014;slowly.</p>',
                '<p>&#x201C;I&#x2019;m Vicente Luna,&#x201D; he said.</p>',
            ])),
            'two.xhtml' => $this->xhtml('Chapter 2: Rings of Interference', '<p>Elsewhere.</p>'),
        ]));
    }

    public function test_chapter_text_is_split_into_blocks_of_words(): void
    {
        $this->putTranscript('chapter-01-europan-extraction', $this->spoken('chapter one europan extraction we lived on alexei a sleek craft drifted slowly im vicente luna he said'));

        $this->artisan('audiobook:sync')->assertSuccessful();

        $this->assertSame([
            ['tag' => 'heading', 'words' => ['Chapter', '1:', 'Europan', 'Extraction']],
            ['tag' => 'quote', 'words' => ['We', 'lived', 'on.']],
            ['tag' => 'quote', 'words' => ['Alexei']],
            ['tag' => 'break', 'words' => []],
            ['tag' => 'p', 'words' => ['A', 'SLEEK', 'craft', 'drifted—', 'slowly.']],
            ['tag' => 'p', 'words' => ['“I’m', 'Vicente', 'Luna,”', 'he', 'said.']],
        ], $this->synced(1)['blocks']);
    }

    public function test_matching_words_get_the_narrators_timings(): void
    {
        $this->putTranscript('chapter-01-europan-extraction', $this->spoken('chapter one europan extraction we lived on alexei a sleek craft drifted slowly im vicente luna he said'));

        $this->artisan('audiobook:sync')->assertSuccessful();

        $timings = $this->synced(1)['timings'];

        // "Europan" is the third spoken word and the third book word.
        $this->assertEquals([2, 2.5], array_slice($timings, 4, 2));
        // "said." is the last of 18 words in both.
        $this->assertEquals([17, 17.5], array_slice($timings, 34, 2));
    }

    public function test_extra_and_skipped_narration_stays_in_sync(): void
    {
        // The narrator adds "um, well" and skips "craft drifted".
        $this->putTranscript('chapter-01-europan-extraction', $this->spoken('chapter one europan extraction we lived on alexei um well a sleek slowly im vicente luna he said'));

        $this->artisan('audiobook:sync')->assertSuccessful();

        $timings = $this->synced(1)['timings'];

        // "A" (book word 8) is spoken as word 10.
        $this->assertEquals([10, 10.5], array_slice($timings, 16, 2));
        // "slowly." (book word 12) is spoken as word 12.
        $this->assertEquals([12, 12.5], array_slice($timings, 24, 2));
        // The skipped "craft drifted—" fit in the gap between "SLEEK" and "slowly.".
        $this->assertGreaterThanOrEqual(11.5, $timings[20]);
        $this->assertLessThanOrEqual(12.0, $timings[23]);
    }

    public function test_timings_never_go_backwards(): void
    {
        $this->putTranscript('chapter-01-europan-extraction', $this->spoken('something else entirely he said'));

        $this->artisan('audiobook:sync')->assertSuccessful();

        $timings = $this->synced(1)['timings'];
        $sorted = $timings;
        sort($sorted);

        $this->assertSame($sorted, $timings);
        $this->assertCount(36, $timings);
    }

    public function test_chapters_without_a_transcript_are_skipped(): void
    {
        $this->putTranscript('chapter-01-europan-extraction', $this->spoken('chapter one'));

        $this->artisan('audiobook:sync')->assertSuccessful();

        Storage::disk('local')->assertExists('test-book/text/chapter-01.json');
        Storage::disk('local')->assertMissing('test-book/text/chapter-02.json');
    }

    public function test_a_single_chapter_can_be_synced(): void
    {
        $this->putTranscript('chapter-01-europan-extraction', $this->spoken('chapter one'));
        $this->putTranscript('chapter-02-rings-of-interferance', $this->spoken('elsewhere'));

        $this->artisan('audiobook:sync', ['book' => 'test-book', 'chapter' => 2])->assertSuccessful();

        Storage::disk('local')->assertMissing('test-book/text/chapter-01.json');
        $this->assertSame([['tag' => 'p', 'words' => ['Elsewhere.']]], $this->synced(2)['blocks']);
    }

    public function test_a_chapter_can_point_at_a_specific_ebook_file(): void
    {
        config(['audiobook.books.test-book.chapters.1.epub' => 'one.xhtml']);
        $this->putTranscript('chapter-02-rings-of-interferance', $this->spoken('chapter one'));

        $this->artisan('audiobook:sync', ['book' => 'test-book', 'chapter' => 2])->assertSuccessful();

        $this->assertSame('Europan', $this->synced(2)['blocks'][0]['words'][2]);
    }

    public function test_every_book_is_synced_into_its_own_folder(): void
    {
        $this->putTranscript('chapter-01-europan-extraction', $this->spoken('chapter one'));
        $this->putTranscript('chapter-01-return', $this->spoken('back again'), 'sequel');

        $this->artisan('audiobook:sync')->assertSuccessful();

        $this->assertSame('Europan', $this->synced(1)['blocks'][0]['words'][2]);
        $this->assertSame([['tag' => 'p', 'words' => ['Back', 'again.']]], $this->synced(1, 'sequel')['blocks']);
    }

    public function test_books_without_chapters_yet_are_skipped(): void
    {
        config(['audiobook.books.coming-soon' => ['title' => 'Coming Soon', 'ebook' => 'not-here-yet.epub', 'chapters' => []]]);
        $this->putTranscript('chapter-01-europan-extraction', $this->spoken('chapter one'));

        $this->artisan('audiobook:sync')->assertSuccessful();

        Storage::disk('local')->assertExists('test-book/text/chapter-01.json');
    }

    public function test_books_without_an_ebook_are_skipped(): void
    {
        config(['audiobook.books.radio-drama' => ['title' => 'Radio Drama', 'chapters' => [
            ['title' => 'Episode 1', 'file' => 'episode-01.mp3', 'duration' => 20.0],
        ]]]);
        $this->putTranscript('chapter-01-europan-extraction', $this->spoken('chapter one'));

        $this->artisan('audiobook:sync')->assertSuccessful();

        Storage::disk('local')->assertExists('test-book/text/chapter-01.json');
        Storage::disk('local')->assertMissing('radio-drama/text/chapter-01.json');
    }

    public function test_a_single_book_can_be_synced(): void
    {
        $this->putTranscript('chapter-01-europan-extraction', $this->spoken('chapter one'));
        $this->putTranscript('chapter-01-return', $this->spoken('back again'), 'sequel');

        $this->artisan('audiobook:sync', ['book' => 'sequel'])->assertSuccessful();

        Storage::disk('local')->assertExists('sequel/text/chapter-01.json');
        Storage::disk('local')->assertMissing('test-book/text/chapter-01.json');
    }

    public function test_unknown_book_fails(): void
    {
        $this->artisan('audiobook:sync', ['book' => 'the-hobbit'])->assertFailed();
    }

    public function test_unknown_chapter_fails(): void
    {
        $this->artisan('audiobook:sync', ['book' => 'sequel', 'chapter' => 2])->assertFailed();
    }

    public function test_missing_ebook_fails(): void
    {
        config(['audiobook.books.test-book.ebook' => 'missing.epub']);

        $this->artisan('audiobook:sync')->assertFailed();
    }

    /**
     * Build WhisperX-style output where word N is spoken from N to N + 0.5 seconds.
     *
     * @return array<string, mixed>
     */
    private function spoken(string $text): array
    {
        $words = [];

        foreach (explode(' ', $text) as $index => $word) {
            $words[] = ['word' => $word, 'start' => (float) $index, 'end' => $index + 0.5, 'score' => 0.9];
        }

        return ['segments' => [['start' => 0, 'end' => count($words), 'text' => $text, 'words' => $words]]];
    }

    /**
     * @param  array<string, mixed>  $transcript
     */
    private function putTranscript(string $name, array $transcript, string $book = 'test-book'): void
    {
        Storage::disk('local')->put("{$book}/transcripts/{$name}.json", json_encode($transcript));
    }

    /**
     * @return array{blocks: array<int, array{tag: string, words: array<int, string>}>, timings: array<int, float>}
     */
    private function synced(int $chapter, string $book = 'test-book'): array
    {
        return json_decode(Storage::disk('local')->get(sprintf('%s/text/chapter-%02d.json', $book, $chapter)), true);
    }

    private function xhtml(string $title, string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><!DOCTYPE html>'
            .'<html xmlns="http://www.w3.org/1999/xhtml"><head><title>'.$title.'</title></head>'
            .'<body><div class="chapter">'.$body.'</div></body></html>';
    }

    /**
     * @param  array<string, string>  $documents
     */
    private function makeEpub(array $documents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'epub');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);

        $zip->addFromString('mimetype', 'application/epub+zip');
        $zip->addFromString('META-INF/container.xml', '<?xml version="1.0"?><container xmlns="urn:oasis:names:tc:opendocument:xmlns:container" version="1.0"><rootfiles><rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/></rootfiles></container>');

        $items = '';
        $spine = '';

        foreach (array_keys($documents) as $index => $href) {
            $items .= "<item id=\"item{$index}\" href=\"{$href}\" media-type=\"application/xhtml+xml\"/>";
            $spine .= "<itemref idref=\"item{$index}\"/>";
        }

        $zip->addFromString('OEBPS/content.opf', '<?xml version="1.0"?><package xmlns="http://www.idpf.org/2007/opf" version="3.0"><manifest>'.$items.'</manifest><spine>'.$spine.'</spine></package>');

        foreach ($documents as $href => $html) {
            $zip->addFromString("OEBPS/{$href}", $html);
        }

        $zip->close();

        $contents = file_get_contents($path);
        unlink($path);

        return $contents;
    }
}
