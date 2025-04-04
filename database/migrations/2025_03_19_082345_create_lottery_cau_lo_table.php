<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lottery_formula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formula_meta_id')->constrained('lottery_formula_meta')->onDelete('cascade');
            $table->enum('combination_type', ['single', 'pair', 'multi', 'dynamic']);
            $table->boolean('is_verified')->default(false);
            $table->date('last_date_verified')->nullable();
            $table->integer('hit_count')->default(0);
            $table->integer('miss_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_processed')->default(false);
            $table->integer('processed_days')->default(0);
            $table->date('last_processed_date')->nullable();
            $table->enum('processing_status', ['pending', 'in_progress', 'completed'])
                ->default('pending');
            $table->timestamps();

            // Accuracy rate computed column (chỉ hỗ trợ MySQL 5.7+ hoặc MariaDB 10.2+)
            $table->decimal('accuracy_rate', 5, 2)->storedAs('CASE WHEN (hit_count + miss_count) > 0 THEN (hit_count * 100.0) / (hit_count + miss_count) ELSE 0 END');
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('lottery_formula');
    }
};
