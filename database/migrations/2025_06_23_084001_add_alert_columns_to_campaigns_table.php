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
            // Alert management columns
            $table->boolean('needs_urgent_review')->default(false);
            $table->text('urgent_review_reason')->nullable();
            $table->timestamp('urgent_review_at')->nullable();

            // Alert acknowledgment tracking
            $table->timestamp('alerts_acknowledged_at')->nullable();
            $table->unsignedBigInteger('alerts_acknowledged_by')->nullable();

            // Auto-stop tracking
            $table->string('stopped_reason')->nullable();
            $table->timestamp('stopped_at')->nullable();

            // Performance metrics cache (JSON)
            $table->json('performance_metrics_cache')->nullable();
            $table->timestamp('metrics_updated_at')->nullable();

            // Index for performance
            $table->index(['status', 'needs_urgent_review']);
            $table->index(['user_id', 'status', 'updated_at']);

            // Foreign key for alerts_acknowledged_by
            $table->foreign('alerts_acknowledged_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['alerts_acknowledged_by']);
            $table->dropIndex(['status', 'needs_urgent_review']);
            $table->dropIndex(['user_id', 'status', 'updated_at']);

            $table->dropColumn([
                'needs_urgent_review',
                'urgent_review_reason',
                'urgent_review_at',
                'alerts_acknowledged_at',
                'alerts_acknowledged_by',
                'stopped_reason',
                'stopped_at',
                'performance_metrics_cache',
                'metrics_updated_at'
            ]);
        });
    }
};
