<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sent_offers')) {
            if (DB::table('sent_offers')->exists()) {
                throw new RuntimeException('sent_offers contains data; refusing to recreate it.');
            }

            Schema::drop('sent_offers');
        }

        Schema::create('sent_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_email')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('token', 64)->unique();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            // Idempotency guarantee: the same offer is never sent twice to a company.
            $table->unique(['offer_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sent_offers');
    }
};
