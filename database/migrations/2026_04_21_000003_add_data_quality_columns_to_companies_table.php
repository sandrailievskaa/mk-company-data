<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('data_quality_flag')->default('ok')->after('scrape_count');
            $table->text('data_quality_note')->nullable()->after('data_quality_flag');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['data_quality_flag', 'data_quality_note']);
        });
    }
};

