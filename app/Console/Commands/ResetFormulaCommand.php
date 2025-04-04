<?php

namespace App\Console\Commands;

use App\Models\FormulaHit;
use App\Models\LotteryFormula;
use Illuminate\Console\Command;

class ResetFormulaCommand extends Command
{
    protected $signature   = 'formula:reset {id : ID của formula cần reset}';
    protected $description = 'Reset trạng thái và xóa lịch sử hit của một formula';

    public function handle()
    {
        $formulaId = $this->argument('id');

        // Xóa các bản ghi hit
        FormulaHit::where('cau_lo_id', $formulaId)->delete();

        // Reset trạng thái formula
        LotteryFormula::where('id', $formulaId)
            ->update([
                'is_processed' => false,
                'processed_days' => 0,
                'last_processed_date' => null,
                'processing_status' => 'pending',
            ]);

        $this->info("Đã reset formula ID: {$formulaId}");
        return 0;
    }
}
