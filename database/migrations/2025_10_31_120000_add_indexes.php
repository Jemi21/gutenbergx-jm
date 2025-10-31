<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS idx_books_downloads ON books_book (download_count DESC)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_books_gutenberg_id ON books_book (gutenberg_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_lang_code ON books_language (code)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_format_mime ON books_format (mime_type)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_format_book ON books_format (book_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bsj_book ON books_book_subjects (book_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_bb_book ON books_book_bookshelves (book_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_ba_book ON books_book_authors (book_id)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_books_downloads');
        DB::statement('DROP INDEX IF EXISTS idx_books_gutenberg_id');
        DB::statement('DROP INDEX IF EXISTS idx_lang_code');
        DB::statement('DROP INDEX IF EXISTS idx_format_mime');
        DB::statement('DROP INDEX IF EXISTS idx_format_book');
        DB::statement('DROP INDEX IF EXISTS idx_bsj_book');
        DB::statement('DROP INDEX IF EXISTS idx_bb_book');
        DB::statement('DROP INDEX IF EXISTS idx_ba_book');
    }
};


