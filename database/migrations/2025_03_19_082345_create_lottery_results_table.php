<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('lottery_results', function (Blueprint $table) {
            $table->id();
            $table->date('draw_date');
            $table->json('prizes');
            $table->json('lo_array');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lottery_results');
    }
};
