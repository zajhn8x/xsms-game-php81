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
        Schema::table('wallet_transactions', function (Blueprint $table) {
            // Add gateway-specific fields if they don't exist
            if (!Schema::hasColumn('wallet_transactions', 'gateway_request_id')) {
                $table->string('gateway_request_id')->nullable()->after('gateway_transaction_id');
            }

            if (!Schema::hasColumn('wallet_transactions', 'gateway_response')) {
                $table->json('gateway_response')->nullable()->after('gateway_request_id');
            }

            if (!Schema::hasColumn('wallet_transactions', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('processed_at');
            }

            if (!Schema::hasColumn('wallet_transactions', 'payment_url')) {
                $table->text('payment_url')->nullable()->after('expires_at');
            }

            // Add indexes for better performance
            $table->index(['status', 'gateway'], 'idx_status_gateway');
            $table->index(['expires_at'], 'idx_expires_at');
            $table->index(['gateway_request_id'], 'idx_gateway_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex('idx_status_gateway');
            $table->dropIndex('idx_expires_at');
            $table->dropIndex('idx_gateway_request_id');

            $table->dropColumn([
                'gateway_request_id',
                'gateway_response',
                'expires_at',
                'payment_url'
            ]);
        });
    }
};
