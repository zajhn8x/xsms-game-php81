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
        Schema::table('campaigns', function (Blueprint $table) {
            // Campaign configuration
            $table->string('campaign_type')->default('live'); // live, historical
            $table->decimal('target_profit', 15, 2)->nullable();
            $table->decimal('daily_bet_limit', 15, 2)->nullable();
            $table->decimal('max_loss_per_day', 15, 2)->nullable();
            $table->decimal('total_loss_limit', 15, 2)->nullable();

            // Auto stop/take profit
            $table->boolean('auto_stop_loss')->default(false);
            $table->boolean('auto_take_profit')->default(false);
            $table->decimal('stop_loss_amount', 15, 2)->nullable();
            $table->decimal('take_profit_amount', 15, 2)->nullable();

            // Strategy configuration
            $table->string('betting_strategy')->default('manual');
            $table->json('strategy_config')->nullable();

            // Sharing and visibility
            $table->boolean('is_public')->default(false);
            $table->text('notes')->nullable();

            // Performance tracking
            $table->decimal('total_bet_amount', 15, 2)->default(0);
            $table->decimal('total_win_amount', 15, 2)->default(0);
            $table->integer('total_bet_count')->default(0);
            $table->integer('win_bet_count')->default(0);
            $table->decimal('win_rate', 5, 2)->default(0);
            $table->timestamp('last_bet_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'campaign_type', 'target_profit', 'daily_bet_limit',
                'max_loss_per_day', 'total_loss_limit',
                'auto_stop_loss', 'auto_take_profit', 'stop_loss_amount', 'take_profit_amount',
                'betting_strategy', 'strategy_config',
                'is_public', 'notes',
                'total_bet_amount', 'total_win_amount', 'total_bet_count', 'win_bet_count', 'win_rate',
                'last_bet_at'
            ]);
        });
    }
};
