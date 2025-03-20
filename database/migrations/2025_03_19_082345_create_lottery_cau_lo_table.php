<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('lottery_cau_lo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('lottery_cau_lo')->onDelete('cascade');
            $table->date('draw_date');
            $table->string('lo_number', 2);
            $table->foreignId('formula_id')->constrained('lottery_cau_meta')->onDelete('cascade');
            $table->integer('occurrence');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('lottery_cau_lo');
    }
};
