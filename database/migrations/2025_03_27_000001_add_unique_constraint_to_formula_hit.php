<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('formula_hit', function (Blueprint $table) {
            // Xóa các bản ghi trùng lặp trước khi thêm unique constraint
            DB::statement('DELETE t1 FROM formula_hit t1 INNER JOIN formula_hit t2 WHERE t1.id > t2.id AND t1.cau_lo_id = t2.cau_lo_id AND t1.ngay = t2.ngay');

            // Thêm unique constraint
            $table->unique(['cau_lo_id', 'ngay', 'so_trung'], 'formula_hit_unique');
        });
    }

    public function down()
    {
        Schema::table('formula_hit', function (Blueprint $table) {
            $table->dropUnique('formula_hit_unique');
        });
    }
};
