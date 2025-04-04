<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('campaign_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->string('lo_number', 2);
            $table->integer('points');
            $table->decimal('amount', 10, 2);
            $table->decimal('win_amount', 10, 2)->default(0);
            $table->date('bet_date');
            $table->boolean('is_win')->default(false);
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('campaign_bets');
    }
};
