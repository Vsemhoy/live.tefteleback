<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FeedController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'filter' => ['nullable', Rule::in(['all', 'eventor', 'exploiter', 'ledger'])],
            'before' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $userId = $request->user()->id;
        $filter = $validated['filter'] ?? 'all';
        $limit = min((int) ($validated['limit'] ?? 30), 100);
        $before = $validated['before'] ?? null;

        $queries = [];

        if ($filter === 'all' || $filter === 'eventor') {
            $queries[] = $this->eventorQuery($userId, $before);
        }

        if ($filter === 'all' || $filter === 'exploiter') {
            $queries[] = $this->exploiterQuery($userId, $before);
        }

        if ($filter === 'all' || $filter === 'ledger') {
            $queries[] = $this->ledgerQuery($userId, $before);
        }

        $union = array_shift($queries);

        foreach ($queries as $query) {
            $union->unionAll($query);
        }

        $items = DB::query()
            ->fromSub($union, 'feed')
            ->orderByDesc('occurred_at')
            ->limit($limit + 1)
            ->get();

        $hasMore = $items->count() > $limit;
        $data = $items->take($limit)->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'has_more' => $hasMore,
                'next_before' => $hasMore ? $data->last()?->occurred_at : null,
            ],
        ]);
    }

    private function eventorQuery(string $userId, ?string $before): Builder
    {
        $query = DB::table('evt_events as e')
            ->leftJoin('evt_sections as s', 's.id', '=', 'e.section_id')
            ->leftJoin('cnt_contents as c', function ($join) {
                $join->on('c.source_id', '=', 'e.id')
                    ->where('c.source_module', '=', 'eventor')
                    ->where('c.is_primary', '=', 1)
                    ->whereNull('c.deleted_at');
            })
            ->where('e.user_id', $userId)
            ->where('e.occurred_at', '<=', now())
            ->selectRaw("
                e.id,
                'eventor' as source,
                e.name,
                e.occurred_at,
                LEFT(COALESCE(c.body_md, e.content), 120) as snippet,
                s.name as section_name,
                NULL as amount,
                NULL as part_cost,
                NULL as labor_cost,
                NULL as time_total_min,
                NULL as thing_name,
                NULL as account_name,
                NULL as category_name,
                NULL as status,
                NULL as priority,
                0 as is_overdue,
                0 as ledger_linked,
                0 as eventor_linked
            ");

        if ($before) {
            $query->where('e.occurred_at', '<', $before);
        }

        return $query;
    }

    private function exploiterQuery(string $userId, ?string $before): Builder
    {
        $query = DB::table('stf_register as r')
            ->leftJoin('stf_things as t', 't.id', '=', 'r.thing_id')
            ->where('r.user_id', $userId)
            ->where('r.occurred_at', '<=', now())
            ->whereNotNull('r.status')
            ->selectRaw("
                r.id,
                'exploiter' as source,
                COALESCE(NULLIF(r.note, ''), t.name, 'Exploiter') as name,
                r.occurred_at,
                NULL as snippet,
                NULL as section_name,
                NULL as amount,
                r.part_cost,
                r.labor_cost,
                (r.time_self_min + r.time_service_min) as time_total_min,
                t.name as thing_name,
                NULL as account_name,
                NULL as category_name,
                r.status,
                r.priority,
                CASE WHEN r.status = 20 AND r.occurred_at < CURDATE() THEN 1 ELSE 0 END as is_overdue,
                CASE WHEN EXISTS (
                    SELECT 1 FROM led_transactions lt
                    WHERE lt.exploiter_event_id = r.id
                      AND lt.deleted_at IS NULL
                ) THEN 1 ELSE 0 END as ledger_linked,
                CASE WHEN EXISTS (
                    SELECT 1 FROM evt_events ev
                    WHERE ev.exploiter_event_id = r.id
                ) THEN 1 ELSE 0 END as eventor_linked
            ");

        if ($before) {
            $query->where('r.occurred_at', '<', $before);
        }

        return $query;
    }

    private function ledgerQuery(string $userId, ?string $before): Builder
    {
        $query = DB::table('led_transactions as tx')
            ->leftJoin('led_accounts as a', 'a.id', '=', 'tx.account_id')
            ->leftJoin('led_categories as cat', 'cat.id', '=', 'tx.category_id')
            ->where('tx.user_id', $userId)
            ->where('tx.occurred_at', '<=', now())
            ->where('tx.is_disabled', 0)
            ->whereNull('tx.deleted_at')
            ->selectRaw("
                tx.id,
                'ledger' as source,
                COALESCE(NULLIF(tx.title, ''), tx.flow_kind) as name,
                tx.occurred_at,
                tx.note as snippet,
                NULL as section_name,
                tx.amount,
                NULL as part_cost,
                NULL as labor_cost,
                NULL as time_total_min,
                NULL as thing_name,
                a.name as account_name,
                cat.name as category_name,
                NULL as status,
                NULL as priority,
                0 as is_overdue,
                0 as ledger_linked,
                0 as eventor_linked
            ");

        if ($before) {
            $query->where('tx.occurred_at', '<', $before);
        }

        return $query;
    }
}
