<?php

namespace App\Http\Controllers\Booker;

use App\Http\Controllers\Controller;
use App\Models\BkrBlock;
use App\Models\BkrBlockGroup;
use App\Models\BkrBook;
use App\Models\BkrPage;
use App\Models\BkrSpace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookerController extends Controller
{
    private const VISIBILITY = ['private', 'friends', 'registered', 'public'];
    private const STRUCTURE_MODES = ['tree', 'flat'];
    private const BLOCK_TYPES = ['markdown', 'excalidraw', 'svg', 'table', 'code', 'callout', 'divider', 'embed', 'checklist'];
    private const BLOCK_ROLES = ['content', 'note', 'source', 'todo', 'ai_response'];
    private const BLOCK_STATUSES = ['draft', 'published', 'archived'];

    public function spaces(Request $request)
    {
        $query = BkrSpace::query()
            ->where('user_id', $request->user()->id)
            ->withCount('books')
            ->when(! $request->boolean('include_archived'), fn ($q) => $q->where('is_archived', false))
            ->orderBy('sort_order')
            ->orderBy('title');

        return response()->json($query->get()->map(fn (BkrSpace $space) => $this->presentSpace($space))->values());
    }

    public function storeSpace(Request $request)
    {
        $data = $this->validateSpace($request);
        $space = BkrSpace::create($this->spacePayload($data, $request->user()->id));

        return response()->json($this->presentSpace($space), 201);
    }

    public function updateSpace(Request $request, string $id)
    {
        $space = $this->spaceForUser($request, $id);
        $data = $this->validateSpace($request, false);
        $space->update($this->spacePayload(array_merge($space->toArray(), $data), $request->user()->id, false, array_keys($data)));

        return response()->json($this->presentSpace($space->fresh()));
    }

    public function destroySpace(Request $request, string $id)
    {
        $this->spaceForUser($request, $id)->delete();

        return response()->json(['id' => $id]);
    }

    public function books(Request $request)
    {
        $query = BkrBook::query()
            ->where('user_id', $request->user()->id)
            ->with(['space:id,title,slug,visibility'])
            ->withCount('pages')
            ->when(! $request->boolean('include_archived'), fn ($q) => $q->where('is_archived', false));

        if ($request->filled('space_id')) {
            $query->where('space_id', $request->get('space_id'));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->get('q'));
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $books = $query->orderBy('sort_order')->orderBy('title')->get();

        return response()->json($books->map(fn (BkrBook $book) => $this->presentBook($book))->values());
    }

    public function showBook(Request $request, string $id)
    {
        $book = $this->bookForUser($request, $id)->load([
            'space:id,title,slug,visibility',
            'pages' => fn ($pages) => $pages
                ->when(! $request->boolean('include_archived'), fn ($q) => $q->where('is_archived', false))
                ->withCount('blockGroups'),
        ]);

        return response()->json($this->presentBook($book, true));
    }

    public function storeBook(Request $request)
    {
        $data = $this->validateBook($request);
        $userId = $request->user()->id;
        $this->assertOptionalSpace($userId, $data['space_id'] ?? null);

        $book = BkrBook::create($this->bookPayload($data, $userId));

        return response()->json($this->presentBook($book->load('space:id,title,slug,visibility')), 201);
    }

    public function updateBook(Request $request, string $id)
    {
        $book = $this->bookForUser($request, $id);
        $data = $this->validateBook($request, false);
        $this->assertOptionalSpace($request->user()->id, $data['space_id'] ?? null);
        $book->update($this->bookPayload(array_merge($book->toArray(), $data), $request->user()->id, false, array_keys($data)));

        return response()->json($this->presentBook($book->fresh()->load('space:id,title,slug,visibility')));
    }

    public function destroyBook(Request $request, string $id)
    {
        $this->bookForUser($request, $id)->delete();

        return response()->json(['id' => $id]);
    }

    public function pages(Request $request)
    {
        $query = BkrPage::query()
            ->where('user_id', $request->user()->id)
            ->withCount('blockGroups')
            ->when(! $request->boolean('include_archived'), fn ($q) => $q->where('is_archived', false));

        if ($request->filled('book_id')) {
            $query->where('book_id', $request->get('book_id'));
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->get('parent_id'));
        }

        $pages = $query->orderBy('sort_order')->orderBy('title')->get();

        return response()->json($pages->map(fn (BkrPage $page) => $this->presentPage($page))->values());
    }

    public function showPage(Request $request, string $id)
    {
        $relations = [
            'book:id,title,slug,structure_mode,visibility',
            'parent:id,title',
            'blockGroups.masterBlock',
        ];

        if ($request->boolean('include_versions')) {
            $relations[] = 'blockGroups.blocks';
        }

        $page = $this->pageForUser($request, $id)->load($relations);

        return response()->json($this->presentPage($page, true, $request->boolean('include_versions')));
    }

    public function storePage(Request $request)
    {
        $data = $this->validatePage($request);
        $userId = $request->user()->id;
        $this->assertBook($userId, $data['book_id']);
        $this->assertOptionalPage($userId, $data['parent_id'] ?? null, $data['book_id']);

        $page = BkrPage::create($this->pagePayload($data, $userId));

        return response()->json($this->presentPage($page), 201);
    }

    public function updatePage(Request $request, string $id)
    {
        $page = $this->pageForUser($request, $id);
        $data = $this->validatePage($request, false);
        $bookId = $data['book_id'] ?? $page->book_id;
        $this->assertBook($request->user()->id, $bookId);
        $this->assertOptionalPage($request->user()->id, $data['parent_id'] ?? null, $bookId, $page->id);
        $page->update($this->pagePayload(array_merge($page->toArray(), $data), $request->user()->id, false, array_keys($data)));

        return response()->json($this->presentPage($page->fresh()));
    }

    public function destroyPage(Request $request, string $id)
    {
        $this->pageForUser($request, $id)->delete();

        return response()->json(['id' => $id]);
    }

    public function storeBlockGroup(Request $request)
    {
        $data = $this->validateBlockGroup($request);
        $userId = $request->user()->id;
        $this->assertPage($userId, $data['page_id']);

        $group = DB::transaction(function () use ($data, $userId) {
            $group = BkrBlockGroup::create($this->blockGroupPayload($data, $userId));
            $block = BkrBlock::create($this->blockPayload(array_merge($data, [
                'group_id' => $group->id,
                'version_number' => 1,
                'status' => $data['status'] ?? 'draft',
            ]), $userId));
            $group->update(['master_block_id' => $block->id]);

            return $group->fresh()->load('masterBlock');
        });

        return response()->json($this->presentBlockGroup($group), 201);
    }

    public function updateBlockGroup(Request $request, string $id)
    {
        $group = $this->blockGroupForUser($request, $id);
        $data = $this->validateBlockGroup($request, false);

        if (isset($data['page_id'])) {
            $this->assertPage($request->user()->id, $data['page_id']);
        }

        if (isset($data['master_block_id'])) {
            $this->assertBlock($request->user()->id, $data['master_block_id'], $group->id);
        }

        $group->update($this->blockGroupPayload(array_merge($group->toArray(), $data), $request->user()->id, false, array_keys($data)));

        return response()->json($this->presentBlockGroup($group->fresh()->load('masterBlock')));
    }

    public function destroyBlockGroup(Request $request, string $id)
    {
        $group = $this->blockGroupForUser($request, $id);

        DB::transaction(function () use ($group) {
            $group->blocks()->delete();
            $group->delete();
        });

        return response()->json(['id' => $id]);
    }

    public function versions(Request $request, string $groupId)
    {
        $group = $this->blockGroupForUser($request, $groupId)->load('blocks');

        return response()->json($group->blocks->map(fn (BkrBlock $block) => $this->presentBlock($block))->values());
    }

    public function storeVersion(Request $request, string $groupId)
    {
        $group = $this->blockGroupForUser($request, $groupId);
        $data = $this->validateBlock($request);
        $makeMaster = $request->boolean('make_master', true);

        $block = DB::transaction(function () use ($group, $data, $makeMaster) {
            $nextVersion = ((int) BkrBlock::query()
                ->where('group_id', $group->id)
                ->max('version_number')) + 1;

            $block = BkrBlock::create($this->blockPayload(array_merge($data, [
                'group_id' => $group->id,
                'version_number' => $nextVersion,
            ]), $group->user_id));

            if ($makeMaster) {
                $group->update(['master_block_id' => $block->id]);
            }

            return $block;
        });

        return response()->json($this->presentBlock($block), 201);
    }

    public function updateBlock(Request $request, string $id)
    {
        $block = BkrBlock::query()->where('user_id', $request->user()->id)->findOrFail($id);
        $data = $this->validateBlock($request, false);
        $block->update($this->blockPayload(array_merge($block->toArray(), $data), $request->user()->id, false, array_keys($data)));

        return response()->json($this->presentBlock($block->fresh()));
    }

    public function destroyBlock(Request $request, string $id)
    {
        $block = BkrBlock::query()->where('user_id', $request->user()->id)->findOrFail($id);
        $group = $block->group;

        DB::transaction(function () use ($block, $group) {
            if ($group && $group->master_block_id === $block->id) {
                $nextMaster = BkrBlock::query()
                    ->where('group_id', $group->id)
                    ->where('id', '!=', $block->id)
                    ->orderByDesc('version_number')
                    ->first();
                $group->update(['master_block_id' => $nextMaster?->id]);
            }

            $block->delete();
        });

        return response()->json(['id' => $id]);
    }

    public function publishVersion(Request $request, string $groupId, string $blockId)
    {
        $group = $this->blockGroupForUser($request, $groupId);
        $block = $this->assertBlock($request->user()->id, $blockId, $group->id);

        DB::transaction(function () use ($group, $block) {
            $block->update([
                'status' => 'published',
                'published_at' => $block->published_at ?: now(),
            ]);
            $group->update(['master_block_id' => $block->id]);
        });

        return response()->json($this->presentBlockGroup($group->fresh()->load('masterBlock')));
    }

    public function reorderPages(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'string', 'size:26'],
            'items.*.sort_order' => ['required', 'integer'],
            'items.*.parent_id' => ['nullable', 'string', 'size:26'],
        ]);

        DB::transaction(function () use ($data, $request) {
            foreach ($data['items'] as $item) {
                $page = $this->pageForUser($request, $item['id']);
                if (! empty($item['parent_id'])) {
                    $this->assertOptionalPage($request->user()->id, $item['parent_id'], $page->book_id, $page->id);
                }
                $page->update([
                    'sort_order' => (int) $item['sort_order'],
                    'parent_id' => $item['parent_id'] ?? null,
                ]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function reorderBlocks(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'string', 'size:26'],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($data, $request) {
            foreach ($data['items'] as $item) {
                $this->blockGroupForUser($request, $item['id'])->update(['sort_order' => (int) $item['sort_order']]);
            }
        });

        return response()->json(['ok' => true]);
    }

    private function validateSpace(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:160'],
            'visibility' => ['nullable', Rule::in(self::VISIBILITY)],
            'sort_order' => ['nullable', 'integer'],
            'is_archived' => ['nullable', 'boolean'],
            'meta' => ['nullable', 'array'],
        ]);
    }

    private function validateBook(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'space_id' => ['nullable', 'string', 'size:26'],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'structure_mode' => ['nullable', Rule::in(self::STRUCTURE_MODES)],
            'visibility' => ['nullable', Rule::in(self::VISIBILITY)],
            'cover_color' => ['nullable', 'string', 'max:24'],
            'cover_svg_url' => ['nullable', 'string'],
            'cover_svg_text' => ['nullable', 'string'],
            'export_settings' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer'],
            'is_archived' => ['nullable', 'boolean'],
            'meta' => ['nullable', 'array'],
        ]);
    }

    private function validatePage(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'book_id' => [$creating ? 'required' : 'sometimes', 'string', 'size:26'],
            'parent_id' => ['nullable', 'string', 'size:26'],
            'title' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:160'],
            'visibility' => ['nullable', Rule::in(self::VISIBILITY)],
            'sort_order' => ['nullable', 'integer'],
            'is_archived' => ['nullable', 'boolean'],
            'meta' => ['nullable', 'array'],
        ]);
    }

    private function validateBlockGroup(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'page_id' => [$creating ? 'required' : 'sometimes', 'string', 'size:26'],
            'master_block_id' => ['nullable', 'string', 'size:26'],
            'type' => ['nullable', Rule::in(self::BLOCK_TYPES)],
            'role' => ['nullable', Rule::in(self::BLOCK_ROLES)],
            'visibility' => ['nullable', Rule::in(self::VISIBILITY)],
            'is_hidden_by_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'meta' => ['nullable', 'array'],
            'title' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'payload' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(self::BLOCK_STATUSES)],
        ]);
    }

    private function validateBlock(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'title' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'payload' => ['nullable', 'array'],
            'status' => ['nullable', Rule::in(self::BLOCK_STATUSES)],
            'published_at' => ['nullable', 'date'],
        ]);
    }

    private function spacePayload(array $data, string $userId, bool $creating = true, array $keys = []): array
    {
        return $this->onlyAllowed([
            'user_id' => $userId,
            'title' => $data['title'] ?? null,
            'slug' => $data['slug'] ?? null,
            'visibility' => $data['visibility'] ?? 'private',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_archived' => (bool) ($data['is_archived'] ?? false),
            'meta' => $data['meta'] ?? null,
        ], $creating, $keys);
    }

    private function bookPayload(array $data, string $userId, bool $creating = true, array $keys = []): array
    {
        return $this->onlyAllowed([
            'user_id' => $userId,
            'space_id' => $data['space_id'] ?? null,
            'title' => $data['title'] ?? null,
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'structure_mode' => $data['structure_mode'] ?? 'tree',
            'visibility' => $data['visibility'] ?? 'private',
            'cover_color' => $data['cover_color'] ?? null,
            'cover_svg_url' => $data['cover_svg_url'] ?? null,
            'cover_svg_text' => $data['cover_svg_text'] ?? null,
            'export_settings' => $data['export_settings'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_archived' => (bool) ($data['is_archived'] ?? false),
            'meta' => $data['meta'] ?? null,
        ], $creating, $keys);
    }

    private function pagePayload(array $data, string $userId, bool $creating = true, array $keys = []): array
    {
        return $this->onlyAllowed([
            'user_id' => $userId,
            'book_id' => $data['book_id'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'title' => $data['title'] ?? null,
            'slug' => $data['slug'] ?? null,
            'visibility' => $data['visibility'] ?? 'private',
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_archived' => (bool) ($data['is_archived'] ?? false),
            'meta' => $data['meta'] ?? null,
        ], $creating, $keys);
    }

    private function blockGroupPayload(array $data, string $userId, bool $creating = true, array $keys = []): array
    {
        $role = $data['role'] ?? 'content';

        return $this->onlyAllowed([
            'user_id' => $userId,
            'page_id' => $data['page_id'] ?? null,
            'master_block_id' => $data['master_block_id'] ?? null,
            'type' => $data['type'] ?? ($role === 'todo' ? 'checklist' : 'markdown'),
            'role' => $role,
            'visibility' => $data['visibility'] ?? 'private',
            'is_hidden_by_default' => (bool) ($data['is_hidden_by_default'] ?? $role !== 'content'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'meta' => $data['meta'] ?? null,
        ], $creating, $keys);
    }

    private function blockPayload(array $data, string $userId, bool $creating = true, array $keys = []): array
    {
        return $this->onlyAllowed([
            'user_id' => $userId,
            'group_id' => $data['group_id'] ?? null,
            'version_number' => (int) ($data['version_number'] ?? 1),
            'title' => $data['title'] ?? null,
            'content' => $data['content'] ?? null,
            'payload' => $data['payload'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'published_at' => $data['published_at'] ?? null,
        ], $creating, $keys);
    }

    private function onlyAllowed(array $payload, bool $creating, array $keys): array
    {
        if ($creating) {
            return $payload;
        }

        $allowed = array_fill_keys($keys, true);
        $allowed['user_id'] = true;

        return array_filter($payload, fn ($value, $key) => isset($allowed[$key]), ARRAY_FILTER_USE_BOTH);
    }

    private function spaceForUser(Request $request, string $id): BkrSpace
    {
        return BkrSpace::query()->where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function bookForUser(Request $request, string $id): BkrBook
    {
        return BkrBook::query()->where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function pageForUser(Request $request, string $id): BkrPage
    {
        return BkrPage::query()->where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function blockGroupForUser(Request $request, string $id): BkrBlockGroup
    {
        return BkrBlockGroup::query()->where('user_id', $request->user()->id)->findOrFail($id);
    }

    private function assertOptionalSpace(string $userId, ?string $spaceId): void
    {
        if ($spaceId && ! BkrSpace::query()->where('user_id', $userId)->where('id', $spaceId)->exists()) {
            abort(422, 'Space must belong to current user.');
        }
    }

    private function assertBook(string $userId, string $bookId): void
    {
        if (! BkrBook::query()->where('user_id', $userId)->where('id', $bookId)->exists()) {
            abort(422, 'Book must belong to current user.');
        }
    }

    private function assertPage(string $userId, string $pageId): void
    {
        if (! BkrPage::query()->where('user_id', $userId)->where('id', $pageId)->exists()) {
            abort(422, 'Page must belong to current user.');
        }
    }

    private function assertOptionalPage(string $userId, ?string $pageId, string $bookId, ?string $currentId = null): void
    {
        if (! $pageId) {
            return;
        }

        if ($currentId && $pageId === $currentId) {
            abort(422, 'Page cannot be its own parent.');
        }

        if (! BkrPage::query()->where('user_id', $userId)->where('book_id', $bookId)->where('id', $pageId)->exists()) {
            abort(422, 'Parent page must belong to the same book.');
        }
    }

    private function assertBlock(string $userId, string $blockId, string $groupId): BkrBlock
    {
        return BkrBlock::query()
            ->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->where('id', $blockId)
            ->firstOrFail();
    }

    private function presentSpace(BkrSpace $space): array
    {
        return [
            'id' => $space->id,
            'title' => $space->title,
            'slug' => $space->slug,
            'visibility' => $space->visibility,
            'sort_order' => $space->sort_order,
            'is_archived' => $space->is_archived,
            'meta' => $space->meta,
            'books_count' => $space->books_count ?? null,
            'created_at' => optional($space->created_at)->toISOString(),
            'updated_at' => optional($space->updated_at)->toISOString(),
        ];
    }

    private function presentBook(BkrBook $book, bool $full = false): array
    {
        $payload = [
            'id' => $book->id,
            'user_id' => $book->user_id,
            'space_id' => $book->space_id,
            'space' => $book->space ? $this->presentSpace($book->space) : null,
            'title' => $book->title,
            'slug' => $book->slug,
            'description' => $book->description,
            'structure_mode' => $book->structure_mode,
            'visibility' => $book->visibility,
            'cover_color' => $book->cover_color,
            'cover_svg_url' => $book->cover_svg_url,
            'cover_svg_text' => $book->cover_svg_text,
            'export_settings' => $book->export_settings,
            'sort_order' => $book->sort_order,
            'is_archived' => $book->is_archived,
            'meta' => $book->meta,
            'pages_count' => $book->pages_count ?? null,
            'created_at' => optional($book->created_at)->toISOString(),
            'updated_at' => optional($book->updated_at)->toISOString(),
        ];

        if ($full) {
            $payload['pages'] = $book->pages->map(fn (BkrPage $page) => $this->presentPage($page))->values();
        }

        return $payload;
    }

    private function presentPage(BkrPage $page, bool $full = false, bool $includeVersions = false): array
    {
        $payload = [
            'id' => $page->id,
            'user_id' => $page->user_id,
            'book_id' => $page->book_id,
            'parent_id' => $page->parent_id,
            'title' => $page->title,
            'slug' => $page->slug,
            'visibility' => $page->visibility,
            'sort_order' => $page->sort_order,
            'is_archived' => $page->is_archived,
            'meta' => $page->meta,
            'block_groups_count' => $page->block_groups_count ?? null,
            'created_at' => optional($page->created_at)->toISOString(),
            'updated_at' => optional($page->updated_at)->toISOString(),
        ];

        if ($full) {
            $payload['book'] = $page->book ? [
                'id' => $page->book->id,
                'title' => $page->book->title,
                'slug' => $page->book->slug,
                'structure_mode' => $page->book->structure_mode,
                'visibility' => $page->book->visibility,
            ] : null;
            $payload['parent'] = $page->parent ? [
                'id' => $page->parent->id,
                'title' => $page->parent->title,
            ] : null;
            $payload['block_groups'] = $page->blockGroups
                ->map(fn (BkrBlockGroup $group) => $this->presentBlockGroup($group, $includeVersions))
                ->values();
        }

        return $payload;
    }

    private function presentBlockGroup(BkrBlockGroup $group, bool $includeVersions = false): array
    {
        $payload = [
            'id' => $group->id,
            'user_id' => $group->user_id,
            'page_id' => $group->page_id,
            'master_block_id' => $group->master_block_id,
            'type' => $group->type,
            'role' => $group->role,
            'visibility' => $group->visibility,
            'is_hidden_by_default' => $group->is_hidden_by_default,
            'sort_order' => $group->sort_order,
            'meta' => $group->meta,
            'master_block' => $group->masterBlock ? $this->presentBlock($group->masterBlock) : null,
            'created_at' => optional($group->created_at)->toISOString(),
            'updated_at' => optional($group->updated_at)->toISOString(),
        ];

        if ($includeVersions && $group->relationLoaded('blocks')) {
            $payload['blocks'] = $group->blocks->map(fn (BkrBlock $block) => $this->presentBlock($block))->values();
        }

        return $payload;
    }

    private function presentBlock(BkrBlock $block): array
    {
        return [
            'id' => $block->id,
            'user_id' => $block->user_id,
            'group_id' => $block->group_id,
            'version_number' => $block->version_number,
            'title' => $block->title,
            'content' => $block->content,
            'payload' => $block->payload,
            'status' => $block->status,
            'published_at' => optional($block->published_at)->toISOString(),
            'created_at' => optional($block->created_at)->toISOString(),
            'updated_at' => optional($block->updated_at)->toISOString(),
        ];
    }
}
