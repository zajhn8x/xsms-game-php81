<?php

namespace Tests\Feature\Components;

use Tests\TestCase;
use App\Models\LotteryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LotteryResultComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_lottery_result_component_renders()
    {
        $result = LotteryResult::factory()->create();

        $view = $this->blade(
            '<x-lottery-result :result="$result"/>',
            ['result' => $result]
        );

        $view->assertSee($result->draw_date->format('Y-m-d'));
    }
}
