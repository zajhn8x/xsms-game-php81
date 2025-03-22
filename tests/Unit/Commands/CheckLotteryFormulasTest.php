
<?php

namespace Tests\Unit\Commands;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessLotteryFormula;

class CheckLotteryFormulasTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_formulas_command()
    {
        Queue::fake();

        $this->artisan('lottery:check-formulas')
             ->assertExitCode(0);

        Queue::assertPushed(ProcessLotteryFormula::class);
    }
}
