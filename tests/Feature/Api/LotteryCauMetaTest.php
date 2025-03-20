
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\LotteryCauMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LotteryCauMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_lottery_cau_meta()
    {
        LotteryCauMeta::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/lottery-cau-meta');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'description']
                    ]
                ]);
    }

    public function test_can_create_lottery_cau_meta()
    {
        $data = [
            'name' => 'Test Formula',
            'description' => 'Test Description'
        ];

        $response = $this->postJson('/api/v1/lottery-cau-meta', $data);

        $response->assertStatus(201)
                ->assertJson(['data' => $data]);
    }

    public function test_can_update_lottery_cau_meta()
    {
        $meta = LotteryCauMeta::factory()->create();
        
        $data = [
            'name' => 'Updated Formula'
        ];

        $response = $this->putJson("/api/v1/lottery-cau-meta/{$meta->id}", $data);

        $response->assertStatus(200)
                ->assertJson(['data' => ['name' => $data['name']]]);
    }

    public function test_can_delete_lottery_cau_meta()
    {
        $meta = LotteryCauMeta::factory()->create();

        $response = $this->deleteJson("/api/v1/lottery-cau-meta/{$meta->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('lottery_cau_meta', ['id' => $meta->id]);
    }
}
