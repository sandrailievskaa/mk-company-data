<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sent_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->onDelete('cascade')->comment('ID на понудата');
            $table->foreignId('company_id')->constrained()->onDelete('cascade')->comment('ID на компанијата');
            $table->string('status')->default('pending')->comment('Статус: pending, sent, failed, opened');
            $table->timestamp('sent_at')->nullable()->comment('Кога е испратена');
            $table->timestamp('opened_at')->nullable()->comment('Кога е отворена (ако се следи)');
            $table->text('error_message')->nullable()->comment('Грешка ако не успеа');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sent_offers');
    }
};
