<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Micro-task 2.2.1.1: Enum cho campaign statuses (1h)
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Add status tracking fields if not exists
            if (!Schema::hasColumn('campaigns', 'status_history')) {
                $table->json('status_history')->nullable()->after('status');
            }

            if (!Schema::hasColumn('campaigns', 'last_status_change')) {
                $table->timestamp('last_status_change')->nullable()->after('status_history');
            }

            if (!Schema::hasColumn('campaigns', 'status_change_reason')) {
                $table->string('status_change_reason')->nullable()->after('last_status_change');
            }

            // Add scheduling fields
            if (!Schema::hasColumn('campaigns', 'scheduled_start_at')) {
                $table->timestamp('scheduled_start_at')->nullable()->after('start_date');
            }

            if (!Schema::hasColumn('campaigns', 'scheduled_stop_at')) {
                $table->timestamp('scheduled_stop_at')->nullable()->after('days');
            }

            if (!Schema::hasColumn('campaigns', 'auto_start')) {
                $table->boolean('auto_start')->default(false)->after('scheduled_stop_at');
            }

            if (!Schema::hasColumn('campaigns', 'auto_stop')) {
                $table->boolean('auto_stop')->default(false)->after('auto_start');
            }

            // Add template relationship if not exists
            if (!Schema::hasColumn('campaigns', 'template_id')) {
                $table->foreignId('template_id')->nullable()->constrained('campaign_templates')->nullOnDelete()->after('user_id');
            }

                        // Add additional performance tracking fields (some may already exist)
            if (!Schema::hasColumn('campaigns', 'total_loss_amount')) {
                $table->decimal('total_loss_amount', 15, 2)->default(0)->after('total_win_amount');
            }

            if (!Schema::hasColumn('campaigns', 'total_bets')) {
                $table->integer('total_bets')->default(0)->after('total_loss_amount');
            }

            if (!Schema::hasColumn('campaigns', 'winning_bets')) {
                $table->integer('winning_bets')->default(0)->after('total_bets');
            }

            if (!Schema::hasColumn('campaigns', 'losing_bets')) {
                $table->integer('losing_bets')->default(0)->after('winning_bets');
            }

            // Add indexes for performance
            $table->index(['status', 'scheduled_start_at']);
            $table->index(['status', 'scheduled_stop_at']);
            $table->index(['user_id', 'status']);
            $table->index('last_status_change');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn([
                'status_history',
                'last_status_change',
                'status_change_reason',
                'scheduled_start_at',
                'scheduled_stop_at',
                'auto_start',
                'auto_stop',
                'template_id',
                'total_loss_amount',
                'total_bets',
                'winning_bets',
                'losing_bets'
            ]);
        });
    }
};
