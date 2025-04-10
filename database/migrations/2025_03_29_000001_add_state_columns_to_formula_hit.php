
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStateColumnsToFormulaHit extends Migration
{
    public function up()
    {
        Schema::table('formula_hit', function (Blueprint $table) {
            $table->tinyInteger('status')->default(0)->after('so_trung')->comment('0: normal, 1: cung chieu, 2: 2 nhay 1 so, 3: 2 nhay cap, 4: nhieu hon 2 nhay');
        });
    }

    public function down()
    {
        Schema::table('formula_hit', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
