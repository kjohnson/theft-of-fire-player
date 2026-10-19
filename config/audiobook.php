<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Series Details
    |--------------------------------------------------------------------------
    */

    'series' => 'Orbital Space',

    'author' => 'Devon Eriksen',

    /*
    |--------------------------------------------------------------------------
    | Audio Location
    |--------------------------------------------------------------------------
    |
    | Each book has its own folder (named after its key below) on a private
    | disk, holding its ebook and its "audio", "text" and "transcripts"
    | folders. Nothing is served from a public URL. On the local disk audio
    | is streamed through a signed route; on S3-compatible object storage
    | (e.g. a private Laravel Cloud bucket) listeners are redirected to a
    | short-lived pre-signed URL.
    |
    | Leave AUDIOBOOK_DISK unset to use the default disk: "local" here, and
    | the bucket attached as the default disk on Laravel Cloud.
    |
    | Signed player URLs expire after "url_lifetime" minutes. The player
    | fetches a fresh one automatically if a listener comes back later.
    |
    */

    'disk' => env('AUDIOBOOK_DISK'),

    'url_lifetime' => (int) env('AUDIOBOOK_URL_LIFETIME', 360),

    /*
    |--------------------------------------------------------------------------
    | Books
    |--------------------------------------------------------------------------
    |
    | In reading order. The key names the book's folder and appears in its
    | URLs. Finishing a book carries on into the next. The "label" shows
    | above the book's title in the player. A book with no chapters yet is
    | shown as coming soon, listing its "upcoming_chapters" titles.
    |
    | Running "php artisan audiobook:sync" matches each chapter's text in the
    | ebook against a WhisperX transcript of its audio (in "transcripts") and
    | writes word timings to the "text" folder, which the player reads.
    | Chapters are found in the ebook by a "Chapter N" title matching their
    | place in the list. Add an "epub" key to a chapter to point at a
    | specific file instead, as Theft of Fire's chapters do since its list
    | starts with the introduction and the epigraph, and each part's title
    | has its own track (the ebook only names the parts in its contents).
    |
    | Durations are in seconds, so the chapter list can show lengths before
    | any audio has been loaded. Get them with:
    | ffprobe -v error -show_entries format=duration -of csv=p=0 file.mp3
    |
    | Set "numbered" to false for tracks that aren't chapters, like the
    | credits or a part's title, so they're listed without a chapter number.
    | A chapter's "number" can be set instead, and a book's "chapter_name"
    | names its chapters.
    |
    */

    'books' => [
        'theft-of-fire' => [
            'title' => 'Theft of Fire',
            'label' => 'Book 1',
            'narrator' => 'Dain Little, Emma Rae, and Ashley Crossman',
            'cover' => 'images/theft-of-fire.jpg',
            'ebook' => 'Theft of Fire - Devon Eriksen.epub',
            'chapters' => [
                [
                    'title' => 'Introduction',
                    'file' => 'introduction.mp3',
                    'duration' => 17.06,
                    'numbered' => false,
                ],
                [
                    'title' => 'Epigraph',
                    'file' => 'epigraph.mp3',
                    'epub' => 'aanNi8Axnp0lJPKE.xhtml',
                    'duration' => 52.22,
                    'numbered' => false,
                ],
                [
                    'title' => 'One Way Ticket',
                    'file' => 'part-1-one-way-ticket.mp3',
                    'duration' => 4.96,
                    'numbered' => false,
                ],
                [
                    'title' => 'What the Cat Dragged In',
                    'file' => 'chapter-01-what-the-cat-dragged-in.mp3',
                    'epub' => 'yIlEnY2xAaXIU01l.xhtml',
                    'duration' => 1120.08,
                ],
                [
                    'title' => 'A List of Things That Won’t Happen',
                    'file' => 'chapter-02-a-list-of-things-that-wont-happen.mp3',
                    'epub' => 'GpbhjpUcSmH7OdBz.xhtml',
                    'duration' => 1763.55,
                ],
                [
                    'title' => 'We Don’t Go to Sedna',
                    'file' => 'chapter-03-we-dont-go-to-sedna.mp3',
                    'epub' => 'g44wawahBQiNTUdu.xhtml',
                    'duration' => 1248.84,
                ],
                [
                    'title' => 'Mutually Assured Destruction',
                    'file' => 'chapter-04-mutually-assured-destruction.mp3',
                    'epub' => 'o7Qw1oVSlhrWfgpV.xhtml',
                    'duration' => 2586.15,
                ],
                [
                    'title' => 'Barsoom',
                    'file' => 'chapter-05-barsoom.mp3',
                    'epub' => 'BoRftgAmCCVtzkWX.xhtml',
                    'duration' => 1078.10,
                ],
                [
                    'title' => 'Two Player Game',
                    'file' => 'part-2-two-player-game.mp3',
                    'duration' => 4.96,
                    'numbered' => false,
                ],
                [
                    'title' => 'Frog and Scorpion',
                    'file' => 'chapter-06-frog-and-scorpion.mp3',
                    'epub' => 'KEolIGddGpTa5iLZ.xhtml',
                    'duration' => 1654.99,
                ],
                [
                    'title' => 'The Third Wheel',
                    'file' => 'chapter-07-the-third-wheel.mp3',
                    'epub' => 'r1DIiyebDJXlkV2e.xhtml',
                    'duration' => 1738.32,
                ],
                [
                    'title' => 'Daddy Isn’t Here',
                    'file' => 'chapter-08-daddy-isnt-here.mp3',
                    'epub' => 'OBdfKJl0u4gar3ax.xhtml',
                    'duration' => 2280.80,
                ],
                [
                    'title' => 'Got Nothing But Time',
                    'file' => 'chapter-09-got-nothing-but-time.mp3',
                    'epub' => 'Ql47hHx4v0dmCftu.xhtml',
                    'duration' => 2392.40,
                ],
                [
                    'title' => 'A Blank Map',
                    'file' => 'chapter-10-a-blank-map.mp3',
                    'epub' => 'am8x8jx9FLU74lx6.xhtml',
                    'duration' => 2401.52,
                ],
                [
                    'title' => 'Half',
                    'file' => 'chapter-11-half.mp3',
                    'epub' => 'kFCmX7T3z13JbllY.xhtml',
                    'duration' => 3811.81,
                ],
                [
                    'title' => 'Throw and Catch',
                    'file' => 'chapter-12-throw-and-catch.mp3',
                    'epub' => '1w2iygfwiBuzGAZ0.xhtml',
                    'duration' => 2979.55,
                ],
                [
                    'title' => 'Three Body Problem',
                    'file' => 'part-3-three-body-problem.mp3',
                    'duration' => 4.96,
                    'numbered' => false,
                ],
                [
                    'title' => 'You Need Therapy',
                    'file' => 'chapter-13-you-need-therapy.mp3',
                    'epub' => 'k9TYQGBijf9SxIiw.xhtml',
                    'duration' => 1991.31,
                ],
                [
                    'title' => 'Word and Hand',
                    'file' => 'chapter-14-word-and-hand.mp3',
                    'epub' => 'sHJuZy6ILt1nf9DK.xhtml',
                    'duration' => 1326.21,
                ],
                [
                    'title' => 'Slingshot',
                    'file' => 'chapter-15-slingshot.mp3',
                    'epub' => 'U2I7QtcbDoB8zdEX.xhtml',
                    'duration' => 2355.28,
                ],
                [
                    'title' => 'The Snark',
                    'file' => 'chapter-16-the-snark.mp3',
                    'epub' => 'XFpcnjyksvKJXNoR.xhtml',
                    'duration' => 2382.81,
                ],
                [
                    'title' => 'Anomalous Trajectory',
                    'file' => 'chapter-17-anomalous-trajectory.mp3',
                    'epub' => 'GRXL3RWtcKNhzg7o.xhtml',
                    'duration' => 1695.29,
                ],
                [
                    'title' => 'Stunt Flying',
                    'file' => 'chapter-18-stunt-flying.mp3',
                    'epub' => '7gPAWBS0GdXQkcas.xhtml',
                    'duration' => 1026.98,
                ],
                [
                    'title' => 'Four Letter Words',
                    'file' => 'part-4-four-letter-words.mp3',
                    'duration' => 4.96,
                    'numbered' => false,
                ],
                [
                    'title' => 'This Is What I Do',
                    'file' => 'chapter-19-this-is-what-i-do.mp3',
                    'epub' => 'skGrE7mFIi6xS3OO.xhtml',
                    'duration' => 1493.24,
                ],
                [
                    'title' => 'Dodge THIS',
                    'file' => 'chapter-20-dodge-this.mp3',
                    'epub' => 'UZ7aXbCJAEt1AKOi.xhtml',
                    'duration' => 1395.23,
                ],
                [
                    'title' => 'Plenty of Hot Water',
                    'file' => 'chapter-21-plenty-of-hot-water.mp3',
                    'epub' => 'oZLIh6W8aFxeX81V.xhtml',
                    'duration' => 1353.01,
                ],
                [
                    'title' => 'We Always End Up Fighting',
                    'file' => 'chapter-22-we-always-end-up-fighting.mp3',
                    'epub' => 'KF4pPCpVjVd97T7h.xhtml',
                    'duration' => 1916.81,
                ],
                [
                    'title' => 'Universal Solution',
                    'file' => 'chapter-23-universal-solution.mp3',
                    'epub' => '453xCwe4xemM5X7l.xhtml',
                    'duration' => 1967.31,
                ],
                [
                    'title' => 'And the Horse You Rode In On',
                    'file' => 'chapter-24-and-the-horse-you-rode-in-on.mp3',
                    'epub' => 'xcTZkraJstFWPovU.xhtml',
                    'duration' => 791.69,
                ],
                [
                    'title' => 'Sucker Punch',
                    'file' => 'chapter-25-sucker-punch.mp3',
                    'epub' => 'TddIc5TjgrvbwApW.xhtml',
                    'duration' => 2173.60,
                ],
                [
                    'title' => 'Trust',
                    'file' => 'chapter-26-trust.mp3',
                    'epub' => 'wBn3FCMDawlVMygm.xhtml',
                    'duration' => 1705.93,
                ],
                [
                    'title' => 'Agni Pariksha',
                    'file' => 'chapter-27-agni-pariksha.mp3',
                    'epub' => 'tIgD27Sz63Nz8Bxc.xhtml',
                    'duration' => 1552.74,
                ],
                [
                    'title' => 'Five Step Process',
                    'file' => 'part-5-five-step-process.mp3',
                    'duration' => 5.07,
                    'numbered' => false,
                ],
                [
                    'title' => 'The Same Deep Water',
                    'file' => 'chapter-28-the-same-deep-water.mp3',
                    'epub' => '3eHAPzqFc6rBDmEG.xhtml',
                    'duration' => 1854.22,
                ],
                [
                    'title' => 'The Magic Trick',
                    'file' => 'chapter-29-the-magic-trick.mp3',
                    'epub' => 'MNOEWV7GyduseDSg.xhtml',
                    'duration' => 743.03,
                ],
                [
                    'title' => 'We’re Not Good People',
                    'file' => 'chapter-30-were-not-good-people.mp3',
                    'epub' => 'aAmAJ2hogOUrmPh7.xhtml',
                    'duration' => 2061.82,
                ],
                [
                    'title' => 'Foxtrot Uniform Two',
                    'file' => 'chapter-31-foxtrot-uniform-two.mp3',
                    'epub' => 'q9OQIbEosN1GBrOm.xhtml',
                    'duration' => 1861.25,
                ],
                [
                    'title' => 'What You Wish For',
                    'file' => 'chapter-32-what-you-wish-for.mp3',
                    'epub' => 'OFL5UzWjRYZzVREG.xhtml',
                    'duration' => 986.36,
                ],
                [
                    'title' => 'The Edge of Sunlight',
                    'file' => 'chapter-33-the-edge-of-sunlight.mp3',
                    'epub' => 'hqPyNgPZAFRYcD4n.xhtml',
                    'duration' => 1297.01,
                ],
                [
                    'title' => 'Outro',
                    'file' => 'outro.mp3',
                    'duration' => 59.85,
                    'numbered' => false,
                ],
            ],
        ],
    ],

];
