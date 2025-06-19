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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('subscription_type')->default('basic')->after('avatar'); // basic, premium, trial
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_type');
            $table->decimal('balance', 15, 2)->default(0)->after('subscription_expires_at');
            $table->decimal('total_deposit', 15, 2)->default(0)->after('balance');
            $table->decimal('total_withdrawal', 15, 2)->default(0)->after('total_deposit');
            $table->boolean('is_active')->default(true)->after('total_withdrawal');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'avatar', 'subscription_type', 'subscription_expires_at',
                'balance', 'total_deposit', 'total_withdrawal', 'is_active', 'last_login_at'
            ]);
        });
    }
};
