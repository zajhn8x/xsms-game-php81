
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lottery_cau_lo_hit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cau_lo_id');
            $table->date('ngay');
            $table->string('so_trung', 10);
            $table->foreign('cau_lo_id')->references('id')->on('lottery_cau_lo');
            $table->index('ngay');
            $table->index('cau_lo_id');
        });

        DB::statement("ALTER TABLE lottery_cau_lo_hit PARTITION BY RANGE (YEAR(ngay)) (
            PARTITION p2023 VALUES LESS THAN (2024),
            PARTITION p2024 VALUES LESS THAN (2025)
        )");
    }

    public function down()
    {
        Schema::dropIfExists('lottery_cau_lo_hit');
    }
};
