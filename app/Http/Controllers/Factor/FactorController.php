<?php

namespace App\Http\Controllers\Factor;

use App\Http\Controllers\Controller;
use App\Models\FctFact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FactorController extends Controller
{
    private const FORMATS = ['text', 'markdown', 'code', 'command', 'number', 'svg'];
    private const KINDS = ['document', 'technical', 'professional', 'personal', 'other'];
    private const DISPLAY_MODES = ['plain', 'large', 'mono', 'article', 'snippet', 'terminal', 'metric', 'preview', 'qr', 'masked'];

    public function index(Request $request)
    {
        $query = FctFact::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_pinned')
            ->orderBy('sort_order')
            ->orderBy('label');

        if (! $request->boolean('include_expert')) {
            $query->where('is_expert', false);
        }

        if ($request->filled('kind') && $request->get('kind') !== 'all') {
            $query->where('kind', $request->get('kind'));
        }

        if ($request->filled('format') && $request->get('format') !== 'all') {
            $query->where('format', $request->get('format'));
        }

        if ($request->boolean('pinned')) {
            $query->where('is_pinned', true);
        }

        if ($request->filled('sensitive')) {
            $query->where('is_sensitive', $request->boolean('sensitive'));
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->get('q'));
            $query->where(function ($inner) use ($q) {
                $inner->where('label', 'like', "%{$q}%")
                    ->orWhere('value', 'like', "%{$q}%")
                    ->orWhere('context', 'like', "%{$q}%")
                    ->orWhere('search_keywords', 'like', "%{$q}%");
            });
        }

        $limit = min(max((int) $request->get('limit', 200), 1), 500);

        return response()->json(
            $query->limit($limit)->get()->map(fn (FctFact $fact) => $this->present($fact))->values()
        );
    }

    public function show(Request $request, string $id)
    {
        return response()->json($this->present($this->factForUser($request, $id)));
    }

    public function store(Request $request)
    {
        $data = $this->validateFact($request);
        $fact = FctFact::create($this->payload($data, $request->user()->id));

        return response()->json($this->present($fact), 201);
    }

    public function update(Request $request, string $id)
    {
        $fact = $this->factForUser($request, $id);
        $data = $this->validateFact($request, false);
        $fact->update($this->payload(array_merge($fact->toArray(), $data), $request->user()->id, false, array_keys($data)));

        return response()->json($this->present($fact->fresh()));
    }

    public function destroy(Request $request, string $id)
    {
        $fact = $this->factForUser($request, $id);
        $fact->delete();

        return response()->json(['id' => $id]);
    }

    public function togglePin(Request $request, string $id)
    {
        $fact = $this->factForUser($request, $id);
        $fact->update(['is_pinned' => ! $fact->is_pinned]);

        return response()->json($this->present($fact->fresh()));
    }

    private function validateFact(Request $request, bool $creating = true): array
    {
        return $request->validate([
            'label' => [$creating ? 'required' : 'sometimes', 'string', 'max:160'],
            'value' => [$creating ? 'required' : 'sometimes', 'string'],
            'format' => ['nullable', Rule::in(self::FORMATS)],
            'language' => ['nullable', 'string', 'max:40'],
            'unit' => ['nullable', 'string', 'max:32'],
            'context' => ['nullable', 'string'],
            'search_keywords' => ['nullable', 'array'],
            'search_keywords.*' => ['nullable', 'string', 'max:160'],
            'kind' => ['nullable', Rule::in(self::KINDS)],
            'display_mode' => ['nullable', Rule::in(self::DISPLAY_MODES)],
            'is_sensitive' => ['nullable', 'boolean'],
            'is_expert' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date'],
            'is_pinned' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);
    }

    private function payload(array $data, string $userId, bool $creating = true, array $only = []): array
    {
        $payload = [
            'user_id' => $userId,
            'label' => $data['label'] ?? null,
            'value' => $data['value'] ?? null,
            'format' => $data['format'] ?? 'text',
            'language' => $data['language'] ?? null,
            'unit' => $data['unit'] ?? null,
            'context' => $data['context'] ?? null,
            'search_keywords' => array_values(array_filter($data['search_keywords'] ?? [])),
            'kind' => $data['kind'] ?? 'other',
            'display_mode' => $data['display_mode'] ?? 'plain',
            'is_sensitive' => (bool) ($data['is_sensitive'] ?? false),
            'is_expert' => (bool) ($data['is_expert'] ?? false),
            'valid_from' => $data['valid_from'] ?? null,
            'valid_to' => $data['valid_to'] ?? null,
            'is_pinned' => (bool) ($data['is_pinned'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];

        if (! $creating) {
            $allowed = array_fill_keys($only, true);
            $allowed['user_id'] = true;

            return array_filter($payload, fn ($value, $key) => isset($allowed[$key]), ARRAY_FILTER_USE_BOTH);
        }

        return $payload;
    }

    private function factForUser(Request $request, string $id): FctFact
    {
        return FctFact::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }

    private function present(FctFact $fact): array
    {
        return [
            'id' => $fact->id,
            'label' => $fact->label,
            'value' => $fact->value,
            'format' => $fact->format,
            'language' => $fact->language,
            'unit' => $fact->unit,
            'context' => $fact->context,
            'search_keywords' => $fact->search_keywords ?? [],
            'kind' => $fact->kind,
            'display_mode' => $fact->display_mode,
            'is_sensitive' => $fact->is_sensitive,
            'is_expert' => $fact->is_expert,
            'valid_from' => optional($fact->valid_from)->format('Y-m-d'),
            'valid_to' => optional($fact->valid_to)->format('Y-m-d'),
            'is_pinned' => $fact->is_pinned,
            'sort_order' => $fact->sort_order,
            'created_at' => optional($fact->created_at)->toISOString(),
            'updated_at' => optional($fact->updated_at)->toISOString(),
        ];
    }
}