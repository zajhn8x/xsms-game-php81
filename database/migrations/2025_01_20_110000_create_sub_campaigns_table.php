<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Micro-task 2.1.4.1: Tạo sub_campaigns table (2h)
     */
    public function up(): void
    {
        Schema::create('sub_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['segment', 'test', 'backup', 'split'])->default('segment');

            // Financial settings
            $table->decimal('allocated_balance', 15, 2);
            $table->decimal('current_balance', 15, 2);
            $table->decimal('daily_bet_limit', 15, 2)->nullable();
            $table->decimal('max_loss_per_day', 15, 2)->nullable();
            $table->decimal('stop_loss_amount', 15, 2)->nullable();
            $table->decimal('take_profit_amount', 15, 2)->nullable();

            // Strategy configuration
            $table->string('betting_strategy');
            $table->json('strategy_config')->nullable();
            $table->json('bet_preferences')->nullable();

            // Scheduling
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('days')->nullable();

            // Status and control
            $table->enum('status', ['pending', 'active', 'paused', 'completed', 'cancelled'])->default('pending');
            $table->boolean('auto_start')->default(false);
            $table->boolean('auto_stop')->default(false);
            $table->integer('priority')->default(1); // 1 = highest, 10 = lowest
            $table->decimal('weight', 5, 2)->default(1.0); // For resource allocation

            // Performance tracking
            $table->decimal('total_bet_amount', 15, 2)->default(0);
            $table->decimal('total_win_amount', 15, 2)->default(0);
            $table->decimal('total_loss_amount', 15, 2)->default(0);
            $table->integer('total_bets')->default(0);
            $table->integer('winning_bets')->default(0);
            $table->integer('losing_bets')->default(0);

            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['parent_campaign_id', 'status']);
            $table->index(['parent_campaign_id', 'priority']);
            $table->index(['start_date', 'end_date']);
            $table->index('status');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_campaigns');
    }
};
