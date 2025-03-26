<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormulaStatisticsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('formula_statistics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('formula_id'); // Liên kết công thức cầu lô
            $table->smallInteger('year'); // Chia theo năm (2005, 2006, ..., 2025)
            $table->tinyInteger('quarter'); // Quý (1, 2, 3, 4)
            $table->smallInteger('frequency')->default(0); // Tổng số lần trúng trong quý
            $table->smallInteger('win_cycle')->default(0); // Chu kỳ trúng trung bình
            $table->decimal('probability', 5, 2)->default(0.00); // Xác suất trúng (%) trong quý

            // Đếm số lần cầu chạy liên tiếp theo mức độ
            $table->tinyInteger('streak_3')->unsigned()->default(0);
            $table->tinyInteger('streak_4')->unsigned()->default(0);
            $table->tinyInteger('streak_5')->unsigned()->default(0);
            $table->tinyInteger('streak_6')->unsigned()->default(0);
            $table->tinyInteger('streak_more_6')->unsigned()->default(0);

            // Trạng thái cầu chạy từ quý trước
            $table->tinyInteger('prev_streak')->unsigned()->default(0);
            // Lưu lại prev_streak của quý sau
            $table->tinyInteger('last_streak')->unsigned()->default(0);

            $table->timestamps();

            $table->unique(['formula_id', 'year', 'quarter'], 'uq_formula_quarter');
            $table->foreign('formula_id')->references('id')->on('lottery_formula')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('formula_statistics');
    }
}
