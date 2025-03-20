<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('lottery_cau_lo_meta', function (Blueprint $table) {
            $table->id();
            $table->string('formula_name', 255);
            $table->text('formula_note')->nullable();
            $table->json('formula_structure');
            $table->enum('combination_type', ['single', 'pair', 'multi', 'dynamic']);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('lottery_cau_lo_meta');
    }
};
