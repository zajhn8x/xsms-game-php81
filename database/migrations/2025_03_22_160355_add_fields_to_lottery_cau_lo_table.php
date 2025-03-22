<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('lottery_cau_lo', function (Blueprint $table) {
            $table->boolean('is_processed')->default(false)->after('is_active');
            $table->integer('processed_days')->default(0)->after('is_processed');
            $table->date('last_processed_date')->nullable()->after('processed_days');
            $table->enum('processing_status', ['pending', 'in_progress', 'completed'])
                ->default('pending')
                ->after('last_processed_date');
        });
    }

    public function down()
    {
        Schema::table('lottery_cau_lo', function (Blueprint $table) {
            $table->dropColumn(['combination_type', 'is_processed', 'processed_days', 'last_processed_date', 'processing_status']);
        });
    }
};
