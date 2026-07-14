<?php

namespace App\Http\Controllers\Contactor;

use App\Http\Controllers\Controller;
use App\Models\CtrContact;
use App\Models\CtrContent;
use App\Models\CtrRelation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactorController extends Controller
{
    public function contacts(Request $request)
    {
        $query = CtrContact::query()
            ->where('user_id', $request->user()->id)
            ->active()
            ->orderByDesc('last_contact_at')
            ->orderBy('name');

        if ($request->filled('group') && $request->get('group') !== 'all') {
            $query->where('group', $request->get('group'));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->get('q'));
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('nickname', 'like', "%{$q}%")
                    ->orWhere('role', 'like', "%{$q}%")
                    ->orWhere('company', 'like', "%{$q}%")
                    ->orWhere('met_context', 'like', "%{$q}%");
            });
        }

        return response()->json(
            $query->get()->map(fn (CtrContact $contact) => $this->presentContact($contact))->values()
        );
    }

    public function showContact(Request $request, string $id)
    {
        $contact = $this->contactForUser($request, $id);

        return response()->json($this->presentContact($contact));
    }

    public function storeContact(Request $request)
    {
        $data = $this->validateContact($request);
        $data['user_id'] = $request->user()->id;

        $contact = CtrContact::create($data);

        return response()->json($this->presentContact($contact), 201);
    }

    public function updateContact(Request $request, string $id)
    {
        $contact = $this->contactForUser($request, $id);
        $contact->update($this->validateContact($request, false));

        return response()->json($this->presentContact($contact->fresh()));
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
            ->with('contact:id,name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at');

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->get('contact_id'));
        }

        if ($request->filled('kind')) {
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

        $content = DB::transaction(function () use ($data, $userId, $contact) {
            $content = CtrContent::create($this->contentPayload($data, $userId));
            $this->syncLastContact($contact);

            return $content;
        });

        return response()->json($this->presentContent($content->load('contact:id,name')), 201);
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

        DB::transaction(function () use ($content, $data, $request) {
            $oldContactId = $content->contact_id;
            $content->update($this->contentPayload($data + $content->toArray(), $request->user()->id));

            $this->syncLastContact($this->contactForUser($request, $content->contact_id));
            if ($oldContactId !== $content->contact_id) {
                $this->syncLastContact($this->contactForUser($request, $oldContactId));
            }
        });

        return response()->json($this->presentContent($content->fresh()->load('contact:id,name')));
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
            ->with(['contactA:id,name', 'contactB:id,name'])
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
        $this->assertRelationContacts($request, $data['contact_a_id'], $data['contact_b_id']);

        $relation = CtrRelation::create($data + ['user_id' => $userId]);

        return response()->json($this->presentRelation($relation->load(['contactA:id,name', 'contactB:id,name'])), 201);
    }

    public function updateRelation(Request $request, string $id)
    {
        $relation = CtrRelation::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        $data = $this->validateRelation($request, false);
        $contactA = $data['contact_a_id'] ?? $relation->contact_a_id;
        $contactB = $data['contact_b_id'] ?? $relation->contact_b_id;
        $this->assertRelationContacts($request, $contactA, $contactB);

        $relation->update($data);

        return response()->json($this->presentRelation($relation->fresh()->load(['contactA:id,name', 'contactB:id,name'])));
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
            'met_at' => ['nullable', 'date'],
            'met_context' => ['nullable', 'string', 'max:255'],
            'last_contact_at' => ['nullable', 'date'],
            'details' => ['nullable', 'array'],
            'is_archived' => ['nullable', 'boolean'],
        ]);
    }

    private function validateContent(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'contact_id' => [$creating ? 'required' : 'sometimes', 'string', 'size:26'],
            'kind' => ['nullable', 'in:fact,meeting,call,note,reminder_done'],
            'occurred_at' => ['nullable', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'body_md' => ['nullable', 'string'],
            'is_pinned' => ['nullable', 'boolean'],
            'eventor_event_id' => ['nullable', 'string', 'size:26'],
            'stuffer_register_id' => ['nullable', 'string', 'size:26'],
            'exploiter_event_id' => ['nullable', 'string', 'size:26'],
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
            'eventor_event_id' => $data['eventor_event_id'] ?? null,
            'stuffer_register_id' => $data['stuffer_register_id'] ?? null,
            'exploiter_event_id' => $data['exploiter_event_id'] ?? null,
            'meta' => $data['meta'] ?? null,
            'sort_order' => $occurredAt->timestamp,
        ];
    }

    private function contactForUser(Request $request, string $id): CtrContact
    {
        return CtrContact::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
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

    private function syncLastContact(CtrContact $contact): void
    {
        $last = CtrContent::query()
            ->where('user_id', $contact->user_id)
            ->where('contact_id', $contact->id)
            ->max('occurred_at');

        $contact->update(['last_contact_at' => $last]);
    }

    private function presentContact(CtrContact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'nickname' => $contact->nickname,
            'group' => $contact->group,
            'role' => $contact->role,
            'company' => $contact->company,
            'avatar' => $contact->avatar,
            'met_at' => optional($contact->met_at)->format('Y-m-d'),
            'met_context' => $contact->met_context,
            'last_contact_at' => optional($contact->last_contact_at)->toISOString(),
            'details' => $contact->details ?? [],
            'is_archived' => $contact->is_archived,
        ];
    }

    private function presentContent(CtrContent $content): array
    {
        return [
            'id' => $content->id,
            'contact_id' => $content->contact_id,
            'contact_name' => $content->contact?->name,
            'kind' => $content->kind,
            'occurred_at' => optional($content->occurred_at)->toISOString(),
            'title' => $content->title,
            'content' => $content->body_md,
            'body_md' => $content->body_md,
            'is_pinned' => $content->is_pinned,
            'eventor_event_id' => $content->eventor_event_id,
            'stuffer_register_id' => $content->stuffer_register_id,
            'exploiter_event_id' => $content->exploiter_event_id,
            'meta' => $content->meta,
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
            'kind' => $relation->kind,
            'context' => $relation->context,
            'valid_from' => optional($relation->valid_from)->format('Y-m-d'),
            'valid_to' => optional($relation->valid_to)->format('Y-m-d'),
            'note' => $relation->note,
        ];
    }
}