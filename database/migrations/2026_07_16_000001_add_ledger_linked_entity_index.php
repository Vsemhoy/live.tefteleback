<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('led_transactions')
            ->where('linked_entity_type', 'exploiter')
            ->update(['linked_entity_type' => 'exploiter.event']);

        if (! $this->indexExists('led_transactions', 'led_transactions_linked_entity_idx')) {
            DB::statement('CREATE INDEX led_transactions_linked_entity_idx ON led_transactions (linked_entity_type, linked_entity_id, occurred_at)');
        }
    }

    public function down(): void
    {
        if ($this->indexExists('led_transactions', 'led_transactions_linked_entity_idx')) {
            DB::statement('DROP INDEX led_transactions_linked_entity_idx ON led_transactions');
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            'SELECT COUNT(1) AS count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index]
        );

        return (int) ($result->count ?? 0) > 0;
    }
};