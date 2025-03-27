<?php

namespace Tests\Feature\Models;

use App\Models\LotteryFormulaMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LotteryFormulaMetaTest extends BaseModelTest
{
    /** @test */
    public function it_can_create_a_lottery_formula_meta()
    {
        $data = [
            'formula_name' => 'Cầu Lô Song Thủ G4-ĐB #7a928',
            'formula_note' => 'Công thức ghép cầu lô tự động từ vị trí G4-4-1 và GDB-1-4',
            'formula_structure' => [
                'positions' => ['G4-4-1', 'GDB-1-4'],
                'description' => 'Ghép cầu lô từ vị trí G4-4-1 và GDB-1-4'
            ],
            'combination_type' => 'pair',
        ];

        $formulaMeta = LotteryFormulaMeta::create($data);

        $this->assertDatabaseHas('lottery_formula_meta', [
            'id' => $formulaMeta->id,
            'formula_name' => $data['formula_name'],
            'combination_type' => $data['combination_type']
        ]);

        $this->assertEquals(['G4-4-1', 'GDB-1-4'], $formulaMeta->positions);
    }
}
