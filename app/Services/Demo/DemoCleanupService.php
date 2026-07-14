<?php

namespace App\Services\Demo;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoCleanupService
{
    public function cleanup(?int $ttlMinutes = null): array
    {
        $ttlMinutes = $ttlMinutes ?? (int) env('DEMO_TTL_MINUTES', 120);
        $cutoff = now()->subMinutes(max(1, $ttlMinutes));
        $summary = [];

        User::query()
            ->where('is_demo', true)
            ->whereNotNull('demo_seeded_at')
            ->each(function (User $user) use ($cutoff, &$summary) {
                $summary[$user->id] = $this->cleanupUser($user, $cutoff);
            });

        return $summary;
    }

    private function cleanupUser(User $user, Carbon $cutoff): array
    {
        $baseline = $user->demo_seeded_at;
        $userId = $user->id;
        $deleted = [];

        DB::transaction(function () use ($userId, $baseline, $cutoff, &$deleted) {
            $eventIds = $this->ids('evt_events', $userId, $baseline, $cutoff);
            $registerIds = $this->ids('stf_register', $userId, $baseline, $cutoff);
            $thingIds = $this->ids('stf_things', $userId, $baseline, $cutoff);
            $locationIds = $this->ids('stf_locations', $userId, $baseline, $cutoff);
            $txIds = $this->ids('led_transactions', $userId, $baseline, $cutoff);
            $accountIds = $this->ids('led_accounts', $userId, $baseline, $cutoff);
            $categoryIds = $this->ids('led_categories', $userId, $baseline, $cutoff);
            $groupIds = $this->ids('led_transaction_groups', $userId, $baseline, $cutoff);

            $deleted['evt_event_tags'] = $this->deleteWhereIn('evt_event_tags', 'event_id', $eventIds);
            $deleted['evt_starred'] = $this->deleteWhereIn('evt_starred', 'event_id', $eventIds);
            $deleted['evt_media'] = $this->deleteWhereIn('evt_media', 'event_id', $eventIds);
            $deleted['evt_embeds'] = $this->deleteWhereIn('evt_embeds', 'event_id', $eventIds);
            $deleted['led_transaction_tags'] = $this->deleteWhereIn('led_transaction_tags', 'transaction_id', $txIds);

            $deleted['sys_timer_entries'] = $this->deleteByUserWindow('sys_timer_entries', $userId, $baseline, $cutoff);
            $deleted['cnt_contents'] = $this->deleteByUserWindow('cnt_contents', $userId, $baseline, $cutoff);
            $deleted['sys_templates'] = $this->deleteByUserWindow('sys_templates', $userId, $baseline, $cutoff);
            $deleted['ctr_contents'] = $this->deleteByUserWindow('ctr_contents', $userId, $baseline, $cutoff);
            $deleted['ctr_relations'] = $this->deleteByUserWindow('ctr_relations', $userId, $baseline, $cutoff);
            $deleted['ctr_contacts'] = $this->deleteByUserWindow('ctr_contacts', $userId, $baseline, $cutoff);

            $deleted['stf_expenses'] = $this->deleteByUserWindow('stf_expenses', $userId, $baseline, $cutoff);
            $deleted['stf_register'] = $this->deleteByIds('stf_register', $registerIds);

            $deleted['led_transactions'] = $this->deleteByIds('led_transactions', $txIds);
            $deleted['led_month_totals'] = $this->deleteByUserWindow('led_month_totals', $userId, $baseline, $cutoff);
            $deleted['led_accounts'] = $this->deleteByIds('led_accounts', $accountIds);
            $deleted['led_categories'] = $this->deleteByIds('led_categories', $categoryIds);
            $deleted['led_transaction_groups'] = $this->deleteByIds('led_transaction_groups', $groupIds);

            $deleted['evt_events'] = $this->deleteByIds('evt_events', $eventIds);
            $deleted['evt_tags'] = $this->deleteByUserWindow('evt_tags', $userId, $baseline, $cutoff);
            $deleted['evt_categories'] = $this->deleteByUserWindow('evt_categories', $userId, $baseline, $cutoff);
            $deleted['evt_sections'] = $this->deleteByUserWindow('evt_sections', $userId, $baseline, $cutoff);
            $deleted['evt_types'] = $this->deleteByUserWindow('evt_types', $userId, $baseline, $cutoff);

            $deleted['stf_things'] = $this->deleteByIds('stf_things', $thingIds);
            $deleted['stf_locations'] = $this->deleteByIds('stf_locations', $locationIds);
            $deleted['led_layers'] = $this->deleteByUserWindow('led_layers', $userId, $baseline, $cutoff);
        });

        return $deleted;
    }

    private function ids(string $table, string $userId, Carbon $baseline, Carbon $cutoff): array
    {
        if (! $this->hasUserWindow($table)) {
            return [];
        }

        return DB::table($table)
            ->where('user_id', $userId)
            ->where('created_at', '>', $baseline)
            ->where('created_at', '<=', $cutoff)
            ->pluck('id')
            ->all();
    }

    private function deleteByUserWindow(string $table, string $userId, Carbon $baseline, Carbon $cutoff): int
    {
        if (! $this->hasUserWindow($table)) {
            return 0;
        }

        return DB::table($table)
            ->where('user_id', $userId)
            ->where('created_at', '>', $baseline)
            ->where('created_at', '<=', $cutoff)
            ->delete();
    }

    private function deleteWhereIn(string $table, string $column, array $ids): int
    {
        if ($ids === [] || ! $this->hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return DB::table($table)->whereIn($column, $ids)->delete();
    }

    private function deleteByIds(string $table, array $ids): int
    {
        if ($ids === [] || ! $this->hasTable($table) || ! Schema::hasColumn($table, 'id')) {
            return 0;
        }

        return DB::table($table)->whereIn('id', $ids)->delete();
    }

    private function hasUserWindow(string $table): bool
    {
        return $this->hasTable($table)
            && Schema::hasColumn($table, 'user_id')
            && Schema::hasColumn($table, 'created_at');
    }

    private function hasTable(string $table): bool
    {
        return Schema::hasTable($table);
    }
}
