<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bkr_books', function (Blueprint $table) {
            if (! Schema::hasColumn('bkr_books', 'cover_svg_url')) {
                $table->text('cover_svg_url')->nullable()->after('cover_color');
            }
            if (! Schema::hasColumn('bkr_books', 'cover_svg_text')) {
                $table->longText('cover_svg_text')->nullable()->after('cover_svg_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bkr_books', function (Blueprint $table) {
            if (Schema::hasColumn('bkr_books', 'cover_svg_text')) {
                $table->dropColumn('cover_svg_text');
            }
            if (Schema::hasColumn('bkr_books', 'cover_svg_url')) {
                $table->dropColumn('cover_svg_url');
            }
        });
    }
};
