
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\LotteryResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LotteryResultTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_lottery_results()
    {
        LotteryResult::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/lottery-results');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'draw_date', 'prizes', 'lo_array']
                    ]
                ]);
    }

    public function test_can_create_lottery_result()
    {
        $data = [
            'draw_date' => '2023-12-01',
            'prizes' => ['1st' => '12345', '2nd' => '67890'],
            'lo_array' => ['12', '34', '56']
        ];

        $response = $this->postJson('/api/v1/lottery-results', $data);

        $response->assertStatus(201)
                ->assertJson(['data' => $data]);
    }

    public function test_can_update_lottery_result()
    {
        $result = LotteryResult::factory()->create();
        
        $data = [
            'draw_date' => '2023-12-02'
        ];

        $response = $this->putJson("/api/v1/lottery-results/{$result->id}", $data);

        $response->assertStatus(200)
                ->assertJson(['data' => ['draw_date' => $data['draw_date']]]);
    }

    public function test_can_delete_lottery_result()
    {
        $result = LotteryResult::factory()->create();

        $response = $this->deleteJson("/api/v1/lottery-results/{$result->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('lottery_results', ['id' => $result->id]);
    }
}
