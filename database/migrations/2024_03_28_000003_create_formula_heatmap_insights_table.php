<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('formula_heatmap_insights', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('formula_id');
            $table->date('date');
            $table->enum('type', ['long_run', 'long_run_stop', 'rebound_after_long_run']);
            $table->json('extra');
            $table->decimal('score', 5, 2)->default(0);
            $table->timestamps();

            // Composite unique key
            $table->unique(['formula_id', 'date']);
            
            // Indexes
            $table->index('type');
            $table->index('date');
            $table->index('score');
        });
    }

    public function down()
    {
        Schema::dropIfExists('formula_heatmap_insights');
    }
}; 