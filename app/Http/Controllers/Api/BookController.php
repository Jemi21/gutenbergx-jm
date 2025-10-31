<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Annotations as OA;

use Illuminate\Support\Facades\File;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Gutendex Books API",
 *     description="Query Project Gutenberg books with filters and pagination"
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description="Default server"
 * )
 */
class BookController extends Controller
{
    /**
     * Return list of available bookshelves (genres).
     */
    public function genres(Request $req)
    {

        $q = DB::table('books_bookshelf as sh')
            ->select('sh.name')
            ->orderBy('sh.name');

        $names = $q->pluck('name')->all();

        return response()->json([
            'count' => count($names),
            'results' => $names,
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/books",
     *   summary="Retrieve books with filters, sorted by downloads desc",
     *   tags={"Books"},
     *   @OA\Parameter(name="ids", in="query", description="Comma-separated Gutenberg IDs", required=false, @OA\Schema(type="string", example="84,1342")),
     *   @OA\Parameter(name="languages", in="query", description="Comma-separated language codes", required=false, @OA\Schema(type="string", example="en,fr")),
     *   @OA\Parameter(name="mime_type", in="query", description="Comma-separated mime-types or prefixes", required=false, @OA\Schema(type="string", example="text/plain,application/epub+zip")),
     *   @OA\Parameter(name="topic", in="query", description="Comma-separated topics (matches subjects or bookshelves, partial, case-insensitive)", required=false, @OA\Schema(type="string", example="child,infant")),
     *   @OA\Parameter(name="author", in="query", description="Comma-separated author name fragments (partial, case-insensitive)", required=false, @OA\Schema(type="string", example="tolstoy")),
     *   @OA\Parameter(name="title", in="query", description="Title fragment (partial, case-insensitive)", required=false, @OA\Schema(type="string", example="war")),
     *   @OA\Parameter(name="search", in="query", description="Space-separated words to match in title OR author names (case-insensitive)", required=false, @OA\Schema(type="string", example="tolstoy war")),
     *   @OA\Parameter(name="page", in="query", description="Page number (1-based)", required=false, @OA\Schema(type="integer", default=1, minimum=1)),
     *   @OA\Parameter(name="limit", in="query", description="Items per page (max 25)", required=false, @OA\Schema(type="integer", default=25, maximum=25)),
     *   @OA\Parameter(name="has_cover", in="query", description="Only books that have image/* format", required=false, @OA\Schema(type="boolean", default=false)),
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="count", type="integer"),
     *       @OA\Property(property="next", type="string", nullable=true),
     *       @OA\Property(property="previous", type="string", nullable=true),
     *       @OA\Property(
     *         property="results",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           @OA\Property(property="id", type="integer", description="Gutenberg ID"),
     *           @OA\Property(property="title", type="string"),
     *           @OA\Property(
     *             property="authors",
     *             type="array",
     *             @OA\Items(type="object",
     *               @OA\Property(property="name", type="string"),
     *               @OA\Property(property="birth_year", type="integer", nullable=true),
     *               @OA\Property(property="death_year", type="integer", nullable=true)
     *             )
     *           ),
     *           @OA\Property(property="genre", type="string", nullable=true),
     *           @OA\Property(property="languages", type="array", @OA\Items(type="string")),
     *           @OA\Property(property="subjects", type="array", @OA\Items(type="string")),
     *           @OA\Property(property="bookshelves", type="array", @OA\Items(type="string")),
     *           @OA\Property(property="downloads", type="integer"),
     *           @OA\Property(
     *             property="download_links",
     *             type="array",
     *             @OA\Items(type="object",
     *               @OA\Property(property="mime_type", type="string"),
     *               @OA\Property(property="url", type="string")
     *             )
     *           )
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $req)
    {
        // Enforce pagination rules: 25 max per page
        $page = max(1, (int) $req->query('page', 1));
        $limit = min(25, (int) $req->query('limit', 25));

        // Parse filters (comma-separated multi-values)
        $ids = $req->filled('ids') ? array_values(array_filter(array_map('trim', explode(',', (string) $req->query('ids'))))) : [];
        $languages = $req->filled('languages') ? array_values(array_filter(array_map('trim', explode(',', (string) $req->query('languages'))))) : [];
        $mimeTypes = $req->filled('mime_type') ? array_values(array_filter(array_map('trim', explode(',', (string) $req->query('mime_type'))))) : [];
        $topics = $req->filled('topic') ? array_values(array_filter(array_map('trim', explode(',', (string) $req->query('topic'))))) : [];
        $authors = $req->filled('author') ? array_values(array_filter(array_map('trim', explode(',', (string) $req->query('author'))))) : [];
        $title = $req->filled('title') ? trim((string) $req->query('title')) : null;
        $search = $req->filled('search') ? preg_split('/\s+/', trim((string) $req->query('search'))) : [];

        // Base query: filters only (no select/order/pagination yet)
        $base = DB::table('books_book as b');

        // ids filter (Project Gutenberg IDs)
        if (!empty($ids)) {
            $idsInt = array_map('intval', $ids);
            $base->whereIn('b.gutenberg_id', $idsInt);
        }

        // language filter -> join book_languages -> language
        if (!empty($languages)) {
            $base->join('books_book_languages as bl', 'bl.book_id', '=', 'b.id')
                 ->join('books_language as lang', 'lang.id', '=', 'bl.language_id')
                 ->whereIn('lang.code', $languages);
        }

        // mime-type filter -> join formats
        if (!empty($mimeTypes)) {
            $base->join('books_format as f', 'f.book_id', '=', 'b.id')
                 ->where(function ($q) use ($mimeTypes) {
                     foreach ($mimeTypes as $mt) {
                         $q->orWhere('f.mime_type', 'ilike', $mt)
                           ->orWhere('f.mime_type', 'ilike', rtrim($mt, '%') . '%');
                     }
                 });
        }

        // only books with cover images (exists image/* format)
        if ($req->boolean('has_cover')) {
            $base->whereExists(function($q) {
                $q->from('books_format as fimg')
                  ->whereColumn('fimg.book_id', 'b.id')
                  ->where('fimg.mime_type', 'ilike', 'image/%');
            });
        }

        // topic filter (subjects or bookshelves) case-insensitive partial
        if (!empty($topics)) {
            $base->leftJoin('books_book_subjects as bsj', 'bsj.book_id', '=', 'b.id')
                 ->leftJoin('books_subject as subj', 'subj.id', '=', 'bsj.subject_id')
                 ->leftJoin('books_book_bookshelves as bb', 'bb.book_id', '=', 'b.id')
                 ->leftJoin('books_bookshelf as shelf', 'shelf.id', '=', 'bb.bookshelf_id')
                 ->where(function ($q) use ($topics) {
                     foreach ($topics as $topic) {
                         $q->orWhereRaw('subj.name ILIKE ?', ['%' . $topic . '%'])
                           ->orWhereRaw('shelf.name ILIKE ?', ['%' . $topic . '%']);
                     }
                 });
        }

        // author filter (partial, case-insensitive)
        if (!empty($authors)) {
            $base->join('books_book_authors as ba', 'ba.book_id', '=', 'b.id')
                 ->join('books_author as a', 'a.id', '=', 'ba.author_id')
                 ->where(function ($q) use ($authors) {
                     foreach ($authors as $author) {
                         $q->orWhereRaw('a.name ILIKE ?', ['%' . $author . '%']);
                     }
                 });
        }

        // title filter (partial, case-insensitive)
        if (!empty($title)) {
            $base->whereRaw('b.title ILIKE ?', ['%' . $title . '%']);
        }

        // search words: title OR author matches, for each word
        if (!empty($search)) {
            $base->where(function($outer) use ($search) {
                foreach ($search as $word) {
                    if ($word === '') { continue; }
                    $outer->where(function($q) use ($word) {
                        $q->orWhereRaw('b.title ILIKE ?', ['%' . $word . '%'])
                          ->orWhereExists(function($sq) use ($word) {
                              $sq->from('books_book_authors as sba')
                                 ->join('books_author as sa', 'sa.id', '=', 'sba.author_id')
                                 ->whereColumn('sba.book_id', 'b.id')
                                 ->whereRaw('sa.name ILIKE ?', ['%' . $word . '%']);
                          });
                    });
                }
            });
        }

        // Total count (distinct books)
        $total = (int) (clone $base)->distinct()->count('b.id');

        // Page of IDs ordered by downloads desc, grouped to avoid duplicates
        $pageIds = (clone $base)
            ->select('b.id', 'b.download_count')
            ->groupBy('b.id', 'b.download_count')
            ->orderBy('b.download_count', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->pluck('b.id')
            ->all();

        if (empty($pageIds)) {
            $baseUrl = url('/api/books');
            $params = $req->query();
            $params['limit'] = $limit;
            $next = ($page * $limit < $total) ? $baseUrl . '?' . http_build_query(array_merge($params, ['page' => $page + 1])) : null;
            $previous = ($page > 1) ? $baseUrl . '?' . http_build_query(array_merge($params, ['page' => $page - 1])) : null;

            return response()->json([
                'count' => $total,
                'next' => $next,
                'previous' => $previous,
                'results' => [],
            ]);
        }

        // Fetch core book rows for page
        $books = DB::table('books_book as b')
            ->select('b.id', 'b.gutenberg_id', 'b.title', 'b.download_count')
            ->whereIn('b.id', $pageIds)
            ->get()
            ->keyBy('id');

        // Load relations for these books
        $authorsRows = DB::table('books_book_authors as ba')
            ->join('books_author as a', 'a.id', '=', 'ba.author_id')
            ->whereIn('ba.book_id', $pageIds)
            ->select('ba.book_id', 'a.name', 'a.birth_year', 'a.death_year')
            ->get();

        $languagesRows = DB::table('books_book_languages as bl')
            ->join('books_language as l', 'l.id', '=', 'bl.language_id')
            ->whereIn('bl.book_id', $pageIds)
            ->select('bl.book_id', 'l.code')
            ->get();

        $subjectsRows = DB::table('books_book_subjects as bsj')
            ->join('books_subject as s', 's.id', '=', 'bsj.subject_id')
            ->whereIn('bsj.book_id', $pageIds)
            ->select('bsj.book_id', 's.name')
            ->get();

        $shelvesRows = DB::table('books_book_bookshelves as bb')
            ->join('books_bookshelf as sh', 'sh.id', '=', 'bb.bookshelf_id')
            ->whereIn('bb.book_id', $pageIds)
            ->select('bb.book_id', 'sh.name')
            ->get();

        $formatsRows = DB::table('books_format as f')
            ->whereIn('f.book_id', $pageIds)
            ->select('f.book_id', 'f.mime_type', 'f.url')
            ->get();

        // Assemble related data into maps
        $bookIdToAuthors = [];
        foreach ($authorsRows as $row) {
            $bookIdToAuthors[$row->book_id][] = [
                'name' => $row->name,
                'birth_year' => $row->birth_year,
                'death_year' => $row->death_year,
            ];
        }

        $bookIdToLanguages = [];
        foreach ($languagesRows as $row) {
            $bookIdToLanguages[$row->book_id][] = $row->code;
        }

        $bookIdToSubjects = [];
        foreach ($subjectsRows as $row) {
            $existingSubjects = $bookIdToSubjects[$row->book_id] ?? [];
            $existingSubjects[] = $row->name;
            $bookIdToSubjects[$row->book_id] = $existingSubjects;
        }

        $bookIdToShelves = [];
        foreach ($shelvesRows as $row) {
            $existingShelves = $bookIdToShelves[$row->book_id] ?? [];
            $existingShelves[] = $row->name;
            $bookIdToShelves[$row->book_id] = $existingShelves;
        }

        $bookIdToFormats = [];
        foreach ($formatsRows as $row) {
            $bookIdToFormats[$row->book_id][] = [
                'mime_type' => $row->mime_type,
                'url' => $row->url,
            ];
        }

        // Maintain order as in pageIds (already by downloads desc)
        $results = [];
        foreach ($pageIds as $bookId) {
            $b = $books[$bookId];
            $subjectsList = $bookIdToSubjects[$bookId] ?? [];
            $shelvesList = $bookIdToShelves[$bookId] ?? [];

            // Derive genre from first bookshelf or subject
            $genre = null;
            if (!empty($shelvesList)) {
                $genre = $shelvesList[0];
            } elseif (!empty($subjectsList)) {
                $genre = $subjectsList[0];
            }

            $results[] = [
                'id' => $b->gutenberg_id,
                'title' => $b->title,
                'authors' => $bookIdToAuthors[$bookId] ?? [],
                'genre' => $genre,
                'languages' => $bookIdToLanguages[$bookId] ?? [],
                'subjects' => $subjectsList,
                'bookshelves' => $shelvesList,
                'downloads' => $b->download_count,
                'download_links' => $bookIdToFormats[$bookId] ?? [],
            ];
        }

        // Build next/previous URLs relative to our API
        $base = url('/api/books');
        $queryParams = $req->query();
        $queryParams['limit'] = $limit;

        $next = null;
        if ($page * $limit < $total) {
            $queryParams['page'] = $page + 1;
            $next = $base . '?' . http_build_query($queryParams);
        }

        $previous = null;
        if ($page > 1) {
            $queryParams['page'] = $page - 1;
            $previous = $base . '?' . http_build_query($queryParams);
        }

        return response()->json([
            'count' => $total,
            'next' => $next,
            'previous' => $previous,
            'results' => $results,
        ]);
    }
}
