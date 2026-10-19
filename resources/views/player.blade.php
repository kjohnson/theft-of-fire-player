@php($book = $books->first())
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#110f1f">
        <meta name="description" content="Listen to the {{ $series['title'] }} audiobooks by {{ $series['author'] }}.">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ $series['title'] }}">

        <meta property="og:title" content="{{ $series['title'] }} — Audiobooks">
        <meta property="og:description" content="By {{ $series['author'] }}.">
        <meta property="og:image" content="{{ $book['cover'] }}">

        <title>{{ $series['title'] }} — Audiobooks</title>

        <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
        <link rel="icon" type="image/png" href="{{ asset('images/icon-192.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="preload" as="image" href="{{ $book['cover'] }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-dvh bg-night-950 text-haze-50 antialiased select-none">
        <script type="application/json" id="audiobook-data">
            @json(['series' => $series, 'books' => $books])
        </script>

        {{-- Blurred cover backdrop --}}
        <div aria-hidden="true" class="pointer-events-none fixed inset-0 overflow-hidden">
            <img src="{{ $book['cover'] }}" alt="" data-bind="backdrop" class="absolute inset-0 size-full scale-125 object-cover opacity-30 blur-3xl">
            <div class="absolute inset-0 bg-linear-to-b from-night-950/40 via-night-950/80 to-night-950"></div>
        </div>

        <main
            id="player"
            class="relative mx-auto flex min-h-dvh max-w-md flex-col px-6 pt-[max(1rem,env(safe-area-inset-top))] pb-[max(1.5rem,env(safe-area-inset-bottom))]"
        >
            <header class="flex items-center justify-between py-2">
                <div class="min-w-0">
                    <p class="text-xs font-medium tracking-[0.2em] text-haze-400 uppercase" data-bind="book-label">{{ $book['label'] }}</p>
                    <h1 class="truncate text-base font-semibold" data-bind="book-title">{{ $book['title'] }}</h1>
                </div>
                <div class="-mr-2 flex items-center">
                    <button
                        type="button"
                        data-action="text"
                        class="tap reader-toggle hidden size-11 items-center justify-center rounded-full text-haze-200"
                        aria-label="Follow along in the text"
                        aria-pressed="false"
                    >
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 6.5C10.5 5 8 4.5 4 4.5v13c4 0 6.5.5 8 2 1.5-1.5 4-2 8-2v-13c-4 0-6.5.5-8 2Zm0 0v13"/></svg>
                    </button>
                    <button
                        type="button"
                        data-action="open-chapters"
                        class="tap flex size-11 items-center justify-center rounded-full text-haze-200"
                        aria-label="Show books and chapters"
                    >
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3.5 6h.01M3.5 12h.01M3.5 18h.01"/></svg>
                    </button>
                </div>
            </header>

            <div class="flex flex-1 flex-col justify-center py-4" data-view="cover">
                <img
                    src="{{ $book['cover'] }}"
                    alt="{{ $book['title'] }} cover art"
                    data-bind="cover"
                    width="1024"
                    height="1024"
                    class="mx-auto aspect-square w-full max-w-[min(100%,48dvh)] rounded-2xl object-cover shadow-2xl shadow-black/60 ring-1 ring-white/10"
                >

                <div class="mt-7 text-center">
                    <p class="text-sm font-medium text-lime-400" data-bind="chapter-label">@php($numberedCount = $book['chapters']->whereNotNull('number')->count()){{ $book['chapters']->first()['number'] ? $book['chapterName'].' '.$book['chapters']->first()['number'].($numberedCount > 1 ? ' of '.$numberedCount : '') : $book['title'] }}</p>
                    <h2 class="mt-1 text-2xl leading-tight font-semibold text-balance" data-bind="chapter-title">{{ $book['chapters']->first()['title'] }}</h2>
                    <p class="mt-1 text-sm text-haze-400">{{ $series['author'] }} · Read by <span data-bind="narrator">{{ $book['narrator'] }}</span></p>
                </div>
            </div>

            {{-- Follow-along text --}}
            <div class="relative -mx-6 hidden min-h-0 flex-1" data-view="text">
                <div class="reader-size" role="group" aria-label="Text size">
                    <button type="button" data-action="text-smaller" class="tap flex size-9 items-center justify-center rounded-full" aria-label="Smaller text">
                        <span class="text-xs font-semibold" aria-hidden="true">A−</span>
                    </button>
                    <button type="button" data-action="text-larger" class="tap flex size-9 items-center justify-center rounded-full" aria-label="Larger text">
                        <span class="text-base font-semibold" aria-hidden="true">A+</span>
                    </button>
                </div>
                <div id="reader" class="reader" aria-label="Chapter text" tabindex="-1">
                    <p class="reader-status" data-bind="reader-status">Loading text…</p>
                </div>
                <button type="button" data-action="follow" class="tap reader-follow hidden" aria-label="Back to narration">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                    Back to narration
                </button>
            </div>

            <section aria-label="Playback">
                {{-- Scrubber --}}
                <div class="relative">
                    <input
                        type="range"
                        min="0"
                        max="{{ $book['chapters']->first()['duration'] }}"
                        step="1"
                        value="0"
                        data-bind="seek"
                        class="scrubber"
                        aria-label="Seek"
                    >
                    {{-- Marks where "back to" returns; the button above it is the accessible control. --}}
                    <button type="button" data-action="return" data-bind="return-marker" class="return-marker hidden" tabindex="-1" aria-hidden="true"></button>
                    <button type="button" data-action="return" class="tap return-point hidden">
                        <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/></svg>
                        <span data-bind="return-time"></span>
                    </button>
                    <div class="mt-1 flex justify-between text-xs text-haze-400 tabular-nums">
                        <span data-bind="elapsed">0:00</span>
                        <span data-bind="status" class="text-lime-400" aria-live="polite"></span>
                        <span data-bind="remaining">-0:00</span>
                    </div>
                </div>

                {{-- Transport --}}
                <div class="mt-4 flex items-center justify-between">
                    <button type="button" data-action="prev" class="tap flex size-12 items-center justify-center rounded-full text-haze-200" aria-label="Previous chapter">
                        <svg class="size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M6 5a1 1 0 0 1 1 1v12a1 1 0 1 1-2 0V6a1 1 0 0 1 1-1Zm12.5.4A1 1 0 0 1 20 6.2v11.6a1 1 0 0 1-1.5.86l-9.6-5.8a1 1 0 0 1 0-1.72l9.6-5.8Z"/></svg>
                    </button>

                    <button type="button" data-action="back" class="tap relative flex size-14 items-center justify-center rounded-full text-haze-50" aria-label="Back 15 seconds">
                        <svg class="size-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 3v5h5"/></svg>
                        <span class="absolute pt-0.5 text-[0.65rem] font-semibold">15</span>
                    </button>

                    <button
                        type="button"
                        data-action="toggle"
                        class="tap flex size-20 items-center justify-center rounded-full bg-lime-500 text-night-950 shadow-lg shadow-lime-500/30"
                        aria-label="Play"
                    >
                        <svg data-icon="play" class="ml-1 size-9" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4.8a1 1 0 0 1 1.5-.86l11.2 7.2a1 1 0 0 1 0 1.72l-11.2 7.2A1 1 0 0 1 7 19.2V4.8Z"/></svg>
                        <svg data-icon="pause" class="hidden size-9" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><rect x="5.5" y="4" width="4.5" height="16" rx="1.25"/><rect x="14" y="4" width="4.5" height="16" rx="1.25"/></svg>
                        <svg data-icon="loading" class="hidden size-9 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 3a9 9 0 1 0 9 9"/></svg>
                    </button>

                    <button type="button" data-action="forward" class="tap relative flex size-14 items-center justify-center rounded-full text-haze-50" aria-label="Forward 30 seconds">
                        <svg class="size-9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-3-6.7"/><path d="M21 3v5h-5"/></svg>
                        <span class="absolute pt-0.5 text-[0.65rem] font-semibold">30</span>
                    </button>

                    <button type="button" data-action="next" class="tap flex size-12 items-center justify-center rounded-full text-haze-200" aria-label="Next chapter">
                        <svg class="size-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M18 5a1 1 0 0 1 1 1v12a1 1 0 1 1-2 0V6a1 1 0 0 1 1-1ZM5.5 5.4l9.6 5.8a1 1 0 0 1 0 1.72l-9.6 5.8A1 1 0 0 1 4 17.8V6.2a1 1 0 0 1 1.5-.86Z"/></svg>
                    </button>
                </div>
            </section>
        </main>

        {{-- Chapter sheet --}}
        <dialog id="chapters" class="sheet" aria-labelledby="chapters-heading">
            <div class="mx-auto flex min-h-full max-w-md flex-col px-4 pt-3 pb-[max(1rem,env(safe-area-inset-bottom))]">
                <div class="mx-auto mb-3 h-1.5 w-10 rounded-full bg-white/20" aria-hidden="true"></div>
                <div class="flex items-center justify-between px-2 pb-2">
                    <h2 id="chapters-heading" class="text-lg font-semibold">Chapters</h2>
                    <form method="dialog">
                        <button class="tap -mr-2 flex size-11 items-center justify-center rounded-full text-haze-300" aria-label="Close chapters">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
                        </button>
                    </form>
                </div>

                <div class="-mx-4 mb-3 flex snap-x gap-3 overflow-x-auto px-4 pb-1 [scrollbar-width:none]" role="group" aria-label="Books">
                    @foreach ($books as $listed)
                        <button
                            type="button"
                            data-show-book="{{ $listed['slug'] }}"
                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                            class="tap book-tab flex w-24 shrink-0 snap-start flex-col items-center gap-2 rounded-xl p-2 text-center"
                        >
                            <img src="{{ $listed['cover'] }}" alt="" width="1024" height="1024" loading="lazy" class="aspect-square w-full rounded-lg object-cover ring-1 ring-white/10 {{ $listed['chapters']->isEmpty() ? 'opacity-40' : '' }}">
                            <span class="line-clamp-2 text-xs leading-snug font-medium">{{ $listed['title'] }}</span>
                            @if ($listed['chapters']->isEmpty())
                                <span class="-mt-1 text-[0.7rem] font-semibold whitespace-nowrap text-lime-400">Coming soon</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                @foreach ($books as $listed)
                    @if ($listed['chapters']->isEmpty())
                        <ol class="space-y-1 {{ $loop->first ? '' : 'hidden' }}" data-book-chapters="{{ $listed['slug'] }}" aria-label="{{ $listed['title'] }} chapters, coming soon">
                            @foreach ($listed['upcomingChapters'] as $title)
                                <li class="flex items-center gap-4 rounded-xl px-3 py-3 opacity-50">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-white/5 text-sm font-semibold tabular-nums text-haze-300">
                                        {{ $loop->iteration }}
                                    </span>
                                    <span class="min-w-0 flex-1 truncate font-medium">{{ $title }}</span>
                                </li>
                            @endforeach
                        </ol>
                        @continue
                    @endif
                    <ol class="space-y-1 {{ $loop->first ? '' : 'hidden' }}" data-book-chapters="{{ $listed['slug'] }}" aria-label="{{ $listed['title'] }}">
                        @foreach ($listed['chapters'] as $index => $chapter)
                            <li class="chapter-item rounded-xl">
                                <div class="flex items-center">
                                    <button
                                        type="button"
                                        data-book="{{ $listed['slug'] }}"
                                        data-chapter="{{ $index }}"
                                        class="tap chapter-row flex min-w-0 flex-1 items-center gap-4 rounded-xl py-3 pl-3 text-left"
                                    >
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-white/5 text-sm font-semibold tabular-nums text-haze-300 chapter-number">
                                            @if ($chapter['number'])
                                                {{ $chapter['number'] }}
                                            @else
                                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
                                            @endif
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate font-medium">{{ $chapter['title'] }}</span>
                                            <span class="mt-1 block h-1 overflow-hidden rounded-full bg-white/10">
                                                <span class="block h-full w-0 rounded-full bg-lime-400" data-progress></span>
                                            </span>
                                        </span>
                                        <span class="shrink-0 text-sm text-haze-400 tabular-nums">
                                            {{ sprintf('%02d:%02d', intdiv((int) $chapter['duration'], 60), (int) $chapter['duration'] % 60) }}
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        data-chapter-options="{{ $index }}"
                                        data-options-book="{{ $listed['slug'] }}"
                                        aria-expanded="false"
                                        aria-controls="actions-{{ $listed['slug'] }}-{{ $index }}"
                                        aria-label="Options for {{ $chapter['title'] }}"
                                        class="tap flex size-11 shrink-0 items-center justify-center rounded-full text-haze-400"
                                    >
                                        <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.75"/><circle cx="12" cy="12" r="1.75"/><circle cx="19" cy="12" r="1.75"/></svg>
                                    </button>
                                </div>
                                <div id="actions-{{ $listed['slug'] }}-{{ $index }}" class="hidden justify-end gap-2 px-3 pb-3 [&:not(.hidden)]:flex" data-chapter-actions>
                                    <button type="button" data-action="reset-progress" class="tap rounded-full px-3 py-1.5 text-sm text-haze-300 ring-1 ring-white/15">Reset progress</button>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endforeach

                {{-- At the bottom of the sheet, however few chapters the book has. --}}
                <div class="mt-auto pt-3">
                    <div class="hidden items-center justify-between gap-3 rounded-xl bg-white/5 px-3 py-3 text-sm [&:not(.hidden)]:flex" data-bind="offline">
                        <span class="min-w-0 text-haze-300" data-bind="offline-status" aria-live="polite"></span>
                        <span class="flex shrink-0 gap-2">
                            <button type="button" data-action="download-all" class="tap rounded-full bg-lime-500 px-3 py-1.5 font-semibold text-night-950"></button>
                            <button type="button" data-action="remove-downloads" class="tap hidden rounded-full px-3 py-1.5 text-haze-300 ring-1 ring-white/15">Remove</button>
                        </span>
                    </div>
                </div>
            </div>
        </dialog>

        <audio id="audio" preload="metadata"></audio>
    </body>
</html>
