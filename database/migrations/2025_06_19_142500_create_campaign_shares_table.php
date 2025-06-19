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
        Schema::create('campaign_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shared_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('share_platform'); // facebook, twitter, telegram, copy_link
            $table->string('share_url');
            $table->integer('click_count')->default(0);
            $table->json('analytics')->nullable(); // Tracking data
            $table->timestamps();

            $table->index(['campaign_id', 'shared_by_user_id']);
            $table->index(['share_platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_shares');
    }
};
