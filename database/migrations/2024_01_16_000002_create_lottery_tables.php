
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lottery_cau_meta', function (Blueprint $table) {
            $table->id();
            $table->text('formula_note');
            $table->json('formula_structure');
            $table->timestamps();
        });

        Schema::create('lottery_cau_lo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('lottery_cau_lo')->onDelete('cascade');
            $table->date('draw_date');
            $table->string('lo_number', 2);
            $table->foreignId('formula_id')->constrained('lottery_cau_meta')->onDelete('cascade');
            $table->integer('occurrence');
            $table->enum('status', ['strong', 'medium', 'weak']);
            $table->timestamps();
        });

        Schema::create('lottery_bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('cau_lo_id')->constrained('lottery_cau_lo')->onDelete('cascade');
            $table->decimal('bet_amount', 10, 2);
            $table->enum('bet_status', ['won', 'lost', 'pending']);
            $table->timestamps();
        });

        Schema::create('lottery_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cau_lo_id')->constrained('lottery_cau_lo')->onDelete('cascade');
            $table->text('log_details');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lottery_logs');
        Schema::dropIfExists('lottery_bets');
        Schema::dropIfExists('lottery_cau_lo');
        Schema::dropIfExists('lottery_cau_meta');
    }
};
