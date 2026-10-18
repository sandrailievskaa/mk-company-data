<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('email_source')->nullable()->index()->after('email');
            $table->timestamp('email_found_at')->nullable()->after('email_source');
            $table->string('normalized_name')->nullable()->index()->after('name');
            $table->unsignedSmallInteger('sources_count')->default(1)->after('scrape_count');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['email_source']);
            $table->dropIndex(['normalized_name']);
            $table->dropColumn(['email_source', 'email_found_at', 'normalized_name', 'sources_count']);
        });
    }
};
