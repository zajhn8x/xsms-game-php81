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
        Schema::create('risk_management_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rule_name');
            $table->string('rule_type'); // daily_loss_limit, consecutive_loss_limit, win_streak_protection, etc.
            $table->json('conditions'); // Điều kiện trigger
            $table->json('actions'); // Hành động khi trigger (pause, reduce_bet, notify)
            $table->boolean('is_active')->default(true);
            $table->boolean('is_global')->default(false); // Áp dụng cho tất cả campaigns
            $table->decimal('threshold_amount', 15, 2)->nullable();
            $table->integer('threshold_count')->nullable();
            $table->integer('time_window_hours')->nullable(); // Khung thời gian áp dụng
            $table->integer('trigger_count')->default(0);
            $table->timestamp('last_triggered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['rule_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_management_rules');
    }
};
