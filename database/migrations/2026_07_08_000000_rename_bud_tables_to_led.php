<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'bud_layers' => 'led_layers',
        'bud_accounts' => 'led_accounts',
        'bud_transaction_groups' => 'led_transaction_groups',
        'bud_transactions' => 'led_transactions',
        'bud_month_totals' => 'led_month_totals',
        'bud_transaction_tags' => 'led_transaction_tags',
        'bud_categories' => 'led_categories',
    ];

    public function up(): void
    {
        foreach ($this->tables as $from => $to) {
            if (Schema::hasTable($from) && ! Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $from => $to) {
            if (Schema::hasTable($to) && ! Schema::hasTable($from)) {
                Schema::rename($to, $from);
            }
        }
    }
};
