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
        Schema::create('historical_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historical_campaign_id')->constrained()->cascadeOnDelete();
            $table->date('bet_date');
            $table->string('lo_number', 2);
            $table->decimal('amount', 15, 2);
            $table->decimal('win_amount', 15, 2)->default(0);
            $table->boolean('is_win')->default(false);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['historical_campaign_id', 'bet_date']);
            $table->index(['bet_date', 'lo_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_bets');
    }
};
