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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_id')->unique();
            $table->enum('type', ['deposit', 'withdrawal', 'transfer', 'campaign', 'bonus', 'refund']);
            $table->enum('balance_type', ['real', 'virtual', 'frozen', 'bonus']);
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled']);
            $table->string('gateway')->nullable(); // vnpay, momo, bank_transfer
            $table->string('gateway_transaction_id')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('related_id')->nullable(); // campaign_id, bet_id, etc.
            $table->string('related_type')->nullable(); // campaign, bet, etc.
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'status']);
            $table->index(['transaction_id']);
            $table->index(['gateway_transaction_id']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
