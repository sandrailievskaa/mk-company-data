<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Existing users keep full access.
            $table->string('role')->default('admin')->after('email');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->index()->after('status');
            // Set once the scheduler has run the distribution, so it never repeats.
            $table->timestamp('schedule_dispatched_at')->nullable()->after('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex(['scheduled_at']);
            $table->dropColumn(['scheduled_at', 'schedule_dispatched_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
