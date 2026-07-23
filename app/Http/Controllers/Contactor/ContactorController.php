<?php

namespace App\Http\Controllers\Contactor;

use App\Http\Controllers\Controller;
use App\Models\CtrContact;
use App\Models\CtrContent;
use App\Models\CtrDetail;
use App\Models\CtrRelation;
use App\Models\EvtTag;
use App\Models\TskTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactorController extends Controller
{
    public function contacts(Request $request)
    {
        $sort = in_array($request->get('sort'), ['name', 'group', 'last_contact_at', 'sort_order'], true)
            ? $request->get('sort')
            : 'last_contact_at';
        $dir = $request->get('dir') === 'asc' ? 'asc' : 'desc';

        $query = CtrContact::query()
            ->where('user_id', $request->user()->id)
            ->active()
            ->with('detailsRows')
            ->orderBy($sort, $dir)
            ->orderBy('name');

        if ($request->filled('group') && $request->get('group') !== 'all') {
            $query->where('group', $request->get('group'));
        }

        if ($request->boolean('pinned')) {
            $query->where('is_pinned', true)
                ->orderBy('sort_order')
                ->orderBy('name');
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->get('q'));
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('nickname', 'like', "%{$q}%")
                    ->orWhere('role', 'like', "%{$q}%")
                    ->orWhere('company', 'like', "%{$q}%")
                    ->orWhere('met_context', 'like', "%{$q}%")
                    ->orWhereHas('detailsRows', function ($details) use ($q) {
                        $details->where('value', 'like', "%{$q}%")
                            ->orWhere('label', 'like', "%{$q}%");
                    });
            });
        }

        return response()->json(
            $query->get()->map(fn (CtrContact $contact) => $this->presentContact($contact))->values()
        );
    }

    public function showContact(Request $request, string $id)
    {
        $contact = $this->contactForUser($request, $id)->load('detailsRows');

        return response()->json($this->presentContact($contact));
    }

    public function storeContact(Request $request)
    {
        $data = $this->validateContact($request);
        $userId = $request->user()->id;

        $contact = DB::transaction(function () use ($data, $userId) {
            $contact = CtrContact::create($this->contactPayload($data, $userId));
            $this->syncDetails($contact, $data['details'] ?? []);

            return $contact;
        });

        return response()->json($this->presentContact($contact->load('detailsRows')), 201);
    }

    public function updateContact(Request $request, string $id)
    {
        $contact = $this->contactForUser($request, $id);
        $data = $this->validateContact($request, false);

        DB::transaction(function () use ($contact, $data) {
            $contact->update($this->contactPayload($data, $contact->user_id, false));
            if (array_key_exists('details', $data)) {
                $this->syncDetails($contact, $data['details'] ?? []);
            }
        });

        return response()->json($this->presentContact($contact->fresh()->load('detailsRows')));
    }

    public function destroyContact(Request $request, string $id)
    {
        $contact = $this->contactForUser($request, $id);
        $contact->update(['is_archived' => true]);
        $contact->delete();

        return response()->json(['id' => $id]);
    }

    public function contents(Request $request)
    {
        $query = CtrContent::query()
            ->where('user_id', $request->user()->id)
            ->with('contact:id,name,nickname,avatar,avatar_url')
            ->with(['mentionLinks', 'tags'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at');

        if (! $request->boolean('include_expert')) {
            $query->where('is_expert', false);
        }

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->get('contact_id'));
        }

        if ($request->filled('kind') && $request->get('kind') !== 'all') {
            $query->where('kind', $request->get('kind'));
        }

        $limit = min(max((int) $request->get('limit', 100), 1), 500);

        return response()->json(
            $query->limit($limit)->get()->map(fn (CtrContent $content) => $this->presentContent($content))->values()
        );
    }

    public function storeContent(Request $request)
    {
        $data = $this->validateContent($request);
        $userId = $request->user()->id;
        $contact = $this->contactForUser($request, $data['contact_id']);
        $this->assertOptionalTask($userId, $data['tasker_task_id'] ?? null);
        $this->assertTags($userId, $data['tag_ids'] ?? []);
        $this->assertMentionContacts($userId, $this->mentionContactIds($data));

        $content = DB::transaction(function () use ($data, $userId, $contact) {
            $content = CtrContent::create($this->contentPayload($data, $userId));
            $this->syncContentTags($content, $data['tag_ids'] ?? []);
            $this->syncContentMentions($content, $data);
            $this->syncLastContact($contact);

            return $content;
        });

        return response()->json($this->presentContent($content->load(['contact:id,name,nickname,avatar,avatar_url', 'mentionLinks', 'tags'])), 201);
    }

    public function updateContent(Request $request, string $id)
    {
        $content = CtrContent::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $data = $this->validateContent($request, false);
        if (isset($data['contact_id'])) {
            $this->contactForUser($request, $data['contact_id']);
        }
        $this->assertOptionalTask($request->user()->id, $data['tasker_task_id'] ?? null);
        $this->assertTags($request->user()->id, $data['tag_ids'] ?? []);
        $this->assertMentionContacts($request->user()->id, $this->mentionContactIds($data));

        DB::transaction(function () use ($content, $data, $request) {
            $oldContactId = $content->contact_id;
            $payload = $this->contentPayload(array_merge($content->toArray(), $data), $request->user()->id);
            $content->update($payload);
            if (array_key_exists('tag_ids', $data)) {
                $this->syncContentTags($content, $data['tag_ids'] ?? []);
            }
            if (array_key_exists('mentioned_contact_ids', $data) || array_key_exists('mentions', $data)) {
                $this->syncContentMentions($content, $data);
            }

            $this->syncLastContact($this->contactForUser($request, $content->contact_id));
            if ($oldContactId !== $content->contact_id) {
                $this->syncLastContact($this->contactForUser($request, $oldContactId));
            }
        });

        return response()->json($this->presentContent($content->fresh()->load(['contact:id,name,nickname,avatar,avatar_url', 'mentionLinks', 'tags'])));
    }

    public function destroyContent(Request $request, string $id)
    {
        $content = CtrContent::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
        $contact = $this->contactForUser($request, $content->contact_id);

        DB::transaction(function () use ($content, $contact) {
            $content->delete();
            $this->syncLastContact($contact);
        });

        return response()->json(['id' => $id]);
    }

    public function relations(Request $request)
    {
        $query = CtrRelation::query()
            ->where('user_id', $request->user()->id)
            ->with(['contactA:id,name,nickname,avatar,avatar_url', 'contactB:id,name,nickname,avatar,avatar_url'])
            ->orderBy('kind')
            ->orderByDesc('created_at');

        if ($request->filled('contact_id')) {
            $contactId = $request->get('contact_id');
            $query->where(function ($inner) use ($contactId) {
                $inner->where('contact_a_id', $contactId)
                    ->orWhere('contact_b_id', $contactId);
            });
        }

        return response()->json(
            $query->get()->map(fn (CtrRelation $relation) => $this->presentRelation($relation))->values()
        );
    }

    public function storeRelation(Request $request)
    {
        $data = $this->validateRelation($request);
        $userId = $request->user()->id;
        [$contactA, $contactB] = $this->normalizeRelationPair($data['contact_a_id'], $data['contact_b_id']);
        $this->assertRelationContacts($request, $contactA, $contactB);

        $relation = CtrRelation::create(array_merge($data, [
            'user_id' => $userId,
            'contact_a_id' => $contactA,
            'contact_b_id' => $contactB,
        ]));

        return response()->json($this->presentRelation($relation->load(['contactA:id,name,nickname,avatar,avatar_url', 'contactB:id,name,nickname,avatar,avatar_url'])), 201);
    }

    public function updateRelation(Request $request, string $id)
    {
        $relation = CtrRelation::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $data = $this->validateRelation($request, false);
        [$contactA, $contactB] = $this->normalizeRelationPair(
            $data['contact_a_id'] ?? $relation->contact_a_id,
            $data['contact_b_id'] ?? $relation->contact_b_id
        );
        $this->assertRelationContacts($request, $contactA, $contactB);

        $relation->update(array_merge($data, [
            'contact_a_id' => $contactA,
            'contact_b_id' => $contactB,
        ]));

        return response()->json($this->presentRelation($relation->fresh()->load(['contactA:id,name,nickname,avatar,avatar_url', 'contactB:id,name,nickname,avatar,avatar_url'])));
    }

    public function destroyRelation(Request $request, string $id)
    {
        $relation = CtrRelation::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
        $relation->delete();

        return response()->json(['id' => $id]);
    }

    private function validateContact(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:160'],
            'nickname' => ['nullable', 'string', 'max:120'],
            'group' => ['nullable', 'string', 'max:32'],
            'role' => ['nullable', 'string', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'avatar' => ['nullable', 'string', 'max:512'],
            'avatar_url' => ['nullable', 'string', 'max:512'],
            'met_at' => ['nullable', 'date'],
            'met_precision' => ['nullable', 'in:year,month,day'],
            'met_context' => ['nullable', 'string', 'max:255'],
            'birthday_on' => ['nullable', 'date'],
            'birthday_precision' => ['nullable', 'in:year,month,day'],
            'last_contact_at' => ['nullable', 'date'],
            'details' => ['nullable', 'array'],
            'details.*.kind' => ['nullable', 'string', 'max:32'],
            'details.*.label' => ['nullable', 'string', 'max:80'],
            'details.*.value' => ['nullable', 'string', 'max:1024'],
            'details.*.sort_order' => ['nullable', 'integer'],
            'details.*.meta' => ['nullable', 'array'],
            'is_pinned' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'is_archived' => ['nullable', 'boolean'],
        ]);
    }

    private function validateContent(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'contact_id' => [$creating ? 'required' : 'sometimes', 'string', 'size:26'],
            'kind' => ['nullable', 'in:fact,meeting,call,message,note,incident,rumor,family,work,service,reminder_done'],
            'occurred_at' => ['nullable', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'body_md' => ['nullable', 'string'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['string', 'size:26'],
            'mentioned_contact_ids' => ['nullable', 'array'],
            'mentioned_contact_ids.*' => ['string', 'size:26'],
            'mentions' => ['nullable', 'array'],
            'mentions.*.contact_id' => ['required_with:mentions', 'string', 'size:26'],
            'mentions.*.role' => ['nullable', 'string', 'max:32'],
            'mentions.*.sort_order' => ['nullable', 'integer'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_expert' => ['nullable', 'boolean'],
            'eventor_event_id' => ['nullable', 'string', 'size:26'],
            'stuffer_register_id' => ['nullable', 'string', 'size:26'],
            'exploiter_event_id' => ['nullable', 'string', 'size:26'],
            'tasker_task_id' => ['nullable', 'string', 'size:26'],
            'meta' => ['nullable', 'array'],
        ]);
    }

    private function validateRelation(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'contact_a_id' => [$creating ? 'required' : 'sometimes', 'string', 'size:26'],
            'contact_b_id' => [$creating ? 'required' : 'sometimes', 'string', 'size:26'],
            'kind' => ['nullable', 'string', 'max:32'],
            'context' => ['nullable', 'string', 'max:255'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function contactPayload(array $data, string $userId, bool $creating = true): array
    {
        $payload = [
            'user_id' => $userId,
            'name' => $data['name'] ?? null,
            'nickname' => $data['nickname'] ?? null,
            'group' => $data['group'] ?? 'friends',
            'role' => $data['role'] ?? null,
            'company' => $data['company'] ?? null,
            'avatar' => $data['avatar'] ?? $data['avatar_url'] ?? null,
            'avatar_url' => $data['avatar_url'] ?? $data['avatar'] ?? null,
            'met_at' => $data['met_at'] ?? null,
            'met_precision' => $data['met_precision'] ?? null,
            'met_context' => $data['met_context'] ?? null,
            'birthday_on' => $data['birthday_on'] ?? null,
            'birthday_precision' => $data['birthday_precision'] ?? null,
            'last_contact_at' => $data['last_contact_at'] ?? null,
            'details' => $data['details'] ?? null,
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_archived' => (bool) ($data['is_archived'] ?? false),
        ];

        if (! $creating) {
            $allowed = array_fill_keys(array_keys($data), true);
            if (array_key_exists('avatar', $data) || array_key_exists('avatar_url', $data)) {
                $allowed['avatar'] = true;
                $allowed['avatar_url'] = true;
            }
            $allowed['user_id'] = true;

            return array_filter($payload, fn ($value, $key) => isset($allowed[$key]), ARRAY_FILTER_USE_BOTH);
        }

        return $payload;
    }

    private function contentPayload(array $data, string $userId): array
    {
        $occurredAt = Carbon::parse($data['occurred_at'] ?? now());

        return [
            'user_id' => $userId,
            'contact_id' => $data['contact_id'],
            'kind' => $data['kind'] ?? 'note',
            'occurred_at' => $occurredAt,
            'title' => $data['title'] ?? null,
            'body_md' => $data['body_md'] ?? $data['content'] ?? null,
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'is_expert' => (bool) ($data['is_expert'] ?? false),
            'eventor_event_id' => $data['eventor_event_id'] ?? null,
            'stuffer_register_id' => $data['stuffer_register_id'] ?? null,
            'exploiter_event_id' => $data['exploiter_event_id'] ?? null,
            'tasker_task_id' => $data['tasker_task_id'] ?? null,
            'meta' => $data['meta'] ?? null,
            'sort_order' => $occurredAt->timestamp,
        ];
    }

    private function syncDetails(CtrContact $contact, array $details): void
    {
        CtrDetail::query()
            ->where('user_id', $contact->user_id)
            ->where('contact_id', $contact->id)
            ->delete();

        foreach (array_values($details) as $index => $detail) {
            if (! is_array($detail)) {
                continue;
            }

            $value = trim((string) ($detail['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            CtrDetail::create([
                'user_id' => $contact->user_id,
                'contact_id' => $contact->id,
                'kind' => $detail['kind'] ?? 'custom',
                'label' => $detail['label'] ?? null,
                'value' => $value,
                'sort_order' => (int) ($detail['sort_order'] ?? ($index + 1)),
                'meta' => $detail['meta'] ?? null,
            ]);
        }
    }

    private function syncContentTags(CtrContent $content, array $tagIds): void
    {
        $content->tags()->sync(array_values(array_unique($tagIds)));
    }

    private function syncContentMentions(CtrContent $content, array $data): void
    {
        $mentions = [];

        foreach (($data['mentioned_contact_ids'] ?? []) as $index => $contactId) {
            $mentions[] = [
                'contact_id' => $contactId,
                'role' => 'mentioned',
                'sort_order' => $index,
            ];
        }

        foreach (($data['mentions'] ?? []) as $index => $mention) {
            if (! is_array($mention) || empty($mention['contact_id'])) {
                continue;
            }

            $mentions[] = [
                'contact_id' => $mention['contact_id'],
                'role' => $mention['role'] ?? 'mentioned',
                'sort_order' => (int) ($mention['sort_order'] ?? $index),
            ];
        }

        $seen = [];
        $mentions = array_values(array_filter($mentions, function ($mention) use (&$seen) {
            $key = $mention['contact_id'].':'.($mention['role'] ?? '');
            if (isset($seen[$key])) {
                return false;
            }

            $seen[$key] = true;
            return true;
        }));

        DB::table('ctr_content_mentions')
            ->where('user_id', $content->user_id)
            ->where('content_id', $content->id)
            ->delete();

        foreach ($mentions as $mention) {
            DB::table('ctr_content_mentions')->insert([
                'id' => (string) \Symfony\Component\Uid\Ulid::generate(),
                'user_id' => $content->user_id,
                'content_id' => $content->id,
                'contact_id' => $mention['contact_id'],
                'role' => $mention['role'],
                'sort_order' => $mention['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function contactForUser(Request $request, string $id): CtrContact
    {
        return CtrContact::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }

    private function normalizeRelationPair(string $contactA, string $contactB): array
    {
        return strcmp($contactA, $contactB) <= 0
            ? [$contactA, $contactB]
            : [$contactB, $contactA];
    }

    private function assertRelationContacts(Request $request, string $contactA, string $contactB): void
    {
        if ($contactA === $contactB) {
            abort(422, 'Relation contacts must be different.');
        }

        $count = CtrContact::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('id', [$contactA, $contactB])
            ->count();

        if ($count !== 2) {
            abort(422, 'Relation contacts must belong to current user.');
        }
    }

    private function assertOptionalTask(string $userId, ?string $taskId): void
    {
        if (! $taskId) {
            return;
        }

        if (! TskTask::query()->where('user_id', $userId)->where('id', $taskId)->exists()) {
            abort(422, 'Task must belong to current user.');
        }
    }

    private function assertTags(string $userId, array $tagIds): void
    {
        $tagIds = array_values(array_unique(array_filter($tagIds)));
        if ($tagIds === []) {
            return;
        }

        $count = EvtTag::query()
            ->whereIn('id', $tagIds)
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhere('is_system', true);
            })
            ->count();

        if ($count !== count($tagIds)) {
            abort(422, 'One or more tags are invalid or not accessible.');
        }
    }

    private function assertMentionContacts(string $userId, array $contactIds): void
    {
        $contactIds = array_values(array_unique(array_filter($contactIds)));
        if ($contactIds === []) {
            return;
        }

        $count = CtrContact::query()
            ->where('user_id', $userId)
            ->whereIn('id', $contactIds)
            ->count();

        if ($count !== count($contactIds)) {
            abort(422, 'One or more mentioned contacts are invalid or not accessible.');
        }
    }

    private function mentionContactIds(array $data): array
    {
        $ids = $data['mentioned_contact_ids'] ?? [];

        foreach (($data['mentions'] ?? []) as $mention) {
            if (is_array($mention) && ! empty($mention['contact_id'])) {
                $ids[] = $mention['contact_id'];
            }
        }

        return $ids;
    }

    private function syncLastContact(CtrContact $contact): void
    {
        $last = CtrContent::query()
            ->where('user_id', $contact->user_id)
            ->where('contact_id', $contact->id)
            ->where('is_expert', false)
            ->max('occurred_at');

        $contact->update(['last_contact_at' => $last]);
    }

    private function presentContact(CtrContact $contact): array
    {
        $details = $contact->relationLoaded('detailsRows')
            ? $contact->detailsRows->map(fn (CtrDetail $detail) => $this->presentDetail($detail))->values()->all()
            : ($contact->details ?? []);

        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'nickname' => $contact->nickname,
            'group' => $contact->group,
            'role' => $contact->role,
            'company' => $contact->company,
            'avatar' => $contact->avatar_url ?: $contact->avatar,
            'avatar_url' => $contact->avatar_url ?: $contact->avatar,
            'met_at' => optional($contact->met_at)->format('Y-m-d'),
            'met_precision' => $contact->met_precision,
            'met_context' => $contact->met_context,
            'birthday_on' => optional($contact->birthday_on)->format('Y-m-d'),
            'birthday_precision' => $contact->birthday_precision,
            'last_contact_at' => optional($contact->last_contact_at)->toISOString(),
            'details' => $details,
            'is_pinned' => $contact->is_pinned,
            'sort_order' => $contact->sort_order,
            'is_archived' => $contact->is_archived,
        ];
    }

    private function presentDetail(CtrDetail $detail): array
    {
        return [
            'id' => $detail->id,
            'kind' => $detail->kind,
            'label' => $detail->label,
            'value' => $detail->value,
            'sort_order' => $detail->sort_order,
            'meta' => $detail->meta,
        ];
    }

    private function presentContent(CtrContent $content): array
    {
        return [
            'id' => $content->id,
            'contact_id' => $content->contact_id,
            'contact_name' => $content->contact?->name,
            'contact' => $content->contact ? [
                'id' => $content->contact->id,
                'name' => $content->contact->name,
                'nickname' => $content->contact->nickname,
                'avatar' => $content->contact->avatar_url ?: $content->contact->avatar,
                'avatar_url' => $content->contact->avatar_url ?: $content->contact->avatar,
            ] : null,
            'kind' => $content->kind,
            'occurred_at' => optional($content->occurred_at)->toISOString(),
            'title' => $content->title,
            'content' => $content->body_md,
            'body_md' => $content->body_md,
            'is_pinned' => $content->is_pinned,
            'is_expert' => $content->is_expert,
            'eventor_event_id' => $content->eventor_event_id,
            'stuffer_register_id' => $content->stuffer_register_id,
            'exploiter_event_id' => $content->exploiter_event_id,
            'tasker_task_id' => $content->tasker_task_id,
            'meta' => $content->meta,
            'tags' => $content->relationLoaded('tags')
                ? $content->tags->map(fn (EvtTag $tag) => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                    'color' => $tag->color,
                    'bgcolor' => $tag->bgcolor,
                ])->values()->all()
                : [],
            'mentions' => $content->relationLoaded('mentionLinks')
                ? $content->mentionLinks->map(fn ($mention) => [
                    'id' => $mention->id,
                    'contact_id' => $mention->contact_id,
                    'role' => $mention->role,
                    'sort_order' => $mention->sort_order,
                    'contact' => $mention->contact ? [
                        'id' => $mention->contact->id,
                        'name' => $mention->contact->name,
                        'nickname' => $mention->contact->nickname,
                        'avatar' => $mention->contact->avatar_url ?: $mention->contact->avatar,
                        'avatar_url' => $mention->contact->avatar_url ?: $mention->contact->avatar,
                    ] : null,
                ])->values()->all()
                : [],
        ];
    }

    private function presentRelation(CtrRelation $relation): array
    {
        return [
            'id' => $relation->id,
            'contact_a_id' => $relation->contact_a_id,
            'contact_b_id' => $relation->contact_b_id,
            'contact_a_name' => $relation->contactA?->name,
            'contact_b_name' => $relation->contactB?->name,
            'contact_a' => $relation->contactA ? $this->presentRelationContact($relation->contactA) : null,
            'contact_b' => $relation->contactB ? $this->presentRelationContact($relation->contactB) : null,
            'kind' => $relation->kind,
            'context' => $relation->context,
            'valid_from' => optional($relation->valid_from)->format('Y-m-d'),
            'valid_to' => optional($relation->valid_to)->format('Y-m-d'),
            'note' => $relation->note,
        ];
    }

    private function presentRelationContact(CtrContact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'nickname' => $contact->nickname,
            'avatar' => $contact->avatar_url ?: $contact->avatar,
            'avatar_url' => $contact->avatar_url ?: $contact->avatar,
        ];
    }
}





