<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Micro-task 2.1.2.1: Tạo campaign_templates table (3h)
     */
    public function up(): void
    {
        Schema::create('campaign_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['system', 'user', 'shared'])->default('user');
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->boolean('is_public')->default(false);
            $table->json('template_data');
            $table->integer('usage_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->timestamps();

            $table->index(['category', 'is_public']);
            $table->index(['user_id', 'category']);
            $table->index('usage_count');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_templates');
    }
};
