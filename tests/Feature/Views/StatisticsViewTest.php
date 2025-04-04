<?php

namespace Tests\Feature\Views;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StatisticsViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_statistics_view_can_be_rendered()
    {
        $response = $this->get('/statistics');
        $response->assertStatus(200);
        $response->assertViewIs('statistics.index');
    }

    public function test_statistics_view_has_required_data()
    {
        $response = $this->get('/statistics');
        $response->assertViewHas(['totalBets', 'totalAmount', 'totalWins', 'totalWinnings']);
    }
}
