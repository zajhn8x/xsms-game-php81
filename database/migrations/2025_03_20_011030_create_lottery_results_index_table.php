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
        Schema::create('lottery_results_index', function (Blueprint $table) {
            $table->id();
            $table->date('draw_date'); // Ngày mở thưởng
            $table->char('position',10); // Vị trí số trong giải thưởng
            $table->integer('value'); // Giá trị 0-9
            $table->timestamps();

            $table->unique(['draw_date', 'position']); // Đảm bảo không trùng vị trí trong ngày
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lottery_results_index');
    }
};
