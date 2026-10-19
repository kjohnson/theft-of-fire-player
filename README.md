# Theft of Fire — Audiobook Player

A mobile-first Laravel site for listening to *Theft of Fire* (Orbital Space #1) by Devon Eriksen, voiced by
Dain Little, Emma Rae, and Ashley Crossman. Based on the
[After Moses player](https://github.com/kjohnson/after-moses-player).

The audiobook has a short track for each of the book's five parts (One Way Ticket, Two Player Game, Three Body
Problem, Four Letter Words, Five Step Process). They're listed between the chapters, unnumbered, as
`part-1-one-way-ticket.mp3` and so on.

- Books and chapter list with per-chapter progress; finishing a book carries on into the next
- Resume where you left off (saved in the browser's localStorage)
- Back 15s / forward 30s
- Follow-along text: the ebook's text with the spoken word highlighted (tap a word to jump there)
- Works offline: the chapter that's playing and the next one are saved on the device automatically, and
  "Save all" in the chapters menu saves the whole book shown there. A service worker (`public/sw.js`) serves saved
  chapters, including seeking, and keeps the player itself available without a connection.
- Lock-screen and headphone controls via the Media Session API
- Installable to the home screen (web app manifest)

No database is required.

## Local development

```sh
composer install
npm install
cp .env.example .env && php artisan key:generate
composer run dev
```

> `php artisan serve` doesn't support HTTP range requests, so seeking in Safari/iOS only works against a real web server (Laravel Cloud, Herd, Valet, nginx).

## Adding a book

Each book has an entry in `books` in `config/audiobook.php` (in reading order) and its own folder in
`storage/app/private/`, named after its key:

```
storage/app/private/theft-of-fire/
    Theft of Fire - Devon Eriksen.epub
    audio/introduction.mp3, epigraph.mp3, part-1-one-way-ticket.mp3, chapter-01-what-the-cat-dragged-in.mp3, …
    transcripts/chapter-01-what-the-cat-dragged-in.json, …
    text/chapter-01.json, …              (written by audiobook:sync)
```

A book with an empty `chapters` list is shown as coming soon, with its `upcoming_chapters` titles dimmed in the
chapters menu; the player and `audiobook:sync` skip it until its chapters are added.

The audio and transcripts aren't committed to git; the synced text is. Its cover goes in `public/images/`
(1024×1024 JPEG, e.g. the MP3's embedded art: `ffmpeg -i chapter.mp3 -an -vf scale=1024:1024 cover.jpg`).

## Adding chapters

1. Drop the MP3 in the book's `audio/` folder (lowercase, no spaces, e.g. `chapter-04-some-title.mp3`).
2. Add an entry to the book's `chapters` with its title, file, and duration in seconds. Set `numbered` to
   `false` for tracks that aren't chapters, like the credits:

   ```sh
   ffprobe -v error -show_entries format=duration -of csv=p=0 storage/app/private/theft-of-fire/audio/chapter-04-some-title.mp3
   ```

3. Upload it to the bucket with `php artisan audiobook:upload s3`.

## Follow-along text

Put a WhisperX JSON transcript of each chapter in the book's `transcripts/` folder, named after its MP3, and
run `php artisan audiobook:sync` (or `php artisan audiobook:sync theft-of-fire 7` for one track of one book).
Chapters are found in the ebook by a "Chapter N" title matching their place in the list, or by their `epub`
key when the list doesn't line up with the ebook (Theft of Fire's list starts with the introduction and
epigraph, and has a track for each part's title, so every chapter has one). Tracks without text in the ebook,
like the part titles, are skipped:

```sh
whisperx storage/app/private/theft-of-fire/audio/chapter-04-mutually-assured-destruction.mp3 --model small.en --language en \
    --compute_type int8 --output_format json --output_dir storage/app/private/theft-of-fire/transcripts
php artisan audiobook:sync theft-of-fire 7
```

The synced text lands in the book's `text/` folder. Chapters without it don't show the text button.

## Deploying to Laravel Cloud

1. Push this repo to GitHub/GitLab/Bitbucket and create an application in Laravel Cloud.
2. Build command: `composer install --no-dev && npm ci && npm run build && php artisan optimize`
3. Deploy command: leave empty (there are no migrations to run).
4. Environment variables: `SESSION_DRIVER=cookie`, `CACHE_STORE=file` (no database needed).

### Hosting the audio in Object Storage

The MP3s are too large for the repo, so they live in a private bucket. Locally they sit in
each book's `audio/` folder in `storage/app/private` (git-ignored), outside the web root. The player only
receives signed links (`/audio/{book}/{chapter}?expires=…&signature=…`) that expire after `AUDIOBOOK_URL_LIFETIME` minutes (default 360),
are rate limited, and are refused when opened directly in a browser tab or embedded on another site. To set
up the bucket:

1. Add a **private** Laravel Object Storage bucket to the environment and mark it as the **default disk**
   (any disk name). Redeploy so the bucket's connection is injected. Listeners are then redirected to
   short-lived pre-signed URLs.
2. Copy one of the bucket's access keys (Organization > Resources > Object storage > your bucket >
   Access keys > View credentials) into your local `.env`:

   ```dotenv
   AWS_ACCESS_KEY_ID=…
   AWS_SECRET_ACCESS_KEY=…
   AWS_BUCKET=…
   AWS_ENDPOINT=…
   AWS_DEFAULT_REGION=auto
   ```

3. Upload the files:

   ```sh
   php artisan audiobook:upload s3
   ```

   This copies each book's `audio/*.mp3` and `text/*.json` from local storage into the same folders in the bucket,
   skipping files that are already uploaded unchanged. Run it again after adding chapters or re-running
   `audiobook:sync`. The player checks for new text every 5 minutes.

`AUDIOBOOK_DISK` can name a disk other than the default, but isn't needed on Laravel Cloud.
