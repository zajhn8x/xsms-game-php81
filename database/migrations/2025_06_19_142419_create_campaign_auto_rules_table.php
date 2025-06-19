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
        Schema::create('campaign_auto_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('rule_type'); // heatmap, streak, pattern, time_based
            $table->json('conditions'); // Điều kiện trigger
            $table->json('actions'); // Hành động thực hiện
            $table->integer('priority')->default(1); // Độ ưu tiên
            $table->boolean('is_active')->default(true);
            $table->integer('execution_count')->default(0);
            $table->timestamp('last_executed_at')->nullable();
            $table->decimal('min_bet_amount', 15, 2)->default(1000);
            $table->decimal('max_bet_amount', 15, 2)->default(100000);
            $table->integer('cooldown_minutes')->default(0); // Thời gian nghỉ giữa các lần execute
            $table->timestamps();

            $table->index(['campaign_id', 'is_active']);
            $table->index(['rule_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_auto_rules');
    }
};
