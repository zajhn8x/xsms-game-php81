<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->integer('days');
            $table->decimal('initial_balance', 12, 2);
            $table->decimal('current_balance', 12, 2);
            $table->enum('bet_type', ['manual', 'formula']);
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->date('last_updated')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('campaigns');
    }
};
