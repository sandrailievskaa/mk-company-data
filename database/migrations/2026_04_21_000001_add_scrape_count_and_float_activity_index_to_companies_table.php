<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'scrape_count')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->unsignedInteger('scrape_count')->default(1)->after('activity_index');
            });
        }

        // SQLite is flexible with types, so we don't need to alter the column there.
        // For MySQL, alter the column with raw SQL (avoids requiring extra schema deps).
        $driver = DB::getDriverName();
        if ($driver === 'mysql' && Schema::hasColumn('companies', 'activity_index')) {
            DB::statement('ALTER TABLE companies MODIFY activity_index DOUBLE NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql' && Schema::hasColumn('companies', 'activity_index')) {
            DB::statement('ALTER TABLE companies MODIFY activity_index INT NOT NULL DEFAULT 0');
        }

        if (Schema::hasColumn('companies', 'scrape_count')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('scrape_count');
            });
        }
    }
};

