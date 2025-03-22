
<?php

namespace Tests\Feature\Commands;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportLotteryDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_import_csv_data()
    {
        $this->artisan('lottery:import', [
            'file' => base_path('xsmb_mau.csv')
        ])->assertSuccessful();

        $this->assertDatabaseHas('lottery_results', [
            'draw_date' => '2005-10-01'
        ]);

        $this->assertDatabaseHas('lottery_results_index', [
            'draw_date' => '2005-10-01',
            'position' => 'special',
            'value' => '34584'
        ]);
    }
}
