<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scrape_runs', function (Blueprint $table) {
            // Listings that repeated a company already seen earlier in the same run
            // and were merged into its record instead of creating a duplicate.
            $table->unsignedInteger('duplicates_skipped')->default(0)->after('records_updated');
        });
    }

    public function down(): void
    {
        Schema::table('scrape_runs', function (Blueprint $table) {
            $table->dropColumn('duplicates_skipped');
        });
    }
};
