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
        Schema::create('historical_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('test_start_date'); // Ngày bắt đầu test
            $table->date('test_end_date');   // Ngày kết thúc test
            $table->date('data_start_date'); // Ngày bắt đầu dữ liệu
            $table->date('data_end_date');   // Ngày kết thúc dữ liệu
            $table->decimal('initial_balance', 15, 2);
            $table->decimal('final_balance', 15, 2)->default(0);
            $table->string('betting_strategy'); // manual, auto_heatmap, auto_streak
            $table->json('strategy_config')->nullable();
            $table->enum('status', ['pending', 'running', 'completed', 'failed']);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['test_start_date', 'test_end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_campaigns');
    }
};
